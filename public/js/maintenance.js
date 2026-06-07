// js/maintenance.js

function displayLogs(){
	const log_table = getElementOrThrow('log_table');
	
	// ouvrir, fermer, recharger
	if(log_table.innerHTML != ''){
		log_table.innerHTML = "";
		return;
	}

	const fetcher = new Fetcher({
		endpoint: 'index.php?action=get_logs',
		method: 'POST',
		onSuccess: (data) => {
			log_table.innerHTML = data.view;
			console.log(log_table);
		},
		onFailure: () => {
			toastNotify("Aucune donnée disponible");
		}
	});
	fetcher.send({});
}
function cleanLogs(){
	if(!confirm('Voulez-vous vraiment supprimer cette entrée?')){
		return;
	}
	const log_table = getElementOrThrow('log_table');
	
	const fetcher = new Fetcher({
		endpoint: 'index.php?action=erase_logs',
		method: 'POST',
		onSuccess: () => {
			log_table.innerHTML = '';
			toastNotify('Les journaux de connexion ont été effacés');
		},
		onFailure: () => {
			toastNotify("L'application a rencontré une erreur, rien n'a été effacé");
		}
	});
	fetcher.send({});
}

function preventClickSpam(button){
	if(button.disabled){
        return;
    }

	button.disabled = true;
	button.style.color = 'grey';
	toastNotify('Veuillez patienter...', 1000);

	setTimeout(() => {
        button.disabled = false;
        button.style.color = '#ff1d04';
    }, 1000);
}