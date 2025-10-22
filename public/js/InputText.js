// s'en servir dans menu et chemin
class InputText{
	constructor(name){
		this.name = name;
		this.parent = document.getElementById(name);
	}
	openTextInput(){
		this.parent.querySelector('#' + this.name + '_span').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_input').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_open').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_submit').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_cancel').classList.remove('hidden');
	}
	closeTextInput(){
		this.parent.querySelector('#' + this.name + '_span').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_input').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_open').classList.remove('hidden');
		this.parent.querySelector('#' + this.name + '_submit').classList.add('hidden');
		this.parent.querySelector('#' + this.name + '_cancel').classList.add('hidden');
	}
	submitTextInput(){
		const new_text = this.parent.querySelector('#' + this.name + '_input').value;

		fetch('index.php?entire_site_edit=' + this.name, {
	        method: 'POST',
	        headers: { 'Content-Type': 'application/json' },
	        body: JSON.stringify({new_text: new_text})
	    })
	    .then(response => response.json())
	    .then(data => {
	        if(data.success){
	        	this.parent.querySelector('#' + this.name + '_span').innerHTML = new_text;
				this.closeTextInput(this.name);
	        }
	        else{
	            console.error("Erreur: le serveur n'a pas enregistré le nouveau texte.");
	        }
	    })
	    .catch(error => {
	        console.error('Erreur:', error);
	    });
	}
	cancelTextInput(){
		this.parent.querySelector('#' + this.name + '_input').value = this.parent.querySelector('#' + this.name + '_span').innerHTML;
		this.closeTextInput(this.name);
	}
}