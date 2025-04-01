let editors = {};

function openEditor(id, page = '') {
    const real_id = 'i' + id.slice(1);

    // Récupérer et sauvegarder le contenu d'origine de l'article
    const articleContent = document.getElementById(id).innerHTML;
    document.getElementById(id).setAttribute('data-original-content', articleContent);

    tinymce.init({
        selector: `#${id}`,
        language: 'fr_FR', // télécharger des paquets de langue ici: https://www.tiny.cloud/get-tiny/language-packages/
        language_url: 'js/tinymce-langs/fr_FR.js', // ou installer tweeb/tinymce-i18n avec composer
        license_key: 'gpl',
        branding: false,
        plugins: 'lists link autolink table image media autoresize help',
        toolbar: 'undo redo newdocument print selectall styles bold italic underline strikethrough fontsizeinput forecolor backcolor fontfamily align numlist bullist outdent indent table link image media help',
        menubar: false,
        toolbar_mode: 'wrap',
        statusbar: false,
        setup: function (editor) {
            editor.on('init', function () {
                editors[id] = editor;
                
                // boutons "Modifier", "Supprimer", "déplacer vers le haut", "déplacer vers le bas", "Annuler" et "Soumettre"
                document.querySelector(`#edit-${id}`).classList.add('hidden');
                document.querySelector(`#cancel-${id}`).classList.remove('hidden');
                document.querySelector(`#submit-${id}`).classList.remove('hidden');
                document.querySelector(`#delete-${real_id}`).classList.add('hidden');
                // boutons absents page article
                if(page != 'article'){
                    document.querySelector(`#position_up-${id}`).classList.add('hidden');
                    document.querySelector(`#position_down-${id}`).classList.add('hidden');
                }
            });
        },
        // upload d'image
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("file", blobInfo.blob());

            fetch("index.php?action=upload_image", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.location) {
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
    document.getElementById(id).innerHTML = articleContent;
}

function deleteArticle(id, page = '') {
    if (confirm('Voulez-vous vraiment supprimer cet article ?'))
    {
        // Envoyer une requête au serveur pour supprimer l'article
        fetch('index.php?action=delete_article', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success)
            {
                if(page == 'article'){
                    // redirection vers la page d'accueil
                    window.setTimeout(function(){
                        location.href = "index.php?page=accueil";
                    }, 0);
                }
                else{
                    // Supprimer l'article du DOM
                    const articleElement = document.getElementById(id);
                    articleElement.parentElement.parentElement.remove(); // <article> est deux niveau au dessus
                }
            }
            else {
                alert('Erreur lors de la suppression de l\'article.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }
}

function closeEditor(id, page = '', display_old = true)
{
    const real_id = 'i' + id.slice(1);

    // Fermer l'éditeur
    tinymce.remove(`#${id}`);
    delete editors[id];
    
    // Restaurer le contenu d'origine de l'article
    if(display_old){
        const originalContent = document.getElementById(id).getAttribute('data-original-content');
        document.getElementById(id).innerHTML = originalContent;
    }

    // boutons "Modifier", "Supprimer", "déplacer vers le haut", "déplacer vers le bas", "Annuler" et "Soumettre"
    document.querySelector(`#edit-${id}`).classList.remove('hidden');
    document.querySelector(`#cancel-${id}`).classList.add('hidden');
    document.querySelector(`#submit-${id}`).classList.add('hidden');
    document.querySelector(`#delete-${real_id}`).classList.remove('hidden');
    // boutons absents page article
    if(page != 'article'){
        document.querySelector(`#position_up-${id}`).classList.remove('hidden');
        document.querySelector(`#position_down-${id}`).classList.remove('hidden');
    }    
}

function submitArticle(id, page = '') {
    // Récupérer l'éditeur correspondant à l'article
    const editor = editors[id];
    if(!editor) {
        console.error('Éditeur non trouvé pour l\'article:', id);
        return;
    }
    
    // Récupérer le contenu de l'éditeur
    const html = editor.getContent();

    // Envoi AJAX au serveur
    fetch('index.php?action=editor_submit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({id: id, content: html})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer l'éditeur et mettre à jour le contenu de l'article
            closeEditor(id, page, false);
            document.getElementById(id).innerHTML = html;
        }
        else {
            alert('Erreur lors de la sauvegarde de l\'article.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}