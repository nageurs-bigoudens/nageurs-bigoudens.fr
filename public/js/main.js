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