// code à réorganiser
// seules certaines fonctions ont leur place dans Editor, d'autres servent à manipuler les articles d'une autre manière (déplacer, supprimer...)
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
    extensions_white_list = ['pdf', 'rtf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'];
    // = $extensions_white_list côté PHP

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
            language: 'fr_FR',
            language_url: 'js/tinymce-langs/fr_FR.js',
            license_key: 'gpl',
            branding: false,
            plugins: 'lists link autolink table image media autoresize help',
            toolbar: 'undo redo newdocument print selectall styles bold italic underline strikethrough fontsizeinput forecolor backcolor fontfamily align numlist bullist outdent indent table link image media help',
            menubar: false,
            toolbar_mode: 'wrap',
            statusbar: false,
            link_title: false, // supprime le champ compliqué "titre" (apparaît au survol du lien) dans la fenêtre "link"
            /*link_attributes_postprocess: (attrs) => { // modifier les attributs des liens créés
                console.log(attrs);
                if (attrs.rel) {
                    attrs.rel += 'noreferrer'; // cacher la page d'où on vient
                }
            },*/
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
                editor.on('Paste', (e) => { // déclenchement AVANT PastePreProcess et quelque que soit le contenu collé
                    if(!e.clipboardData){ // e.clipboardData: DataTransfer
                        return;
                    }
                    const items = e.clipboardData.items; // base64
                    const files = e.clipboardData.files; // explorateur de fichiers
                    let found_file = false;

                    // données dans files
                    if(files && files.length > 0){ // noter que files peut être vide, alors que items non
                        for(let i = 0; i < files.length; i++){
                            let file = files[i];
                            
                            if(this.extensions_white_list.includes(file.name.split('.').pop()?.toLowerCase() || '')){
                                found_file = true;
                                this.uploadDocument(file, editor);
                            }
                            else if(file.type.indexOf('image') !== -1){ 
                                found_file = true;
                                this.uploadImageBase64(file, editor);
                            }
                        }
                    }
                    // données dans items
                    else{ // les images collées depuis l'explorateur sont aussi dans items, or elles sont déjà gérées plus haut
                        for(let i = 0; i < items.length; i++){
                            let item = items[i];

                            if(item.type.indexOf('image') !== -1){ // test type MIME contenant image
                                found_file = true;
                                const file = item.getAsFile(); // presse-papier => fichier lisible
                                if(file){
                                    this.uploadImageBase64(file, editor);
                                }
                                else{
                                    console.error('fichier invalide');
                                }
                            }
                        }
                    }

                    if(found_file){
                        e.preventDefault(); // supprime le collage automatiue
                        skipPastePreProcess = true; // désactiver le PastePreProcess pour ce collage
                    }
                });
                editor.on('PastePreProcess', function (e){ // déclenchement au collage AVANT insertion dans l'éditeur
                    if(skipPastePreProcess){
                        skipPastePreProcess = false; // réinitialiser pour la prochaine fois
                        return; // ignorer ce traitement
                    }

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
                });
                // glisser-déposer de fichiers (sauf images qui sont déjà gérées nativement)
                editor.on('drop', (e) => {
                    const data = e.dataTransfer;
                    if(!data || !data.files || data.files.length === 0){
                        return; // Laisser TinyMCE gérer (texte, images déjà supportées, etc.)
                    }
                    const files = data.files;

                    let has_documents = false;
                    for(let i = 0; i < files.length; i++){
                        if(this.extensions_white_list.includes(files[i].name.split('.').pop()?.toLowerCase() || '')){
                            has_documents = true;
                            break;
                        }
                    }
                    
                    if(has_documents){
                        e.preventDefault();
                        e.stopPropagation();
                        
                        for(let i = 0; i < files.length; i++){
                            let file = files[i];
                            
                            if(this.extensions_white_list.includes(file.name.split('.').pop()?.toLowerCase() || '')){
                                this.uploadDocument(file, editor);
                            }
                            else if(file.type.indexOf('image') !== -1){
                                this.uploadImageBase64(file, editor);
                            }
                        }
                    }
                    // autres cas: tinymce gère tout seul
                });
            },
            // upload d'image avec le bouton "Insérer une image"
            images_upload_handler: this.images_upload_handler, // = fonction fléchée
            // upload de documents avec le bouton "insérer un lien"
            files_upload_handler: this.files_upload_handler, // = fonction fléchée
            documents_file_types: [ // files_upload_handler a besoin qu'on lui donne tous les types mime
                { mimeType: 'application/pdf', extensions: [ 'pdf' ] },
                { mimeType: 'application/rtf', extensions: [ 'rtf' ] },
                { mimeType: 'application/msword', extensions: [ 'doc' ] },
                { mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', extensions: [ 'docx' ] },
                { mimeType: 'application/vnd.ms-excel', extensions: [ 'xls' ] },
                { mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', extensions: [ 'xlsx' ] },
                { mimeType: 'application/vnd.ms-powerpoint', extensions: [ 'ppt' ] },
                { mimeType: 'application/vnd.openxmlformats-officedocument.presentationml.presentation', extensions: [ 'pptx' ] },
                { mimeType: 'application/vnd.oasis.opendocument.text', extensions: [ 'odt' ] },
                { mimeType: 'application/vnd.oasis.opendocument.spreadsheet', extensions: [ 'ods' ] },
                { mimeType: 'application/vnd.oasis.opendocument.presentation', extensions: [ 'odp' ] }
            ],
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
        let content;
        const params = new URL(document.location).searchParams; // "search" = ? et paramètres, searchParams = objet avec des getters
        // à comparer avec: new URLSearchParams(window.location.search);
        // c'est pareil ou pas?

        // clic sur "Tout enregistrer" (ne devrait pas se situer dans Editor)
        if(this.creation_mode && window.Config.page === 'article'){
            const prefixes = ['t', 'p', 'i', 'd'];
            const allElemsWithId = document.querySelectorAll('.data');
            content = {};
            let id_from_builder;

            allElemsWithId.forEach(element => {
                const first_letter = element.id.charAt(0).toLowerCase();
                if(prefixes.includes(first_letter)){
                    content[first_letter] = element.innerHTML;
                    if(first_letter === 'i'){
                        id_from_builder = element.id;
                    }
                    else if(first_letter === 'd'){
                        content[first_letter] = element.getAttribute('date-utc');
                    }
                }
            })
            content['d'] = new Date(content['d']).toISOString().slice(0, 16); // date UTC, format: 2025-09-18T15:21
        }
        // champs à remplir des nouvelles "news"
        else if(window.Config.page === 'article' && params != null && params.get("id")[0] === 'n'){
            this.close(false);
            return;
        }
        // dans les autres cas, on doit pouvoir récupérer l'éditeur
        else{
            // l'éditeur correspond à l'article OU si page = "article" à un élément: titre, aperçu, article
            if(!this.tiny_instance){
                console.error("Éditeur non trouvé pour l'article:", this.id);
                return;
            }
            content = this.tiny_instance.getContent();
        }

        let fetch_params = {
            id: this.id,
            content: content,
            from: new URLSearchParams(window.location.search).get('from') // le "$_GET" de javascript
        };
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

    images_upload_handler = (blobInfo, progress) => new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append("file", blobInfo.blob());

        fetch('index.php?action=upload_image_tinymce', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.location){
                resolve(data.location);
            }
            else{
                reject("Erreur: Chemin d'image invalide");
            }
        })
        .catch(error => {
            reject("Erreur lors de l'upload");
        });
    });
    files_upload_handler = (blobInfo, progress) => new Promise((resolve, reject) => { // utilisation = bouton "link" (OU drag & drop, et oui)
        const formData = new FormData();
        formData.append("file", blobInfo.blob());

        fetch('index.php?action=upload_file_tinymce', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.location){
                // resolve et reject fonctionne avec Promise => type de retour standardisé et évite l'utilistion de callbacks
                resolve({
                    url: data.location,
                    fileName: blobInfo.filename(),
                });
            }
            else{
                reject("Erreur: Chemin du fichier invalide");
            }
        })
        .catch(error => {
            reject("Erreur lors de l'upload");
        });
    });

    uploadImageBase64(file, editor){
        const reader = new FileReader();

        reader.onload = function (event){ // fonction exécutée lorsque reader.readAsDataURL(file) est terminée
            const base64_target = event.target;
            if(!base64_target || !base64_target.result){
                console.error("erreur de lecture du fichier");
                return;
            }

            fetch('index.php?action=upload_image_base64', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image_base64: base64_target.result })
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
    uploadDocument(file, editor){ // utilisation = copier-coller de l'explorateur de fichiers
        const formData = new FormData();
        formData.append("file", file);
        
        fetch('index.php?action=upload_file_tinymce', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.location){
                // créer le lien <a>
                const file_name = file.name;
                const extension = file_name.split('.').pop()?.toLowerCase() || '';
                const target = extension === 'pdf' ? 'target="_blank"' : ''; // PDF = page
                editor.insertContent(`<a href="${data.location}" ${target} title="${file_name}">[${extension}] ${file_name}</a>`);
            }
            else {
                console.error("Erreur: Chemin du fichier invalide");
            }
        })
        .catch(error => {
            console.error("Erreur lors de l'upload du document :", error);
        });
    }
}



