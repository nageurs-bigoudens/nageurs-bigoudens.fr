// flèche gauche <=: position = position du parent + 1, parent = grand-parent, recalculer les positions
function moveOneLevelUp(page_id)
{
	const nav_zone = document.getElementById("nav_zone"); // parent de <nav>
	const menu_edit_buttons = document.getElementById("menu_edit_buttons"); // div englobant le html généré par MenuBuilder

	fetch('index.php?menu_edit=move_one_level_up', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: page_id })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success)
        {
        	console.log(data);
			// affichage
			nav_zone.innerHTML = '';
        	nav_zone.insertAdjacentHTML('afterbegin', data.nav);
        	menu_edit_buttons.innerHTML = '';
			menu_edit_buttons.insertAdjacentHTML('afterbegin', data.menu_buttons);
        }
        else {

            console.error('Échec du déplacement');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// flèche droite =>: position = nombre d'éléments de la fraterie + 1, l'élément précédent devient le parent
function moveOneLevelDown(page_id)
{
	const nav_zone = document.getElementById("nav_zone"); // parent de <nav>
	const menu_edit_buttons = document.getElementById("menu_edit_buttons"); // div englobant le html généré par MenuBuilder

	fetch('index.php?menu_edit=move_one_level_down', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: page_id })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success)
        {
        	console.log(data);
			// affichage
			nav_zone.innerHTML = '';
        	nav_zone.insertAdjacentHTML('afterbegin', data.nav);
        	menu_edit_buttons.innerHTML = '';
			menu_edit_buttons.insertAdjacentHTML('afterbegin', data.menu_buttons);
        }
        else {
            console.error('Échec du déplacement');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function switchMenuPositions(page_id, direction)
{
	const nav_zone = document.getElementById("nav_zone"); // parent de <nav>
	const clicked_menu_entry = document.getElementById(page_id); // div parente du bouton
	let other_entry = null;

	// pas bon
	if(direction == 'down'){
		other_entry = clicked_menu_entry.nextElementSibling;
	}
	else if(direction == 'up'){
		other_entry = clicked_menu_entry.previousElementSibling;
	}

	if(other_entry == null){
		console.log('Inversion impossible');
		return;
	}
	
    fetch('index.php?menu_edit=switch_positions', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id1: clicked_menu_entry.id, id2: other_entry.id })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success)
        {
        	if(direction == 'down'){
				clicked_menu_entry.parentElement.insertBefore(other_entry, clicked_menu_entry);
			}
			else if(direction == 'up'){
				other_entry.parentElement.insertBefore(clicked_menu_entry, other_entry);
			}
			else{
				console.error('Échec de l\'inversion');
			}

			// menu régénéré
        	nav_zone.innerHTML = '';
        	nav_zone.insertAdjacentHTML('afterbegin', data.nav);
        }
        else {

            console.error('Échec de l\'inversion');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function checkMenuEntry(page_id){
	const nav_zone = document.getElementById("nav_zone"); // parent de <nav>
	const clicked_menu_entry = document.getElementById(page_id); // div parente du bouton
	const checkbox = clicked_menu_entry.querySelector("input");
	let color;

	fetch('index.php?menu_edit=displayInMenu', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: clicked_menu_entry.id, checked: checkbox.checked })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success)
        {
        	color = checkbox.checked ? "#ff1d04" : "grey";
        	clicked_menu_entry.querySelector("button").style.color = color;

        	nav_zone.innerHTML = '';
        	nav_zone.insertAdjacentHTML('afterbegin', data.nav);
        }
        else {

            console.error('Échec de l\'inversion');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}


function editUrlEntry(page_id){
	const selected_div = document.getElementById(page_id);
	console.log(selected_div.id);
}

function deleteUrlEntry(page_id){
	const selected_div = document.getElementById(page_id);
	console.log(selected_div.id);
}