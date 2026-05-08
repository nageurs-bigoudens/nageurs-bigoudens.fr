function displayLogs(){
	const log_table = getElementOrThrow('log_table');
	
	// ouvrir, fermer, recharger
	if(log_table.innerHTML != ''){
		log_table.innerHTML = "";
		return;
	}

	let fetcher = new Fetcher({
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
	
	let fetcher = new Fetcher({
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