// restera ici jusqu'à ce que la gestion des balises soient faite ailleurs
function makeNewArticleButtons(id, article_id, clone, placement = 'last')
{
    let share_btn = document.querySelector(`.share.hidden`); // combinaison de deux classes
    let new_btn = document.getElementById(`new-${id}`);
    let edit_btn = document.getElementById(`edit-${id}`);
    let pos_up_btn = document.getElementById(`position_up-${id}`);
    let pos_down_btn = document.getElementById(`position_down-${id}`);
    let delete_btn = document.getElementById(`delete-${id}`);
    let cancel_btn = document.getElementById(`cancel-${id}`);
    let submit_btn = document.getElementById(`submit-${id}`);

    share_btn.classList.remove('hidden');
    new_btn.classList.add('hidden');
    edit_btn.classList.remove('hidden');
    pos_up_btn.classList.remove('hidden');
    pos_down_btn.classList.remove('hidden');
    delete_btn.classList.remove('hidden');
    //cancel_btn.classList.add('hidden');
    //submit_btn.classList.add('hidden');

    let article = document.getElementById(id);
    let article_elem_parent = findParentByTagName(article, 'article');
    
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
    
    let section_child = article_elem_parent.parentNode.querySelector('.section_child'); // renommer section_child
    
    // parentNode vise la balise section
    article_elem_parent.parentNode.replaceChild(clone.cloneNode(true), article_elem_parent); // clone du squelette pour le garder intact

    if(placement === 'first'){
        section_child.insertBefore(article_elem_parent, section_child.firstChild);
    }
    else{ // = 'last'
        section_child.appendChild(article_elem_parent);
    }
}