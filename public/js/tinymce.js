let editors = {};

function openEditor(id, page = '') {
    var creation_mode;
    var real_id;
    var article;

    // création ou modification d'un article?
    if(id[0] === 'n'){
        creation_mode = true;
        article = document.getElementById(id);
    }
    else{
        creation_mode = false;
        // Récupérer et sauvegarder le contenu d'origine de l'article
        real_id = 'i' + id.slice(1);
        article = document.getElementById(id);
        document.getElementById(id).setAttribute('data-original-content', article.innerHTML);
    }

    tinymce.init({
        selector: `#${id}`,
        language: 'fr_FR', // téléchargement ici: https://www.tiny.cloud/get-tiny/language-packages/
        language_url: 'js/tinymce-langs/fr_FR.js', // ou installer tweeb/tinymce-i18n avec composer
        license_key: 'gpl',
        branding: false,
        plugins: 'lists link autolink table image media autoresize help',
        toolbar: 'undo redo newdocument print selectall styles bold italic underline strikethrough fontsizeinput forecolor backcolor fontfamily align numlist bullist outdent indent table link image media help',
        menubar: false,
        toolbar_mode: 'wrap',
        statusbar: false,
        setup: function (editor) {
            editor.on('init', function (){
                editors[id] = editor;
                
                // boutons "Modifier", "Supprimer", "déplacer vers le haut", "déplacer vers le bas", "Annuler" et "Soumettre"
                document.querySelector(`#cancel-${id}`).classList.remove('hidden');
                document.querySelector(`#submit-${id}`).classList.remove('hidden');
                if(creation_mode === false){
                    document.querySelector(`#edit-${id}`).classList.add('hidden');
                    if(page != 'article'){
                        document.querySelector(`#position_up-${id}`).classList.add('hidden');
                        document.querySelector(`#position_down-${id}`).classList.add('hidden');
                        document.querySelector(`#delete-${real_id}`).classList.add('hidden');
                    }
                }
                else{
                    document.querySelector(`#new-${id}`).classList.add('hidden'); // id = new-new-id_node
                }
            });
            let skipPastePreProcess = false;
            editor.on('Paste', function (e){ // déclenchement AVANT PastePreProcess et quelque que soit le contenu collé
                const clipboardData = (e.clipboardData || e.originalEvent.clipboardData);
                if(!clipboardData){
                    return;
                }
                const items = clipboardData.items;
                let foundImage = false;

                for(let i = 0; i < items.length; i++){
                    let item = items[i];

                    if(item.type.indexOf('image') !== -1){ // test type MIME contenant image
                        foundImage = true;

                        const file = item.getAsFile(); // presse-papier => fichier lisible
                        const reader = new FileReader();

                        reader.onload = function (event){ // fonction exécutée lorsque reader.readAsDataURL(file) est terminée
                            const base64Data = event.target.result; // données de l'image

                            fetch('index.php?action=upload_image_base64', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ image_base64: base64Data })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if(data.location){
                                    editor.insertContent('<img src="' + data.location + '">');
                                }
                            })
                            .catch(error => {
                                console.error('Erreur lors de l’upload de l’image base64 :', error);
                            });
                        };
                        reader.readAsDataURL(file); // lecture asynchrone du fichier
                    }
                }

                if(foundImage){
                    e.preventDefault(); // supprime le collage automatiue
                    skipPastePreProcess = true; // désactiver le PastePreProcess pour ce collage
                }
            });
            editor.on('PastePreProcess', function (e){ // déclenchement au collage AVANT insertion dans l'éditeur
                const parser = new DOMParser();
                const doc = parser.parseFromString(e.content, 'text/html');
                const images = doc.querySelectorAll('img');
                
                let downloads_in_progress = [];
                
                images.forEach(img => {
                    if(img.src.startsWith('file://')){ // détection d'images non insérables
                        console.warn('Image locale non insérable dans tinymce :', img.src);
                        img.outerHTML = `<div style="border:1px solid red; padding:10px; margin:5px 0; background-color:#ffe6e6; color:#a94442; font-size:14px;">
                            "Image locale non insérée (vient-elle d'un document LibreOffice ?). Effacez ce message rouge et copiez-collez l'image seule.</div>`;
                    }
                    else if(img.src.startsWith('http')){ // détection d'images web
                        const promise = fetch('index.php?action=upload_image_url', { // promesse d'un fichier téléchargeable sur le serveur
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ image_url: img.src })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.location){
                                img.src = data.location; // remplacer l'image par celle du serveur
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de l’upload de l’image distante:', error);
                        });
                        
                        downloads_in_progress.push(promise);
                    }
                });
                
                // une image web ou plus: différer l'insertion dans l'éditeur le temps que le serveur télécharge les images
                if(downloads_in_progress.length > 0){
                    e.preventDefault();

                    Promise.all(downloads_in_progress).then(() => {
                        e.content = doc.body.innerHTML; // remplacement du HTML dans l'éditeur par la copie modifiée (doc)
                        editor.insertContent(e.content);
                    });
                }
                else{
                    e.content = doc.body.innerHTML; // remplacement du HTML dans l'éditeur par la copie modifiée (doc)
                }
            }); // fin editor.on('PastePreProcess'...
        },
        // upload d'image natif de tinymce avec le bouton "Insérer une image"
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("file", blobInfo.blob());

            fetch("index.php?action=upload_image_tinymce", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.location) {
                    resolve(data.location);
                }
                else {
                    reject("Erreur: Chemin d'image invalide");
                }
            })
            .catch(error => {
                reject("Erreur lors de l'upload");
            });
        }),
        image_caption: true
    });

    // Remplacer le contenu de l'article par l'éditeur
    if(creation_mode === false){
        document.getElementById(id).innerHTML = article.innerHTML;
    }
}

