//function sendMessage(){}

// modif des paramètre d'envoi d'e-mail depuis l'espace admin
/*function changeRecipient(id){
	const email = document.getElementById('recipient').value;
    const hidden = document.getElementById('recipient_hidden').value;
    const warning = document.querySelector('.no_recipient_warning');

	fetch('index.php?action=recipient_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id, email: email, hidden: hidden })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            warning.classList.add('hidden');
        	toastNotify('Adresse e-mail de destination modifiée');
        }
        else{
            toastNotify('E-mail non valide');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}*/

function sendTestEmail(){
    const admin_form = document.querySelector('.admin_form');
    const test_email_success = document.querySelector('.test_email_success');
    test_email_success.innerHTML = 'Envoi en cours, veuillez patienter';
    test_email_success.style.backgroundColor = '#f0f0f0';

    fetch('index.php?action=test_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        let message;
        let color;
        if(data.success){
            message = 'E-mail de test envoyé avec succès';
            color = 'lawngreen';
        }
        else{
            message = "Erreur à l'envoi de l'e-mail";
            color = "orangered"
        }
        test_email_success.innerHTML = message;
        toastNotify(message);
        test_email_success.style.backgroundColor = color;
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function sendVisitorEmail(){
    const send_email_success = document.querySelector('.send_email_success');
    send_email_success.innerHTML = 'Envoi en cours, veuillez patienter';
    send_email_success.style.backgroundColor = 'initial';

    const email_name = document.getElementById('email_name').value;
    const email_address = document.getElementById('email_address').value;
    const email_message = document.getElementById('email_message').value;
    const email_captcha = document.getElementById('email_captcha').value;
    const email_hidden = document.getElementById('email_hidden').value;

    fetch('index.php?action=send_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            name: email_name,
            email: email_address,
            message: email_message,
            captcha: email_captcha,
            hidden: email_hidden
        })
    })
    .then(response => response.json())
    .then(data => {
        let message;
        let color;
        if(data.success){
            message = 'Votre E-mail a été envoyé!';
            color = 'lawngreen';
        }
        else{
            message = "Votre message n'a pas pu être envoyé, votre e-mail ou le captcha ne sont peut-être pas corrects";
            color = "orangered"
        }
        send_email_success.innerHTML = message;
        toastNotify(message);
        send_email_success.style.backgroundColor = color;
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}