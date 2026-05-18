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

// notification après restauration
document.addEventListener('DOMContentLoaded', function(){
	const params = new URLSearchParams(window.location.search);
	// ça pourrait être bien de récupérer le message d'erreur de l'exception d'une autre manière (message dans la variable globale window? c'est faisable??)

	if(typeof window.error_message !== "undefined"){
		toastNotify(window.error_message);
	}

	if(params.has('read_backups_dir')){
		toastNotify("Une erreur s'est produite:<br>" + params.get('read_backups_dir'));
	}

	if(params.has('database_restauration')){
		if(params.get('database_restauration') === 'successful'){
			toastNotify("La base de données a été restaurée avec succès !!");
		}
		else{
			toastNotify("Une erreur s'est produite:<br>" + params.get('database_restauration'));
		}
	}
});
