class Input{
	constructor(name){
		this.name = name;
		/*const name_array = name.split('_');
		this.node = name_array[0];
		this.what = name_array[1];*/
		this.parent = document.getElementById(name);
	}
	open(){
		this.parent.querySelector('#' + this.name + '_content').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_input').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_open').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_submit').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_cancel').classList.remove('hidden');
	}
	close(){
		this.parent.querySelector('#' + this.name + '_content').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_input').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_open').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_submit').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_cancel').classList.add('hidden');
	}
	cancel(){
		this.close();
	}
}

class InputFile extends Input{
	submit(){
		const file = this.parent.querySelector('#' + this.name + '_input').files[0];
		if(!file){
			console.error("Erreur: aucun fichier sélectionné.");
			toastNotify("Erreur: aucun fichier sélectionné.");
			return;
		}
		const form_data = new FormData();
		form_data.append('file', file);

		fetch('index.php?head_foot_image=' + this.name, {
	        method: 'POST', // apparemment il faudrait utiliser PUT
	        body: form_data
	    })
	    .then(response => response.json())
	    .then(data => {
	        if(data.success){
	        	// cas particuliers
	        	if(this.name === 'head_favicon'){
	        		const link = document.querySelector('link[rel="icon"]');
	        		link.type = data.mime_type;
	        		link.href = data.location;
	        	}
	        	else if(this.name === 'header_background'){
	        		document.querySelector('header').style.backgroundImage = "url('" + data.location + "')";
	        	}

	        	this.parent.querySelector('#' + this.name + '_content').src = data.location;
				this.close();
	        }
	        else{
	            console.error("Erreur: le serveur n'a pas enregistré l'image'.");
	        }
	    })
	    .catch(error => {
	        console.error('Erreur:', error);
	    });
	}
}

class InputText extends Input{
	submit(){
		const new_text = this.parent.querySelector('#' + this.name + '_input').value;

		fetch('index.php?head_foot_text=' + this.name, {
	        method: 'POST',
	        headers: { 'Content-Type': 'application/json' },
	        body: JSON.stringify({new_text: new_text})
	    })
	    .then(response => response.json())
	    .then(data => {
	        if(data.success){
	        	this.parent.querySelector('#' + this.name + '_content').innerHTML = new_text;
				this.close();
	        }
	        else{
	            console.error("Erreur: le serveur n'a pas enregistré le nouveau texte.");
	        }
	    })
	    .catch(error => {
	        console.error('Erreur:', error);
	    });
	}
	cancel(){ // surcharge
		this.parent.querySelector('#' + this.name + '_input').value = this.parent.querySelector('#' + this.name + '_content').innerHTML;
		this.close();
	}
}