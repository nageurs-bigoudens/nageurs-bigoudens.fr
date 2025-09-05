/*-- page Nouvelle page --*/

// même fonction que dans modif_page.js
function makePageNamePath(){
	document.getElementById("page_name_path").value = document.getElementById("page_name").value
		.normalize("NFD")					// décompose lettres + accents: é devient "e + accent aigu"
    	.replace(/[\u0300-\u036f]/g, "")	// supprime les accents
    	.replace(/[^a-zA-Z0-9]+/g, " ")		// supprime tout ce qu'il n'est pas alphanuméric
		.trim().toLowerCase().replaceAll(" ", "_");
}


/* to do list:
=> au submit l'utilisateur est emmener sur la nouvelle page
=> interdir les doublons dans new_page_name_path
=> écrire la description avec l'éditeur?
*/