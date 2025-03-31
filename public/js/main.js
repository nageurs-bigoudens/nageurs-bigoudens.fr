function newPassword(page, id = ''){
	if(id != ''){
		id = '&id=' + id;
	}
	alert('Le mot de passe a été modifié.');
	window.setTimeout(function(){
	    location.href = "index.php?page=" + page + "&message=nouveau_mdp" + id;
	}, 0);
}

function copyInClipBoard(link){
	// une balise <input> avec des attributs
	var element = document.createElement("input");
	element.setAttribute("id", "copyMe");
	element.setAttribute("value", link);

	// placement dans la page (= le "document")
	document.body.appendChild(element);
	var cible = document.getElementById('copyMe');

	// selection comme on le ferait à la souris
	cible.select();
	// copie (= Ctrl + C)
	document.execCommand("copy");

	// nettoyage
	element.parentNode.removeChild(element);

	alert('Cette adresse a été copiée dans le presse-papier:\n\n' + link);
}

// complète les fonctions dans tinymce.js
function switchPositions(articleId, direction)
{
	const current_article = document.getElementById(articleId).parentElement.parentElement;
	var other_article = current_article;

	if(direction == 'down'){
		other_article = current_article.nextElementSibling;
	}
	else if(direction == 'up'){
		other_article = current_article.previousElementSibling;
	}
	const other_article_id = other_article.querySelector('div[id]').id;
	
    fetch('index.php?action=switch_positions', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id1: articleId, id2: other_article_id })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success)
        {
        	if(direction == 'down'){
				current_article.parentElement.insertBefore(other_article, current_article);
				console.log('Inversion réussie');
			}
			else if(direction == 'up'){
				other_article.parentElement.insertBefore(current_article, other_article);
				console.log('Inversion réussie');
			}
			else{
				console.log('Échec de l\'inversion');
			}
        }
        else {

            console.log('Échec de l\'inversion');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}