// étendre une classe parente avec InputFile?
class InputFile{
	constructor(name){
		this.name = name;
		this.parent = document.getElementById(name);
	}
	open(){
		this.parent.querySelector('#' + this.name + '_img').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_input').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_open').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_submit').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_cancel').classList.remove('hidden');
	}
	close(){
		this.parent.querySelector('#' + this.name + '_img').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_input').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_open').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_submit').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_cancel').classList.add('hidden');
	}
	submit(){
		const file = this.parent.querySelector('#' + this.name + '_input').files[0];
		if(!file){
			console.error("Erreur: aucun fichier sélectionné.");
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
	        	this.parent.querySelector('#' + this.name + '_img').src = data.location;
				this.close(this.name);
	        }
	        else{
	            console.error("Erreur: le serveur n'a pas enregistré l'image'.");
	        }
	    })
	    .catch(error => {
	        console.error('Erreur:', error);
	    });
	}
	cancel(){
		this.close(this.name);
	}
}