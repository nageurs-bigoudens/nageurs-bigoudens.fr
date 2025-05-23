/* -- mode modification d'une page -- */

// même fonction que dans new_page.js
function makePageNamePath(){
	const page_name = document.getElementById("page_name");
	const page_name_path = document.getElementById("page_name_path");
	page_name_path.value = page_name.value.replace(/\W+/g, " ").trim().toLowerCase().split(' ').join('_');

	/* explication de l'expression régulière
    / = début et fin, \W+ = lettres et chiffres, g = global */
}


// partie "page"
function changePageTitle(page_id){
	const page_name = document.getElementById("page_name");

	fetch('index.php?page_edit=page_title', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
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
        headers: {
            'Content-Type': 'application/json'
        },
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
        headers: {
            'Content-Type': 'application/json'
        },
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
            console.error('Erreur à la modification de la description de la page.');
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
        headers: {
            'Content-Type': 'application/json'
        },
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
            console.error('Erreur au renommage du titre.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function switchBlocsPositions(bloc_id, direction, current_page) {
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
	
    fetch('index.php?page=' + current_page + '&bloc_edit=switch_blocs_positions', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
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

            console.error('Échec de l\'inversion');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}