<?php
// src/controller/ContactFormController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;

class ContactFormController
{
	static public function setEmailParam(EntityManager $entityManager, array $json): void
	{
		$form = new FormValidation($json, 'email_params');
		
		$error = '';
		if($form->validate()){
			$form_data = $entityManager->find('App\Entity\NodeData', $json['id']);
			$form_data->updateData($json['what_param'], trim($json['value']));
			$entityManager->persist($form_data);
			$entityManager->flush();
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

	// les deux méthodes suivantes sont "factorisables", elles ne se distinguent que par la gestion ou non du formulaire rempli par le visiteur
	static public function sendVisitorEmail(EntityManager $entityManager, array $json): void
	{
		$form = new FormValidation($json, 'email_send');

		$error = '';
		if($form->validate()){
			// destinataire = e-mail par défaut dans config.ini OU choisi par l'utilisateur
			$form_data = $entityManager->find('App\Entity\NodeData', $json['id']);
			if($form_data === null){
				http_response_code(500);
				echo json_encode(['success' => false, 'error' => 'server_error']);
				die;
			}
			
			if(!EmailService::send($entityManager, $form_data, false, $form->getField('name'), $form->getField('email'), $form->getField('message'))){
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

		if(EmailService::send($entityManager, $form_data, true, 'nom du visiteur', 'adresse@du_visiteur.fr', "TEST d'un envoi d'e-mail depuis le site web")){
			echo json_encode(['success' => true]);
		}
		else{
			echo json_encode(['success' => false, 'error' => 'email_not_sent']);
		}
		die;
	}
	static public function deleteEmail(EntityManager $entityManager, array $json): void
	{
		$email = $entityManager->find('App\Entity\Email', $json['id']);
		$entityManager->remove($email);
		$entityManager->flush();
		echo json_encode(['success' => true]);
		die;
	}
	static public function toggleSensitiveEmail(EntityManager $entityManager, array $json): void
	{
		$email = $entityManager->find('App\Entity\Email', $json['id']);
		$email->makeSensitive($json['checked']);
		$entityManager->flush();
		echo json_encode(['success' => true, 'checked' => $json['checked'], 'deletion_date' => $email->getDeletionDate()->format('d/m/Y')]);
		die;
	}
}