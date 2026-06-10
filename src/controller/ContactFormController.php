<?php
// src/controller/ContactFormController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\EmailForm;
use App\Entity\Email;
use Symfony\Component\HttpFoundation\JsonResponse;

class ContactFormController
{
	static public function keepEmails(EntityManager $entityManager, array $json): JsonResponse
	{
		$form_data = $entityManager->find(EmailForm::class, $json['id']);
		$form_data->updateData('keep_emails', $json['checked'] ? true : false);
		$entityManager->persist($form_data);
		$entityManager->flush();
		return new JsonResponse(['success' => true, 'checked' => $json['checked']]);
	}
	static public function setEmailsRetentionPeriod(EntityManager $entityManager, array $json): JsonResponse
	{
		$form_data = $entityManager->find(EmailForm::class, $json['id']);
		$form_data->updateData($json['field'], (int)$json['months']);
		$entityManager->persist($form_data);
		$entityManager->flush();
		return new JsonResponse(['success' => true, 'months' => $json['months']]);
	}
	static public function setEmailParam(EntityManager $entityManager, array $json): JsonResponse
	{
		$form = new FormValidation($json, 'email_params');
		
		if($form->validate()){
			$form_data = $entityManager->find(EmailForm::class, $json['id']);
			$form_data->updateData($json['what_param'], trim($json['value']));
			$entityManager->persist($form_data); // ??
			$entityManager->flush();
			return new JsonResponse(['success' => true]);
		}
		else{
			return new JsonResponse(['success' => false, 'error' => $form->getErrors()[0]]); // la 1ère erreur sera affichée
		}
	}

	// les deux méthodes suivantes sont "factorisables", elles ne se distinguent que par la gestion ou non du formulaire rempli par le visiteur
	static public function sendVisitorEmail(EntityManager $entityManager, array $json): JsonResponse
	{
		$form = new FormValidation($json, 'email_send');

		$error = '';
		if($form->validate()){
			// destinataire = e-mail par défaut dans config.ini OU choisi par l'utilisateur
			$form_data = $entityManager->find(EmailForm::class, $json['id']);
			if($form_data === null){
				return new JsonResponse(['success' => false, 'error' => 'server_error'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // code 500
			}
			
			if(!EmailService::send($entityManager, $form_data, false, $form->getField('name'), $form->getField('email'), $form->getField('message'))){
				$error = 'email_not_sent';
			}
		}
		else{
			$error = $form->getErrors()[0]; // la 1ère erreur sera affichée
		}

		if(empty($error)){
			return new JsonResponse(['success' => true]);
		}
		else{
			return new JsonResponse(['success' => false, 'error' => $error]);
		}
	}
	static public function sendTestEmail(EntityManager $entityManager, array $json): JsonResponse
	{
		// destinataire = e-mail par défaut dans config.ini OU choisi par l'utilisateur
		$form_data = $entityManager->find(EmailForm::class, $json['id']);
		if($form_data === null){
			return new JsonResponse(['success' => false, 'error' => 'server_error'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		if(EmailService::send($entityManager, $form_data, true, 'nom du visiteur', 'adresse@du_visiteur.fr', "TEST d'un envoi d'e-mail depuis le site web")){
			return new JsonResponse(['success' => true]);
		}
		else{
			return new JsonResponse(['success' => false, 'error' => 'email_not_sent']);
		}
	}
	static public function deleteEmail(EntityManager $entityManager, array $json): JsonResponse
	{
		$email = $entityManager->find(Email::class, $json['id']);
		$entityManager->remove($email);
		$entityManager->flush();
		return new JsonResponse(['success' => true]);
	}
	static public function toggleSensitiveEmail(EntityManager $entityManager, array $json): JsonResponse
	{
		$email = $entityManager->find(Email::class, $json['id']);
		$email->makeSensitive($json['checked']);
		$entityManager->flush();
		return new JsonResponse(['success' => true, 'checked' => $json['checked'], 'deletion_date' => $email->getDeletionDate()->format('d/m/Y')]);
	}
}