function deleteArticle(id, page = '') {
    if (confirm('Voulez-vous vraiment supprimer cet article ?'))
    {
        // Envoyer une requête au serveur pour supprimer l'article
        fetch('index.php?action=delete_article', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success)
            {
                // Supprimer l'article du DOM
                const articleElement = document.getElementById(id);
                articleElement.parentElement.parentElement.remove(); // <article> est deux niveau au dessus
                toastNotify("L'article a été supprimé.");
            }
            else {
                toastNotify('Erreur lors de la suppression de l\'article.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }
}

function closeEditor(id, page = '', restore_old = true)
{
    var creation_mode;
    var real_id;
    var article;
    var parent;

    // création ou modification d'un article?
    if(id[0] === 'n'){
        creation_mode = true;
    }
    else{
        creation_mode = false;
    }

    // Fermer l'éditeur
    tinymce.remove(`#${id}`);
    delete editors[id];
    
    if(creation_mode){
        article = document.getElementById(id);
        parent = findParent(article, 'section');
    }
    else{
        real_id = 'i' + id.slice(1);
    }

    // Restaurer le contenu d'origine de l'article
    if(restore_old){
        const originalContent = document.getElementById(id).getAttribute('data-original-content');
        document.getElementById(id).innerHTML = originalContent;
    }

    // boutons: "Nouvel article", Modifier", "Supprimer", "déplacer vers le haut", "déplacer vers le bas", "Annuler" et "Valider"
    document.querySelector(`#cancel-${id}`).classList.add('hidden');
    document.querySelector(`#submit-${id}`).classList.add('hidden');
    if(creation_mode){
        document.querySelector(`#new-${id}`).classList.remove('hidden'); // id = new-new-id_node
    }
    else{
        document.querySelector(`#edit-${id}`).classList.remove('hidden');
        if(page != 'article'){
            document.querySelector(`#position_up-${id}`).classList.remove('hidden');
            document.querySelector(`#position_down-${id}`).classList.remove('hidden');
            document.querySelector(`#delete-${id}`).classList.remove('hidden');
        }
    }
}

function submitArticle(id, page = '', clone = null)
{
    var editor;
    var content;
    const params = new URL(document.location).searchParams; // "search" = ? et paramètres, searchParams = objet avec des getters

    // clic sur "Tout enregistrer"
    if(id[0] === 'n' && page === 'article'){
        const prefixes = ['t', 'p', 'i', 'd'];
        const allElemsWithId = document.querySelectorAll('.data');
        content = {};
        var id_from_builder;

        allElemsWithId.forEach(element => {
            const first_letter = element.id.charAt(0).toLowerCase();
            if(prefixes.includes(first_letter)){
                content[first_letter] = element.innerHTML;
                if(first_letter === 'i'){
                    id_from_builder = element.id;
                }
            }
        })
        content['d'] = dateToISO(content['d']);
    }
    // champs à remplir des nouvelles "news"
    else if(page === 'article' && params != null && params.get("id")[0] === 'n'){
        closeEditor(id, page, false);
        //makeNewArticleButtons(id, id, clone);
        return;
    }
    // dans les autres cas, on doit pouvoir récupérer l'éditeur
    else{
        // l'éditeur correspond à l'article OU page "article" à un élément: titre, aperçu, article
        editor = editors[id];
        if(!editor) {
            console.error('Éditeur non trouvé pour l\'article:', id);
            return;
        }
        content = editor.getContent();
    }
    
    // Envoi AJAX au serveur
    fetch('index.php?action=editor_submit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({id: id, content: content})
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            //console.log(data.article_id);
            if(id[0] === 'n' && page === 'article'){
                console.log('données envoyées au serveur avec succès.');

                // redirection page de l'article
                window.setTimeout(function(){
                    const url_params = new URLSearchParams(window.location.search); // le "$_GET" de javascript
                    location.href = "index.php?page=article&id=" + data.article_id + "&from=" + url_params.get('from');
                }, 0);
            }
            else{
                // Fermer l'éditeur et mettre à jour le contenu de l'article
                closeEditor(id, page, false);
                if(id[0] === 'n'){
                    makeNewArticleButtons(id, data.article_id, clone);
                }
            }
        }
        else{
            alert('Erreur lors de la sauvegarde de l\'article.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function makeNewArticleButtons(id, article_id, clone)
{
    var share_btn = document.querySelector(`.share.hidden`); // combinaison de deux classes
    var new_btn = document.querySelector(`#new-${id}`);
    var edit_btn = document.querySelector(`#edit-${id}`);
    var pos_up_btn = document.querySelector(`#position_up-${id}`);
    var pos_down_btn = document.querySelector(`#position_down-${id}`);
    var delete_btn = document.querySelector(`#delete-${id}`);
    var cancel_btn = document.querySelector(`#cancel-${id}`);
    var submit_btn = document.querySelector(`#submit-${id}`);

    share_btn.classList.remove('hidden')
    new_btn.classList.add('hidden');
    edit_btn.classList.remove('hidden');
    pos_up_btn.classList.remove('hidden');
    pos_down_btn.classList.remove('hidden');
    delete_btn.classList.remove('hidden');
    //cancel_btn.classList.add('hidden');
    //submit_btn.classList.add('hidden');

    var article = document.getElementById(id);
    var parent = findParent(article, 'article');
    
    share_btn.setAttribute('onclick', "copyInClipBoard('" + window.location.href + article_id + "')"); // # de l'ancre ajouté au clic sur le lien ouvrant l'éditeur
    article.id = article_id;
    edit_btn.id = 'edit-' + article_id;
    edit_btn.querySelector('.action_icon').setAttribute('onclick', "openEditor('" + article_id + "')");
    pos_up_btn.id = 'position_up-' + article_id;
    pos_up_btn.querySelector('.action_icon').setAttribute('onclick', "switchPositions('" + article_id + "', 'up')");
    pos_down_btn.id = 'position_down-' + article_id;
    pos_down_btn.querySelector('.action_icon').setAttribute('onclick', "switchPositions('" + article_id + "', 'down')");
    delete_btn.id = 'delete-' + article_id;
    delete_btn.querySelector('.action_icon').setAttribute('onclick', "deleteArticle('" + article_id + "')");
    cancel_btn.id = 'cancel-' + article_id;
    cancel_btn.querySelector('button').setAttribute('onclick', "closeEditor('" + article_id + "')");
    submit_btn.id = 'submit-' + article_id;
    submit_btn.querySelector('button').setAttribute('onclick', "submitArticle('" + article_id + "')");

    var next_div = parent.nextElementSibling.nextElementSibling;
    parent.parentNode.replaceChild(clone.cloneNode(true), parent); // clone du squelette pour le garder intact
    next_div.appendChild(parent);
}