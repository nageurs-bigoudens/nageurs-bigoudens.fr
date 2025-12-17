// modif des paramètres d'e-mail: e-mail source/dest, mot de passe, serveur smtp & chiffrement tls/ssl
function setEmailParam(what_param, id){
	const value = document.getElementById(what_param + '_' + id).value;
    const hidden = document.getElementById(what_param + '_hidden_' + id).value;

	fetch('index.php?action=set_email_param', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id, what_param: what_param, value: value, hidden: hidden })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
        	toastNotify(what_param + ' a été modifié(e)');
        }
        else{
            console.error("Erreur rencontrée à l'enregistrement de cette donnée en base de données");
            toastNotify("Erreur rencontrée à l'enregistrement de cette donnée en base de données");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        toastNotify('Erreur:', error);
    });
}

function keepEmails(block_id){
    const form = document.getElementById('keep_emails_' + block_id);
    const warning = document.getElementById('form_warning_' + block_id);
    if(!form || !warning){
        return;
    }

    fetch('index.php?action=keep_emails', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: block_id,
            checked: form.checked
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            form.checked = data.checked;
            data.checked ? warning.classList.remove('hidden') : warning.classList.add('hidden');
            toastNotify(data.checked ? "Les e-mails seront conservés. Pensez au RGPD." : "Les nouveaux e-mails ne seront pas conservés.");
        }
        else{
            toastNotify("Erreur, le réglage n'a pas été enregistré par le serveur.");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function checkCase(id){
    if(document.getElementById('email_address_' + id).value.match('[A-Z]')){
        toastNotify("Votre e-mail comporte une lettre majuscule, il s'agit probablement d'une erreur.");
    }
}

function sendTestEmail(id){
    //const admin_form = document.querySelector('.admin_form');
    const test_email_success = document.querySelector('.test_email_success_' + id);
    test_email_success.innerHTML = 'Envoi en cours, veuillez patienter';
    test_email_success.style.backgroundColor = 'yellow';

    fetch('index.php?action=test_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id})
    })
    .then(response => response.json())
    .then(data => {
        let message;
        let color;
        if(data.success){
            message = 'E-mail de test envoyé avec succès';
            color = 'chartreuse';
        }
        else{
            message = "Erreur à l'envoi de l'e-mail, vérifiez la configuration du serveur";
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

function sendVisitorEmail(id){
    const email_name = document.getElementById('email_name_' + id).value;
    const email_address = document.getElementById('email_address_' + id).value;
    const email_message = document.getElementById('email_message_' + id).value;
    const email_captcha = document.getElementById('email_captcha_' + id).value;
    const email_hidden = document.getElementById('email_hidden_' + id).value;
    const send_email_success = document.querySelector('.send_email_success_' + id);

    if(email_name === '' || email_address === '' || email_message === '' || email_captcha === ''){
        toastNotify('Veuillez remplir tous les champs.');
        return;
    }
    else{
        send_email_success.innerHTML = 'Envoi en cours, veuillez patienter';
        send_email_success.style.backgroundColor = 'yellow';
    }

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
            hidden: email_hidden,
            id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        let message;
        let color;
        if(data.success){
            message = 'Votre e-mail a été envoyé!';
            color = 'lawngreen';
        }
        else{
            message = "Votre message n'a pas pu être envoyé, votre e-mail ou le captcha ne sont peut-être pas corrects";
            color = "orangered"
        }
        send_email_success.innerHTML = message;
        send_email_success.style.backgroundColor = color;
        toastNotify(message);
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function deleteEmail(id){
    const table_row = document.getElementById(id);
    if(!table_row){
        return;
    }

    if(confirm('Voulez-vous supprimer cet e-mail ?')){
        fetch('index.php?action=delete_email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                table_row.remove();
                toastNotify("E-mail supprimé");
            }
            else{}
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }
}

function toggleSensitiveEmail(id){
    const table_row = document.getElementById(id);
    const checkbox = table_row.querySelector("input[class='make_checkbox_sensitive']");
    const deletion_date = table_row.querySelector(".deletion_date");
    if(!table_row || !checkbox || !deletion_date){
        return;
    }

    fetch('index.php?action=toggle_sensitive_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            checked: checkbox.checked
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            checkbox.checked = data.checked;
            deletion_date.innerHTML = data.deletion_date;
            console.log(data.checked ? "Cet e-mail est maintenant considéré comme sensible." : "Cet e-mail n'est plus sensible.");
        }
        else{}
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}