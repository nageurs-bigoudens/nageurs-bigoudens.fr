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
				console.error('Échec de l\'inversion');
			}
        }
        else {

            console.error('Échec de l\'inversion');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function changeDate(id_date)
{
	const real_id = 'i' + id_date.slice(1);
	const date_span = document.getElementById(id_date); // = <span>
	var old_date = date_span.innerHTML;

	// changer "le 28-12-2024 à 23h14" en "2024-12-28T23:14"
	let values = old_date.split(" à "); // 2 parties: date et heure
	values[1] = values[1].replace('h', ':');
	values[0] = values[0].replace("le ", "");
	let date = values[0].split("-"); // tableau jj-mm-aaaa
    old_date = date[2] + '-' + date[1] + "-" + date[0] + "T" + values[1];

	var label = document.createElement('label');
	label.textContent = 'Choisir une date: ';
	label.id = 'label-' + id_date;

	var input = document.createElement('input');
	input.type = 'datetime-local';
    input.value = old_date;
    input.id = 'input-' + id_date;

    var parent = date_span.parentElement;
    parent.appendChild(label)
    parent.appendChild(input);
	
	date_span.classList.add('hidden');
    document.querySelector(`#edit-${id_date}`).classList.add('hidden');
    document.querySelector(`#cancel-${id_date}`).classList.remove('hidden');
    document.querySelector(`#submit-${id_date}`).classList.remove('hidden');
}

function closeInput(id)
{
    const date_span = document.getElementById(id);
    const date_input = document.getElementById('input-' + id);
    const date_label = document.getElementById('label-' + id);

    date_span.classList.remove('hidden');
    date_input.remove();
    date_label.remove();
    document.querySelector(`#edit-${id}`).classList.remove('hidden');
    document.querySelector(`#cancel-${id}`).classList.add('hidden');
    document.querySelector(`#submit-${id}`).classList.add('hidden');
}

function submitDate(id_date)
{
	const date_input = document.getElementById('input-' + id_date);

    fetch('index.php?action=date_submit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({id: id_date, date: date_input.value})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
        	// modifier la date dans le <span> caché
        	const date_span = document.getElementById(id_date);
        	let date = new Date(date_input.value);
        	date_span.innerHTML = 
				'le ' + String(date.getDate()).padStart(2, '0') + '-' + 
				String(date.getMonth() + 1).padStart(2, '0') + '-' + 
				String(date.getFullYear()).padStart(4, '0') + ' à ' + 
				String(date.getHours()).padStart(2, '0') + 'h' + 
				String(date.getMinutes()).padStart(2, '0');
            
            closeInput(id_date);
        }
        else {
            console.error('Erreur lors de la sauvegarde de la date.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}