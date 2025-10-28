class InputToggler{
	constructor(name, options = {}){
		this.name = name;
		this.parent = document.getElementById(name);

		// ids alternatifs optionnels
		this.content_elem = this.parent.querySelector(options.content_selector || `#${name}_content`);
		this.input_elem = this.parent.querySelector(options.input_selector || `#${name}_input`);
		this.open_elem = this.parent.querySelector(options.open_selector || `#${name}_open`);
		this.submit_elem = this.parent.querySelector(options.submit_selector || `#${name}_submit`);
		this.cancel_elem = this.parent.querySelector(options.cancel_selector || `#${name}_cancel`);

		// balises à ne pas gérer (fonctionne mais inutilisé pour l'instant)
		this.ignored_tags = {
			has_content: options.has_content !== false, // => true sauf si le paramètre vaut false
			has_input: options.has_input !== false,
			has_open_button: options.has_open_button !== false,
			has_submit_button: options.has_submit_button !== false,
			has_cancel_button: options.has_cancel_button !== false
		}
	}
	open(){
		this.toggleVisibility(true);
	}
	close(){
		this.toggleVisibility(false);
	}
	toggleVisibility(show_input = false){
		// avec && si la partie de gauche est "false", on traite la partie de droite
		// ?. est l'opérateur de chainage optionnel
		this.ignored_tags.has_content && this.content_elem.classList.toggle('hidden', show_input);
		this.ignored_tags.has_input && this.input_elem.classList.toggle('hidden', !show_input);
		this.ignored_tags.has_open_button && this.open_elem.classList.toggle('hidden', show_input);
		this.ignored_tags.has_submit_button && this.submit_elem.classList.toggle('hidden', !show_input);
		this.ignored_tags.has_cancel_button && this.cancel_elem.classList.toggle('hidden', !show_input);
	}
	cancel(){
		this.close();
	}
}

class InputText extends InputToggler{
	constructor(name, options = {}){
		super(name, options);
		this.fetch_endpoint = options.endpoint || 'index.php';
		this.fetch_key = options.fetch_key || 'head_foot_text';
	}
	submit(){
		fetch(this.fetch_endpoint + '?' + this.fetch_key + '=' + this.name, {
	        method: 'POST',
	        headers: { 'Content-Type': 'application/json' },
	        body: JSON.stringify({new_text: this.input_elem.value})
	    })
	    .then(response => response.json())
	    .then(data => {
	        if(data.success){
	        	this.content_elem.innerHTML = this.input_elem.value;
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
	cancel(){
		this.input_elem.value = this.content_elem.innerHTML;
		super.cancel();
	}
}

class InputFile extends InputToggler{
	constructor(name, options = {}){
		super(name, options);
		this.fetch_endpoint = options.endpoint || 'index.php';
		this.fetch_key = options.fetch_key || 'head_foot_image';
	}
	submit(){
		const file = this.input_elem.files[0];
		if(!file){
			console.error("Erreur: aucun fichier sélectionné.");
			toastNotify("Erreur: aucun fichier sélectionné.");
			return;
		}
		const form_data = new FormData();
		form_data.append('file', file);

		fetch(this.fetch_endpoint + '?' + this.fetch_key + '=' + this.name, {
	        method: 'POST', // apparemment il faudrait utiliser PUT
	        body: form_data
	    })
	    .then(response => response.json())
	    .then(data => {
	        if(data.success){
				this.onSuccess(data);
				this.close();
	        }
	        else{
	        	this.onFailure(data);
	            console.error(data.message);
	        }
	    })
	    .catch(error => {
	        console.error('Erreur:', error);
	    });
	}
	onSuccess(data){
		this.content_elem.src = data.location;
	}
	onFailure(data){
		if(data.format === 'ico'){
    		toastNotify("Format ICO mal géré par le serveur, essayez avec un PNG.");
    	}
	}
}

class InputFileFavicon extends InputFile{
    onSuccess(data){
        const link = document.querySelector('link[rel="icon"]');
        link.type = data.mime_type;
        link.href = data.location;
        super.onSuccess(data);
    }
}
class InputFileHeaderBackground extends InputFile{
    onSuccess(data){
        document.querySelector('header').style.backgroundImage = `url('${data.location}')`;
        super.onSuccess(data);
    }
}