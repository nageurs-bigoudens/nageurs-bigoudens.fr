<?php
// src/EmailService.php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;
use Doctrine\ORM\EntityManager;
use App\Entity\Email;
use App\Entity\NodeData;

class EmailService
{
	const KEEP_EMAILS_DEFAULT = false;

	static public function send(EntityManager $entityManager, NodeData $form_data, bool $test_email, string $name = '', string $email = '', string $message = ''): bool
	{
		$mail = new PHPMailer(true); // true => exceptions
	    $mail->CharSet = 'UTF-8';

	    $smtp_host = $form_data->getData()['smtp_host'] ?? Config::$smtp_host;
	    $smtp_secure = $form_data->getData()['smtp_secure'] ?? Config::$smtp_secure;
		$smtp_username = $form_data->getData()['smtp_username'] ?? Config::$smtp_username;
		$smtp_password = $form_data->getData()['smtp_password'] ?? Config::$smtp_password;
		$email_from = $form_data->getData()['email_from'] ?? Config::$email_from; // une adresse bidon est donnée à setFrom()
		$email_from_name = $form_data->getData()['email_from_name'] ?? Config::$email_from_name; // = site web
		$email_dest = $form_data->getData()['email_dest'] ?? Config::$email_dest;
		$email_dest_name = $form_data->getData()['email_dest_name'] ?? Config::$email_dest_name; // = destinataire formulaire

	    try{
	        // Paramètres du serveur
	        $mail->isSMTP();
	        $mail->Host = $smtp_host;
	        $mail->SMTPAuth = true;
	        $mail->Port = 25;
	        
	        if($mail->SMTPAuth){
	        	$mail->Username = $smtp_username; // e-mail
	        	$mail->Password = $smtp_password;
	        	$mail->SMTPSecure = $smtp_secure; // tls (starttls) ou ssl (smtps)
	        	if($mail->SMTPSecure === 'tls'){
	        		$mail->Port = 587;
	        	}
	        	elseif($mail->SMTPSecure === 'ssl'){
	        		$mail->Port = 465;
	        	}
	        }
	        //var_dump($mail->smtpConnect());die; // test de connexion

	        // Expéditeur et destinataire
	        // $email_from, $email_from_name et $email_dest_name sont modifiables uniquement dans le config.ini pour l'instant
	        $mail->setFrom(strtolower($email_from), $email_from_name);
	        $mail->addAddress(strtolower($email_dest), $email_dest_name);

	        // Contenu
	        $mail->isHTML(true);
	        if($test_email){
	        	$mail->Subject = "TEST d'un envoi d'e-mail depuis le site web";
		    }
		    else{
		        $mail->Subject = 'Message envoyé par: ' . $name . ' (' . $email . ') depuis le site web';
		    }
		    $mail->Body = $message;
		    $mail->AltBody = $message;

	        $mail->send();

	        // copie en BDD
	        if(!$test_email && ($form_data->getData()['keep_emails'] ?? self::KEEP_EMAILS_DEFAULT)){
	        	$db_email = new Email($name, $email, Config::$email_dest, $message);
		        $entityManager->persist($db_email);
		        self::updateLastContactDate($entityManager, $email);
		        $entityManager->flush();
	        }

	        return true;
	    }
	    catch(Exception $e){
	        echo "Le message n'a pas pu être envoyé. Erreur : {$e} <br> {$mail->ErrorInfo}";
	        return false;
	    }
	}

	static public function updateLastContactDate(EntityManager $entityManager, string $sender): void
	{
		foreach($entityManager->getRepository('App\Entity\Email')->findAll() as $email){
			$email->getSenderAddress() === $sender ? $email->updateLastContactDate() : null;
		}
	}

	// peut être appelée par bin/clean_emails_cron.php
	static public function cleanEmails(EntityManager $entityManager): void
	{
		$emails = $entityManager->getRepository('App\Entity\Email')->findAll();
		foreach($emails as $email){
		    if($email->getDeletionDate() < new \DateTime()){
		        $entityManager->remove($email);
		    }
		}
		$entityManager->flush();
	}
}