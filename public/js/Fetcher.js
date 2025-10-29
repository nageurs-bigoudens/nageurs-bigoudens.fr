class Fetcher{
	constructor(options = {}) {
		this.endpoint = options.endpoint || 'index.php';
		this.method = options.method || 'POST'; // normalement c'est GET par défaut
		
		// Callbacks optionnels
		this.onSuccess = options.onSuccess || null;
		this.onFailure = options.onFailure || null;
		//this.onError = options.onError || null; // Pour les erreurs réseau/parsing
	}

	send(body){
		const options = { method: this.method };

		if(this.method !== 'GET'){ // si GET, ni body ni headers
			if(body instanceof FormData){ // pas de headers
				options.body = body;
			}
			else if(body !== null && typeof body === 'object'){ // objet => json
				options.headers = { 'Content-Type': 'application/json' };
				options.body = JSON.stringify(body);
			}
			else{ // blob?
				options.body = body;
			}
		}

		return fetch(this.endpoint, options)
		.then(response => response.json())
		.then(data => this.onResponse(data))
		.catch(error => {
			console.error('Erreur:', error);
		});
	}

	onResponse(data){
		if(data.success){
			if(this.onSuccess){
				this.onSuccess(data);
			}
			return{ success: true, data };
		}
		else{
			if(this.onFailure){
				this.onFailure(data);
			}
			return { success: false, data };
		}
	}
}