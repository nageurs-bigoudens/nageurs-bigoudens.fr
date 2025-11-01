// mère
class InputToggler{
	constructor(name, options = {}){
		this.name = name;
		this.parent = document.getElementById(name);

		// des ids alternatifs sont possibles
		this.content_elem = this.parent.querySelector(options.content_selector || `#${name}_content`);
		this.input_elem = this.parent.querySelector(options.input_selector || `#${name}_input`);
		this.open_elem = this.parent.querySelector(options.open_selector || `#${name}_open`);
		this.submit_elem = this.parent.querySelector(options.submit_selector || `#${name}_submit`);
		this.cancel_elem = this.parent.querySelector(options.cancel_selector || `#${name}_cancel`);

		// balises à ne pas gérer
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


// enfants
class InputText extends InputToggler{ // pour input type text ou url
	constructor(name, options = {}){
		super(name, options);
		this.fetcher = new Fetcher({
			endpoint: (options.endpoint || 'index.php?') + (options.fetch_key || 'head_foot_text=') + this.name,
			method: 'POST',
			onSuccess: (data) => this.onSuccess(data)
		});
	}
	submit(){
		this.fetcher.send({new_text: this.input_elem.value})
			.then(result => {
				toastNotify(result.success ? 'texte modifié avec succès' : "erreur: la modification des données côté serveur a échoué");
			});
	}
	onSuccess(data){
		this.content_elem.innerHTML = this.input_elem.value;
		this.close();
	}
	cancel(){
		this.input_elem.value = this.content_elem.innerHTML;
		super.cancel();
	}
}

class InputFile extends InputToggler{
	constructor(name, options = {}){
		super(name, options);
		this.fetcher = new Fetcher({
			endpoint: (options.endpoint || 'index.php?') + (options.fetch_key || 'head_foot_image=') + this.name,
			method: 'POST',
			onSuccess: (data) => this.onSuccess(data),
			onFailure: (data) => this.onFailure(data)
		});
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

		this.fetcher.send(form_data)
			.then(result => {
				toastNotify(result.success ? 'fichier téléchargé avec succès' : "erreur: la modification des données côté serveur a échoué");
			});
	}
	onSuccess(data){
		this.content_elem.src = data.location;
		this.close();
	}
	onFailure(data){
		if(data.format === 'ico'){
    		toastNotify("Format ICO mal géré par le serveur, essayez avec un PNG.");
    	}
	}
}


// petits enfants
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

class InputTextSocialNetwork extends InputText{
	open(){
		const elem_parent = this.content_elem.parentNode;
		if(elem_parent.tagName.toLowerCase() === 'a'){
			this.input_elem.value = elem_parent.href;
		}
		super.open();
	}
	onSuccess(data){
		if(this.input_elem.value){
			this.content_elem.parentNode.href = this.input_elem.value;
		}
		else{
			this.content_elem.parentNode.removeAttribute('href');
		}
		this.close(); // vu qu'on n'appelle pas super.onSuccess
	}
}