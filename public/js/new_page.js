/*-- page Nouvelle page --*/

// même fonction que dans modif_page.js
function makePageNamePath(){
	const page_name = document.getElementById("page_name");
	const page_name_path = document.getElementById("page_name_path");
	page_name_path.value = page_name.value.replace(/\W+/g, " ").trim().toLowerCase().split(' ').join('_');

	/* explication de l'expression régulière
    / = début et fin, \W+ = lettres et chiffres, g = global */
}


/* to do list:
=> au submit l'utilisateur est emmener sur la nouvelle page
=> interdir les doublons dans new_page_name_path
=> écrire la description avec l'éditeur?
*/