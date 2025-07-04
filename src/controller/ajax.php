<?php
// src/controller/ajax.php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Entity\Email;

// mettre ça ailleurs?
function sendEmail(string $recipient, bool $true_email, string $name = '', string $email = '', string $message = ''): bool
{
	$mail = new PHPMailer(true); // true => exceptions
    $mail->CharSet = 'UTF-8';

    try{
        // Paramètres du serveur
        $mail->isSMTP();
        $mail->Host = Config::$smtp_host;
        $mail->SMTPAuth = true;
        $mail->Port = 25;
        
        if($mail->SMTPAuth){
        	$mail->Username = Config::$smtp_username; // e-mail
        	$mail->Password = Config::$smtp_password;
        	$mail->SMTPSecure = Config::$smtp_secure; // tls (starttls) ou ssl (smtps)
        	if($mail->SMTPSecure === 'tls'){
        		$mail->Port = 587;
        	}
        	elseif($mail->SMTPSecure === 'ssl'){
        		$mail->Port = 465;
        	}
        }
        //var_dump($mail->smtpConnect());die; // test de connexion

        // Expéditeur et destinataire
        $mail->setFrom(strtolower(Config::$email_from), Config::$email_from_name); // expéditeur
        $mail->addAddress(strtolower($recipient), Config::$email_dest_name); // destinataire

        // Contenu
        $mail->isHTML(true);
        if($true_email){
	        $mail->Subject = 'Message envoyé par: ' . $name . ' (' . $email . ') depuis le site web';
	        
	    }
	    else{
	        $mail->Subject = "TEST d'un envoi d'e-mail depuis le site web";
	    }
	    $mail->Body = $message;
	    $mail->AltBody = $message;

        $mail->send();
        return true;
    }
    catch(Exception $e){
    	return false;
        //echo "Le message n'a pas pu être envoyé. Erreur : {$mail->ErrorInfo}";
    }
}


// détection des requêtes envoyées avec fetch (application/json) et récupération du JSON
if($_SERVER['CONTENT_TYPE'] === 'application/json')
{
	$data = file_get_contents('php://input');
	$json = json_decode($data, true);

	if(isset($_GET['action']))
	{
		/* -- bloc Formulaire -- */
		if($_GET['action'] === 'send_email'){
			$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
			$captcha_try = isset($json['captcha']) ? Captcha::controlInput($json['captcha']) : 0;

			// contrôles des entrées
			$name = htmlspecialchars(trim($json['name']));
			$email = strtolower(htmlspecialchars(trim($json['email'])));
			$message = htmlspecialchars(trim($json['message']));

			// destinataire = e-mail par défaut dans config.ini OU choisi par l'utilisateur
			$form_data = $entityManager->find('App\Entity\NodeData', $json['id']);
			$recipient = $form_data->getData()['email'] ?? Config::$email_dest;
			
			if($captcha_try != 0 && $captcha_solution != 0 && ($captcha_try === $captcha_solution)
				&& filter_var($email, FILTER_VALIDATE_EMAIL) && isset($json['hidden']) && empty($json['hidden'])
				&& sendEmail($recipient, true, $name, $email, $message))
			{
				$db_email = new Email(Config::$email_from, Config::$email_dest, $message);
		        $entityManager->persist($db_email);
		        $entityManager->flush();
				echo json_encode(['success' => true]);
			}
			else{
				echo json_encode(['success' => false]);
			}
			die;
		}
	}
}