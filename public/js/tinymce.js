let editors = {};

function openEditor(articleId) {
    // Récupérer et sauvegarder le contenu d'origine de l'article
    const articleContent = document.getElementById(articleId).innerHTML;
    document.getElementById(articleId).setAttribute('data-original-content', articleContent);

    tinymce.init({
        selector: `#${articleId}`,
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
                editors[articleId] = editor;
                
                // Masquer le bouton "Modifier" et afficher les boutons "Annuler" et "Soumettre"
                if(articleId != 'new')
                {
                    document.querySelector(`#edit-${articleId}`).classList.add('hidden');
                    document.querySelector(`#delete-${articleId}`).classList.add('hidden');
                    document.querySelector(`#position_up-${articleId}`).classList.add('hidden');
                    document.querySelector(`#position_down-${articleId}`).classList.add('hidden');
                }
                else{
                    document.querySelector(`#new-${articleId}`).classList.add('hidden');
                }
                document.querySelector(`#cancel-${articleId}`).classList.remove('hidden');
                document.querySelector(`#submit-${articleId}`).classList.remove('hidden');
                
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
    document.getElementById(articleId).innerHTML = articleContent;
}

function deleteArticle(articleId, page = '') {
    if (confirm('Voulez-vous vraiment supprimer cet article ?'))
    {
        // Envoyer une requête au serveur pour supprimer l'article
        fetch('index.php?action=delete_article', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: articleId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success)
            {
                if(page == 'article'){
                    // redirection vers la page d'accueil
                    window.setTimeout(function(){
                        location.href = "index.php?page=accueil";
                    }, 0);
                }
                else{
                    // Supprimer l'article du DOM
                    const articleElement = document.getElementById(articleId);
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

function closeEditor(articleId, display_old = true)
{
    // Fermer l'éditeur
    tinymce.remove(`#${articleId}`);
    delete editors[articleId];
    
    // Restaurer le contenu d'origine de l'article
    if(display_old){
        const originalContent = document.getElementById(articleId).getAttribute('data-original-content');
        document.getElementById(articleId).innerHTML = originalContent;
    }

    // Afficher le bouton "Modifier" et masquer les boutons "Annuler" et "Soumettre"
    if(articleId != 'new'){
        document.querySelector(`#edit-${articleId}`).classList.remove('hidden');
        document.querySelector(`#delete-${articleId}`).classList.remove('hidden');
        document.querySelector(`#position_up-${articleId}`).classList.remove('hidden');
        document.querySelector(`#position_down-${articleId}`).classList.remove('hidden');
    }
    else{
        document.querySelector(`#new-${articleId}`).classList.remove('hidden');
    }
    document.querySelector(`#cancel-${articleId}`).classList.add('hidden');
    document.querySelector(`#submit-${articleId}`).classList.add('hidden');
}

function submitArticle(articleId) {
    // Récupérer l'éditeur correspondant à l'article
    const editor = editors[articleId];
    if (!editor) {
        console.error('Éditeur non trouvé pour l\'article:', articleId);
        return;
    }
    
    // Récupérer le contenu de l'éditeur
    const newContent = editor.getContent();

    // Envoi AJAX au serveur
    fetch('index.php?action=editor_submit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({id: articleId, content: newContent})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer l'éditeur et mettre à jour le contenu de l'article
            closeEditor(articleId, false);
            document.getElementById(articleId).innerHTML = newContent;
        }
        else {
            alert('Erreur lors de la sauvegarde de l\'article.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}