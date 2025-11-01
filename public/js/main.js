function newPassword(id = ''){
	if(id != ''){
		id = '&id=' + id;
	}
	alert('Le mot de passe a été modifié.');
	window.setTimeout(function(){
	    location.href = "index.php?page=" + window.Config.page + "&message=nouveau_mdp" + id;
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

	toastNotify('Cette adresse a été copiée dans le presse-papier:<br>' + link);
}

function toastNotify(message){
    var toast = document.getElementById('toast');
    toast.innerHTML = message;
    toast.className = 'toast show';
    setTimeout(function(){ toast.className = toast.className.replace('show', ''); }, 5000);
}

function controlURL(input){
	const url = input.value.trim();
	if(!url){
		return;
	}
	if(/^[a-z][a-z0-9+.-]*:/i.test(url) // un "protocole" (https://, ftp://, mailto:, etc)
		|| url.startsWith('/') || url.startsWith('./') || url.startsWith('../')){ // Lien local (commence par /, ./ ou ../)
		return;
	}
	input.value = 'https://' + url; // Cas par défaut
}

// exécuté à la fin du chargement de la page
document.addEventListener('DOMContentLoaded', () => {

	insertLocalDates();

	// ouvrir/fermer les sous-menus avec écran tactile
	document.querySelectorAll('.sub-menu-toggle').forEach(button => {
		button.addEventListener('click', e => {
			e.preventDefault();
			const li = button.parentElement; // <li class="drop-down">

			// fermer les autres sous-menus de même niveau
			// :scope pour pouvoir utiliser > pour restreindre aux frères directs
			li.parentElement.querySelectorAll(':scope > .drop-down, :scope > .drop-right').forEach(sibling => {
				if(sibling !== li){
					sibling.classList.remove('open'); // fermer sous-menus frères
					sibling.querySelectorAll('.drop-right').forEach(desc => {
						desc.classList.remove('open'); // fermer sous-menus neveux
					});
				}
			});

			if(!li.classList.toggle('open')){ // fermer sous-menu
				li.querySelectorAll('.drop-right').forEach(desc => {
					desc.classList.remove('open'); // fermer sous-menus enfants
				});
			}
		});
	});

	// hauteur de <nav> en fonction de celle du menu en position fixe
	const nav = document.querySelector('nav');
	const nav_zone = document.getElementById('nav_zone');
	const resize_observer = new ResizeObserver(entries => {
		for(const entry of entries){
			nav_zone.style.height = entry.contentRect.height + 'px';
		}
	});
	if(nav){
		resize_observer.observe(nav);
	}
});


function fetchArticles(bloc_id){
	const parent = document.getElementById(bloc_id);
	
	const block_type = parent.getAttribute('block-type');
	let last_article = '';
	if(block_type === 'post_block'){
		// pas parfait, suppose que les positions sont correctes
		last_article = parent.querySelectorAll('article').length - 1;
	}
	else if(block_type === 'news_block'){
		// date_time du dernier article affiché (heure UTC), date vide si bloc vide
		const news_elements = parent.querySelector('.section_child').querySelectorAll('article');
		last_article = news_elements.length !== 0 ? news_elements[news_elements.length - 1].querySelector('.local_date').getAttribute('date-utc') : '';
	}
	else{
		console.log("Erreur, le type de bloc n'est pas reconnu");
		return;
	}

	fetch('index.php?fetch=next_articles&id=' + bloc_id + '&last_article=' + last_article) // méthode GET par défaut
    .then(response => response.json())
    .then(data => {
        if(data.success){
            // insérer les articles
            parent.querySelector('.section_child').innerHTML += data.html;
            insertLocalDates();

            // cacher le bouton
            parent.querySelector('.fetch_articles').querySelector('button').className = data.truncated ? '' : 'hidden';
            
            console.log("Articles insérés dans le bloc");
        }
        else{
            console.log("Erreur côté serveur à la récupération d'articles");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}


// complète les fonctions dans tinymce.js
function switchPositions(article_id, direction)
{
	const current_article = findParentByTagName(document.getElementById(article_id), 'article'); // l'id n'est pas toujours sur la même balise
	let other_article;
	let other_article_id;

	if(direction == 'down'){
		other_article = current_article.nextElementSibling;
	}
	else if(direction == 'up'){
		other_article = current_article.previousElementSibling;
	}
	
	try{
		other_article_id = other_article.querySelector('div[id]').id;
	}
	catch(error){
		console.log('Inversion impossible');
		return;
	}
	
    fetch('index.php?action=switch_positions', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id1: article_id, id2: other_article_id })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
        	if(direction == 'down'){
				current_article.parentElement.insertBefore(other_article, current_article);
				console.log('Inversion réussie');
			}
			else if(direction == 'up'){
				other_article.parentElement.insertBefore(current_article, other_article);
				console.log('Inversion réussie');
			}
			else{
				console.error("Échec de l'inversion");
			}
        }
        else{
            console.error("Échec de l'inversion");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
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

function findParentByTagName(element, tag_name){
    while(element !== null){
        if(element.tagName === tag_name.toUpperCase()){ // tagName est en majuscules
            return element;
        }
        element = element.parentElement;
    }
    return null;
}

function checkSocialNetwork(id){
	const checkbox = document.getElementById(id).querySelector('input[type="checkbox"]');

	new Fetcher({
        endpoint: 'index.php?head_foot_social_check=' + id,
        method: 'POST',
        onSuccess: (data) => {
	        checkbox.checked = data.checked;
	        document.getElementById(id + '_content').classList.toggle('svg_fill_red', data.checked);
        	toastNotify('Le logo "' + id.split('_')[1] + '" ' + (data.checked ? 'sera' : 'ne sera pas') + ' affiché.');
	    },
	    onFailure: (data) => {
	    	console.error(data.message || "Erreur serveur");
	    }
    })
    .send({checked: checkbox.checked});
}


/* -- fonctions spécifiques à la gestion des dates -- */

function openDatetimeLocalInput(id_date)
{
	const real_id = 'i' + id_date.slice(1);
	const date_span = document.getElementById(id_date); // = <span>

	/*var old_date = date_span.innerHTML;*/
	let old_date = new Date(date_span.getAttribute('date-utc'));
	old_date = forInputTypeDatetimeLocal(old_date); // 2025-06-03T17:24
	
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

function submitDate(id_date)
{
	const date_input = document.getElementById('input-' + id_date);
	const date_span = document.getElementById(id_date);

	let date = new Date(date_input.value); // forme: 2025-02-04T00:24
	let utc_date = date.toISOString(); // forme: 2025-02-03T23:24:00.000Z

	// cas des nouvelles "news"
    const params = new URL(document.location).searchParams; // "search" = ? et paramètres, searchParams = objet avec des getters
    if(params != null && params.get("id")[0] === 'n')
	{
		// modifier la date dans le <span> caché ET l'attribut date-utc
		date_span.setAttribute('date-utc', utc_date); // heure UTC
		date_span.innerHTML = toFormatedLocalDate(utc_date); // heure locale

        closeInput(id_date);
        return;
	}
	else{
		fetch('index.php?action=date_submit', {
	        method: 'POST',
	        headers: {
	            'Content-Type': 'application/json'
	        },
	        body: JSON.stringify({id: id_date, date: utc_date.slice(0, 16)}) // heure UTC
	    })
	    .then(response => response.json())
	    .then(data => {
	        if(data.success){
	        	// modifier la date dans le <span> caché ET l'attribut date-utc
				date_span.setAttribute('date-utc', utc_date); // heure UTC
				date_span.innerHTML = toFormatedLocalDate(utc_date); // heure locale

	            closeInput(id_date);
	        }
	        else{
	            console.error('Erreur lors de la sauvegarde de la date.');
	        }
	    })
	    .catch(error => {
	        console.error('Erreur:', error);
	    });
	}
}

function insertLocalDates(){
	// détection des dates et conversion à l'heure locale
	document.querySelectorAll('.local_date').forEach(function(element){
        const utc_date = element.getAttribute('date-utc'); // forme: 2025-10-10T12:17:00+00:00
        element.innerText = toFormatedLocalDate(utc_date);
    });
}

function toFormatedLocalDate(utc_string_date){ // forme: 2025-07-17T13:54:00.000Z ou 2025-02-04T00:24
	const date = new Date(utc_string_date);

	// donne l'heure locale, forme: le 10-10-2025 à 14:17
	return 'le ' + String(date.getDate()).padStart(2, '0')
    	+ '-' + String(date.getMonth() + 1).padStart(2, '0') // mois comptés à partir de 0 !!
    	+ '-' + date.getFullYear()
    	+ ' à ' + String(date.getHours()).padStart(2, '0')
    	+ 'h' + String(date.getMinutes()).padStart(2, '0');
}
function forInputTypeDatetimeLocal(date){ // forme: 2024-12-28T23:14
    return date.getFullYear()
    	+ '-' + String(date.getMonth() + 1).padStart(2, '0') // mois comptés à partir de 0 !!
    	+ '-' + String(date.getDate()).padStart(2, '0')
    	+ 'T' + String(date.getHours()).padStart(2, '0')
    	+ ':' + String(date.getMinutes()).padStart(2, '0');
}