<?php
// src/controller/ContactFormController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;

class ContactFormController
{
	static public function updateRecipient(EntityManager $entityManager, array $json): void
	{
		$email = htmlspecialchars(trim($json['email']));

		if((filter_var($email, FILTER_VALIDATE_EMAIL) // nouvel e-mail
			|| ($json['email'] === '' && !empty(Config::$email_dest))) // e-mail par défaut
			&& isset($json['hidden']) && empty($json['hidden']))
		{
			$form_data = $entityManager->find('App\Entity\NodeData', $json['id']);
			$form_data->updateData('email', $email);
			$entityManager->persist($form_data);
			$entityManager->flush();

			echo json_encode(['success' => true]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
	static public function sendVisitorEmail(EntityManager $entityManager, array $json): void
	{
		$form = new FormValidation($json, 'email');

		$error = '';
		if($form->validate()){
			// destinataire = e-mail par défaut dans config.ini OU choisi par l'utilisateur
			$form_data = $entityManager->find('App\Entity\NodeData', $json['id']);
			if($form_data === null){
				http_response_code(500);
				echo json_encode(['success' => false, 'error' => 'server_error']);
				die;
			}
			$recipient = $form_data->getData()['email'] ?? Config::$email_dest;

			if(!EmailService::send($entityManager, $recipient, true, $form->getField('name'), $form->getField('email'), $form->getField('message'))){
				$error = 'email_not_sent';
			}
		}
		else{
			$error = $form->getErrors()[0]; // la 1ère erreur sera affichée
		}

		if(empty($error)){
			echo json_encode(['success' => true]);
		}
		else{
			echo json_encode(['success' => false, 'error' => $error]);
		}
		die;
	}
	static public function sendTestEmail(EntityManager $entityManager, array $json): void
	{
		// destinataire = e-mail par défaut dans config.ini OU choisi par l'utilisateur
		$form_data = $entityManager->find('App\Entity\NodeData', $json['id']);
		if($form_data === null){
			http_response_code(500);
			echo json_encode(['success' => false, 'error' => 'server_error']);
			die;
		}
		$recipient = $form_data->getData()['email'] ?? Config::$email_dest;

		if(EmailService::send($entityManager, $recipient, false, 'nom du visiteur', 'adresse@du_visiteur.fr', "TEST d'un envoi d'e-mail depuis le site web")){
			echo json_encode(['success' => true]);
		}
		else{
			echo json_encode(['success' => false, 'error' => 'email_not_sent']);
		}
		die;
	}
}