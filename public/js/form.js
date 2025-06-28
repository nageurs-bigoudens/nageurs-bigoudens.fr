//function sendMessage(){}

function changeRecipient(){
	const email = document.getElementById('recipient').value;
	const id_form = '';

	fetch('index.php?action=recipient_email', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id_form: id_form, email: email })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success)
        {
        	toastNotify('Adresse e-mail de destination modifiée');
        }
        else {

            console.error('Erreur: echec de la modification de l\'adresse e-mail de destination');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}