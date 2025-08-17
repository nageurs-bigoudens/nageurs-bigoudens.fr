<?php
// src/EmailService.php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;
use App\Entity\Email;
use Doctrine\ORM\EntityManager;

class EmailService
{
	static public function send(EntityManager $entityManager, string $recipient, bool $true_email, string $name = '', string $email = '', string $message = ''): bool
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

	        // copie en BDD
	        $db_email = new Email($email, Config::$email_dest, $message);
	        $entityManager->persist($db_email);
	        $entityManager->flush();

	        return true;
	    }
	    catch(Exception $e){
	    	return false;
	        //echo "Le message n'a pas pu être envoyé. Erreur : {$mail->ErrorInfo}";
	    }
	}
}