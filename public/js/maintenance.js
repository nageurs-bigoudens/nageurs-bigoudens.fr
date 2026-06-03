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

// notification de succès ou erreur après restauration
document.addEventListener('DOMContentLoaded', function(){
	// 1/ message généré avant la redirection
	const params = new URLSearchParams(window.location.search);

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
	if(params.has('get_last_dump')){
		toastNotify(params.get('get_last_dump'));
	}
	if(params.has('get_all_media')){
		toastNotify(params.get('get_all_media'));
	}


	// 2/ message généré après la redirection, au moment de l'ouverture de la page
	if(typeof window.error_message !== "undefined"){
		toastNotify(window.error_message);
	}
});
