/* -- mode modification d'une page -- */

// beaucoup de fonctions similaires
// à factoriser avec le pattern stratégie?

// même fonction que dans new_page.js
function makePageNamePath(){
    document.getElementById("page_name_path").value = document.getElementById("page_name").value
        .normalize("NFD")                   // décompose lettres + accents: é devient "e + accent aigu"
        .replace(/[\u0300-\u036f]/g, "")    // supprime les accents
        .replace(/[^a-zA-Z0-9]+/g, " ")     // supprime tout ce qu'il n'est pas alphanuméric
        .trim().toLowerCase().replaceAll(" ", "_");
}


// partie "page"
function changePageTitle(page_id){
	const page_name = document.getElementById("page_name");

	fetch('index.php?page_edit=page_title', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({title: page_name.value, page_id: page_id})
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
        	document.querySelector("title").innerHTML = data.title; // metadonnées
			document.getElementById("m_" + page_id).innerHTML = data.title; // menu
            const thesee = document.getElementById("thesee"); // fil d'Ariane
            if(thesee != null){
                thesee.innerHTML = data.title;
            }
			console.log("la page a été renommée: " + data.title);
			toastNotify("la page a été renommée: " + data.title);
        }
        else{
            console.error('Erreur au renommage de la page.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}
/*function changePageMenuPath(page_id){
	const page_name_path = document.getElementById("page_name_path");
	
	fetch('index.php?page_edit=page_menu_path', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({page_menu_path: page_name_path.value, page_id: page_id})
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
        	// oh putaing...
			let url = new URL(document.getElementById("m_" + page_id).parentElement.href); // url attrapée dans une balise <a>
			let params = new URLSearchParams(url.search); // params à droite du ?
			let path_array = params.get('page').split('/'); // chemin 'page' découpé dans un tableau
            console.log(data.page_name_path);
			path_array[path_array.length - 1] = data.page_name_path; // modif de la dernière case
			params.set('page', path_array.join('/')); // réassemblage du chemin et MAJ de params
			url.search = params.toString(); // MAJ de url
			document.getElementById("m_" + page_id).parentElement.href = url.toString(); // MAJ de la balise <a>

            //  modifier l'URL sans rafraichir en touchant à l'historique
            params.set('action', 'modif_page'); // on veut rester en mode "modif"
            url.search = params.toString();
            history.pushState({}, '', url.toString())
            
            console.log("la nouveau chemin est: " + data.page_name_path);
            toastNotify("la nouveau chemin est: " + data.page_name_path);
        }
        else{
            console.error("Erreur à la modification du chemin de la page dans l'URL.");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}*/
function changeDescription(node_data_id){
	const textarea = document.getElementById("description_textarea");

	fetch('index.php?page_edit=page_description', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({description: textarea.value, node_data_id: node_data_id})
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
        	document.querySelector('meta[name="description"]').setAttribute('content', data.description); // c'était vraiment nécéssaire?
			console.log("la nouvelle description de la page est: " + data.description);
			toastNotify("la nouvelle description de la page est: " + data.description);
        }
        else{
            console.error('Erreur côté serveur à la modification de la description de la page.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}


// partie "blocs"
function renamePageBloc(bloc_id){
	const input = document.getElementById("bloc_rename_" + bloc_id);
	const title = document.getElementById(bloc_id).querySelector("h3");

	fetch('index.php?bloc_edit=rename_page_bloc', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({bloc_title: input.value, bloc_id: bloc_id})
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
        	title.innerHTML = data.title;
        	console.log(data.title);
        	toastNotify('Le bloc a été renommé: ' + data.title);
        }
        else{
            console.error('Erreur côté serveur au renommage du titre.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function switchBlocsPositions(bloc_id, direction) {
	const current_bloc = document.getElementById(bloc_id);
	const current_bloc_edit_zone = document.getElementById("bloc_edit_" + bloc_id);
	var other_bloc;

	if(direction == 'down'){
		other_bloc = current_bloc.nextElementSibling;
	}
	else if(direction == 'up'){
		other_bloc = current_bloc.previousElementSibling;
	}

	if(other_bloc == null || other_bloc.tagName !== 'SECTION')
	{
		console.log('Inversion impossible');
		return;
	}
	const other_bloc_edit_zone = document.getElementById("bloc_edit_" + other_bloc.id);
	
    fetch('index.php?page=' + window.Config.page + '&bloc_edit=switch_blocs_positions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id1: bloc_id, id2: parseInt(other_bloc.id) })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success)
        {
        	if(direction == 'down'){
				current_bloc.parentElement.insertBefore(other_bloc, current_bloc);
				current_bloc_edit_zone.parentElement.insertBefore(other_bloc_edit_zone, current_bloc_edit_zone);
				console.log('Inversion réussie');
			}
			else if(direction == 'up'){
				other_bloc.parentElement.insertBefore(current_bloc, other_bloc);
				other_bloc_edit_zone.parentElement.insertBefore(current_bloc_edit_zone, other_bloc_edit_zone);
				console.log('Inversion réussie');
			}
        }
        else {

            console.error("Échec de l'inversion côté serveur");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function articlesOrderSelect(bloc_id){
    const articles_order_select = document.getElementById('articles_order_select_' + bloc_id).value;

    fetch('index.php?bloc_edit=change_articles_order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: bloc_id, chrono_order: articles_order_select })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            // inversion des articles
            /*const parent = document.getElementById(bloc_id).querySelector(".section_child");
            const articles = Array.from(parent.querySelectorAll("article"));
            articles.reverse().forEach(article => {
                parent.appendChild(article); // déplace dans le DOM, ne copie pas
            });*/

            // à cause de la pagination, au lieu d'inverser, on remplace les articles par les 1er dans le nouveau sens
            document.getElementById(bloc_id).querySelector('.section_child').innerHTML = '';
            fetchArticles(bloc_id);

            console.log('ordre ' + articles_order_select);
        }
        else{
            console.log("Erreur côté serveur au changement de l'ordre d'affichage");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function changePresentation(bloc_id){
    const presentation = document.getElementById('presentation_select_' + bloc_id).value;

    fetch('index.php?bloc_edit=change_presentation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: bloc_id, presentation: presentation })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            document.getElementById(bloc_id).className = presentation;
            document.getElementById(bloc_id).querySelector(".section_child").style.gridTemplateColumns = presentation === 'grid' ? 'repeat(auto-fit, minmax(' + data.cols_min_width + 'px, 1fr))' : '';
            document.getElementById('cols_min_width_edit_' + bloc_id).className = presentation === 'grid' ? '' : 'hidden';
            console.log('Changement de présentation');
        }
        else{
            console.log('Erreur côté serveur au changement de présentation');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// ressemble à changePaginationLimit
function changeColsMinWidth(bloc_id){
    const cols_min_width_input = document.getElementById('cols_min_width_select_' + bloc_id);
    
    if(cols_min_width_input.value < 150){
        cols_min_width_input.value = 150;
    }
    else if(cols_min_width_input.value > 500){
        cols_min_width_input.value = 500;
    }

    fetch('index.php?bloc_edit=change_cols_min_width', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: bloc_id, cols_min_width: cols_min_width_input.value })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            document.getElementById(bloc_id).className = 'grid';
            document.getElementById(bloc_id).querySelector(".section_child").style.gridTemplateColumns = 'repeat(auto-fit, minmax(' + data.cols_min_width + 'px, 1fr))';
            console.log('Changement de la largeur minimum en mode grille');
        }
        else{
            console.log('Erreur côté serveur au changement du nb de colonnes en mode grille');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// ressemble à changeColsMinWidth
function changePaginationLimit(bloc_id){
    const pagination_limit_input = document.getElementById('pagination_limit_' + bloc_id);
    
    if(pagination_limit_input.value > 30){
        pagination_limit_input.value = 30;
    }
    else if(pagination_limit_input.value < 0){
        pagination_limit_input.value = 0; // fait joli dans la BDD, les valeurs négatives ont le même effet que 0
    }

    fetch('index.php?bloc_edit=change_pagination_limit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: bloc_id, pagination_limit: pagination_limit_input.value })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            const parent = document.getElementById(bloc_id).querySelector('.section_child');
            const articles_list = parent.querySelectorAll('article');

            if(data.new_limit > data.old_limit || data.new_limit <= 0){ // si 0, fetchArticles va TOUT chercher!
                parent.innerHTML = ''; // pas opti, mais améliorer ça serait très compliqué
                fetchArticles(bloc_id);
            }
            else if(data.new_limit < articles_list.length){
                // retirer les articles
                const articles_array = Array.from(articles_list).slice(0, data.new_limit);
                parent.innerHTML = '';
                for(let i = 0; i < articles_array.length; i++){
                    parent.appendChild(articles_array[i]);
                }
                // remettre le bouton "Articles suivants"
                document.getElementById(bloc_id).querySelector('.fetch_articles').querySelector('button').className = '';
            }

            console.log("Changement du nombre d'articles affichés simultanément dans ce bloc");
        }
        else{
            console.log("Erreur côté serveur au changement du nb d'éléments affichés par la pagination");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}