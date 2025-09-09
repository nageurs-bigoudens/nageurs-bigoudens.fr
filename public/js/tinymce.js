// code à réorganiser
// seule certaines fonctions ont leur place dans Editor, d'autres servent à manipuler les articles d'une autre manière (déplacer, supprimer...)
// => encapsuler Editor dans une classe Article (comme la balise) qui existe même quand l'éditeur est fermé


/* -- utilisé par les évènements -- */
let editors = {};
function openEditor(id){
    if(!editors[id]){
        editors[id] = new Editor(id); // appel de init à l'intérieur
    }
    //else{editors[id].reopen();}
}
// placement d'un nouvel article dans un bloc "Articles libres"
function setArticlePlacement(id){
    if(editors[id]){
        editors[id].setArticlePlacement(id);
    }
}
function closeEditor(id, restore_old){
    if(editors[id]){
        editors[id].close(restore_old);
    }
}
function submitArticle(id, clone = null)
{
    // bouton Valider de l'éditeur
    if(editors[id]){
        editors[id].submit(clone);
    }
    // bouton Tout enregistrer
    else if(window.Config.page === "article" && id[0] === 'n'){
        if(Object.keys(editors).length === 0){ // vérifier qu'il n'y a pas d'éditeur ouvert
            editors[id] = new Editor(id);
            editors[id].submit();
        }
        else{
            toastNotify("Un editeur est ouvert. Validez ou annulez d'abord votre saisie dans chaque éditeur.");
        }
    }
}
// standalone contraîrement aux autres fonctions ici
function deleteArticle(id){
    if(confirm('Voulez-vous vraiment supprimer cet article ?'))
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
                findParentByTagName(articleElement, 'article').remove();
                toastNotify("L'article a été supprimé.");
            }
            else{
                toastNotify('Erreur lors de la suppression de l\'article.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }
}



class Editor
{
    constructor(id){
        this.id = id;
        this.article = document.getElementById(this.id);
        this.creation_mode = this.id[0] === 'n' ? true : false;
        //this.isOpen = false;
        this.tiny_instance = null;

        // moche, on ne devrait sortir l'envoi des données avec fetch de Editor.submit
        if(!this.creation_mode || window.Config.page !== 'article'){
            if(this.creation_mode && window.Config.page !== 'article'){
                this.setArticlePlacement(this.id);
            }
            else{
                // insérer le contenu de l'article dans l'éditeur
                this.article.setAttribute('data-original-content', this.article.innerHTML);
            }
            this.init();
        }
        //else // bouton Tout enregistrer, pas d'éditeur
    }

    setArticlePlacement(id_block){
        const checked_button = document.querySelector('input[name="article_placement-' + id_block + '"]:checked');
        if(checked_button){ // vrai clic
            this.placement = checked_button.value;
        }
        else{
            document.getElementById('radio_last-' + id_block).checked = true; // faux clic
            this.placement = 'last';
        }
    }
    
    init(){
        tinymce.init({
            selector: `[id="${this.id}"]`, // écrire [id="246"] au lieu de #246 parce que l'id commence par un chiffre
            language: 'fr_FR', // téléchargement ici: https://www.tiny.cloud/get-tiny/language-packages/
            language_url: 'js/tinymce-langs/fr_FR.js', // ou installer tweeb/tinymce-i18n avec composer
            license_key: 'gpl',
            branding: false,
            plugins: 'lists link autolink table image media autoresize help',
            toolbar: 'undo redo newdocument print selectall styles bold italic underline strikethrough fontsizeinput forecolor backcolor fontfamily align numlist bullist outdent indent table link image media help',
            menubar: false,
            toolbar_mode: 'wrap',
            statusbar: false,
            // les fonctions fléchées permettent de garder le contexte (= this)
            setup: (editor) => {
                editor.on('init', () => {
                    this.tiny_instance = editor;
                    
                    // boutons "Modifier", "Supprimer", "déplacer vers le haut", "déplacer vers le bas", "Annuler" et "Soumettre"
                    document.getElementById(`cancel-${this.id}`).classList.remove('hidden');
                    document.getElementById(`submit-${this.id}`).classList.remove('hidden');
                    const radio = document.getElementById(`radio-${this.id}`);
                    if(radio){
                        radio.classList.remove('hidden');
                    }
                    if(this.creation_mode){
                        document.getElementById(`new-${this.id}`).classList.add('hidden'); // id = new-new-id_node
                    }
                    else{
                        document.getElementById(`edit-${this.id}`).classList.add('hidden');
                        if(window.Config.page !== 'article'){
                            document.getElementById(`position_up-${this.id}`).classList.add('hidden');
                            document.getElementById(`position_down-${this.id}`).classList.add('hidden');
                            document.getElementById(`delete-${this.id}`).classList.add('hidden');
                        }
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
    }

    close(restore_old = true){
        tinymce.remove(`[id="${this.id}"]`); // comme dans tinymce.init
        delete editors[this.id];

        // Restaurer le contenu d'origine de l'article
        if(restore_old){
            const original_content = document.getElementById(this.id).getAttribute('data-original-content');
            document.getElementById(this.id).innerHTML = original_content;
        }

        // boutons: "Nouvel article", Modifier", "Supprimer", "déplacer vers le haut", "déplacer vers le bas", "Annuler" et "Valider"
        document.getElementById(`cancel-${this.id}`).classList.add('hidden');
        document.getElementById(`submit-${this.id}`).classList.add('hidden');

        const radio = document.getElementById(`radio-${this.id}`);
        if(radio){
            document.querySelector('input[name="article_placement-' + this.id + '"]:checked').checked = false; // décoche l'option "en mémoire"
            radio.classList.add('hidden');
        }
        
        if(this.creation_mode){
            document.getElementById(`new-${this.id}`).classList.remove('hidden'); // id = new-new-id_node
        }
        else{
            document.getElementById(`edit-${this.id}`).classList.remove('hidden');
            if(window.Config.page !== 'article'){
                document.getElementById(`position_up-${this.id}`).classList.remove('hidden');
                document.getElementById(`position_down-${this.id}`).classList.remove('hidden');
                document.getElementById(`delete-${this.id}`).classList.remove('hidden');
            }
        }
    }

    submit(clone = null){
        //var editor;
        var content;
        const params = new URL(document.location).searchParams; // "search" = ? et paramètres, searchParams = objet avec des getters
        // à comparer avec: new URLSearchParams(window.location.search);
        // c'est pareil ou pas?

        // clic sur "Tout enregistrer" (ne devrait pas se situer dans Editor)
        if(this.creation_mode && window.Config.page === 'article'){
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
        else if(window.Config.page === 'article' && params != null && params.get("id")[0] === 'n'){
            this.close(false);
            return;
        }
        // dans les autres cas, on doit pouvoir récupérer l'éditeur
        else{
            // l'éditeur correspond à l'article OU si page = "article" à un élément: titre, aperçu, article
            //editor = editors[id];
            if(!this.tiny_instance){
                console.error("Éditeur non trouvé pour l'article:", this.id);
                return;
            }
            content = this.tiny_instance.getContent();
        }

        let fetch_params = {id: this.id, content: content};
        if(this.placement){
            fetch_params['placement'] = this.placement;
        }
        
        // Envoi AJAX au serveur
        fetch('index.php?action=editor_submit', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(fetch_params)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success)
            {
                if(this.creation_mode && window.Config.page === 'article'){
                    console.log('données envoyées au serveur avec succès.');

                    // redirection page de l'article
                    window.setTimeout(function(){
                        const url_params = new URLSearchParams(window.location.search); // le "$_GET" de javascript
                        location.href = "index.php?page=article&id=" + data.article_id + "&from=" + url_params.get('from');
                    }, 0);
                }
                else{
                    // Fermer l'éditeur et mettre à jour le contenu de l'article
                    this.close(false);
                    if(this.creation_mode){
                        makeNewArticleButtons(this.id, data.article_id, clone, this.placement);
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
    
    //reopen(){}

    /*destroy(){
        this.close();
        delete editors[this.id];
        console.log(`Editor ${this.id} détruit.`);
    }*/
}





// restera ici jusqu'à ce que la gestion des balises soient faite ailleurs
function makeNewArticleButtons(id, article_id, clone, placement = 'last')
{
    var share_btn = document.querySelector(`.share.hidden`); // combinaison de deux classes
    var new_btn = document.getElementById(`new-${id}`);
    var edit_btn = document.getElementById(`edit-${id}`);
    var pos_up_btn = document.getElementById(`position_up-${id}`);
    var pos_down_btn = document.getElementById(`position_down-${id}`);
    var delete_btn = document.getElementById(`delete-${id}`);
    var cancel_btn = document.getElementById(`cancel-${id}`);
    var submit_btn = document.getElementById(`submit-${id}`);

    share_btn.classList.remove('hidden');
    new_btn.classList.add('hidden');
    edit_btn.classList.remove('hidden');
    pos_up_btn.classList.remove('hidden');
    pos_down_btn.classList.remove('hidden');
    delete_btn.classList.remove('hidden');
    //cancel_btn.classList.add('hidden');
    //submit_btn.classList.add('hidden');

    var article = document.getElementById(id);
    var article_elem_parent = findParentByTagName(article, 'article');
    
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
    
    var section_child = article_elem_parent.parentNode.querySelector('.section_child'); // renommer section_child
    
    // parentNode vise la balise section
    article_elem_parent.parentNode.replaceChild(clone.cloneNode(true), article_elem_parent); // clone du squelette pour le garder intact

    if(placement === 'first'){
        section_child.insertBefore(article_elem_parent, section_child.firstChild);
    }
    else{ // = 'last'
        section_child.appendChild(article_elem_parent);
    }
}