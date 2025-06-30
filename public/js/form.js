//function sendMessage(){}

function changeRecipient(id){
	const email = document.getElementById('recipient').value;
    const warning = document.querySelector('.no_recipient_warning');

	fetch('index.php?action=recipient_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id, email: email })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            warning.classList.add('hidden');
        	toastNotify('Adresse e-mail de destination modifiée');
        }
        else{
            console.error('Erreur: echec de la modification de l\'adresse e-mail de destination');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}