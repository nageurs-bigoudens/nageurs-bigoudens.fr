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
    
	/*const element = document.getElementById(page_id); // div parente du bouton cliqué
	let previous_element = element.previousElementSibling;
	
	if(previous_element != null)
	{
		// si l'element précédent n'a pas de chemin relatif, donc est une URL, on vérifie le précédent également
		if(previous_element.querySelector(".path") == null){
			let test_previous = previous_element;
			while(test_previous.querySelector(".url") != null){
				console.log(test_previous);
				//if()
				test_previous = test_previous.previousElementSibling;
				if(test_previous == null){
					console.log("pas d'élément précédent");
					return;
				}
				console.log(test_previous);
			}
			previous_element = test_previous;
		}

		fetch('index.php?menu_edit=move_one_level_down', {
	        method: 'POST',
	        headers: {
	        'Content-Type': 'application/json'
	        },
	        body: JSON.stringify({ id: element.id })
	    })
	    .then(response => response.json())
	    .then(data => {
	        if(data.success)
	        {
	        	//

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
		
		// nouveau parent
		let level_div = previous_element.querySelector(".level");
		if(level_div == null){
			// créer une <div class="level">
			level_div = document.createElement("div");
			level_div.classList.add("level");
			previous_element.appendChild(level_div);
		}

		// déplacement
		level_div.appendChild(element);

		// marges
		let margin_left = parseInt(element.style.marginLeft);
		margin_left += 29;
		element.style.marginLeft = String(margin_left) + "px";

		// MAJ des chemins affichés si c'est un chemin relatif (les liens URL ne peuvent avoir d'enfants)
		const element_path = element.querySelector(".path");
		if(element_path != null){
			const previous_element_path = previous_element.querySelector(".path");
			element_path.innerHTML = previous_element_path.innerHTML + "/" + element_path.innerHTML.split("/").slice(-1);

			// même chose pour tous les enfants sauf les URL vers l'extérieur
			if(element.querySelector(".level") != null){
				element.querySelector(".level").querySelectorAll(".path").forEach( (one_elem) => {
					const parent_elem_path = one_elem.parentNode.parentNode.parentNode.querySelector(".path"); // => div de l'élém => div class level => div du parent
					const end_of_path = one_elem.innerHTML.split("/").slice(-1);
					one_elem.innerHTML = parent_elem_path.innerHTML + "/" + end_of_path[0];
				});
			}
		}

		// dernier problème à corriger: le parent est une URL vers l'extérieur
	}
	else{
		// ne rien faire
		console.log("pas d'élément précédent");
	}*/
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