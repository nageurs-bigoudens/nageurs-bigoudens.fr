<?php
// src/controller/ajax_calendar_admin.php

declare(strict_types=1);

use App\Entity\Event;

// actions sur le calendrier
if(isset($_SESSION['admin']) && $_SESSION['admin'] === true
	&& $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json')
{
	$data = file_get_contents('php://input');
	$json = json_decode($data, true);
	
	if($_GET['action'] === 'new_event'){
        try{
            $event = new Event($json);
        }
        catch(InvalidArgumentException $e){
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            http_response_code(400);
            die;
        }
        $entityManager->persist($event);
        $entityManager->flush();
		
		echo json_encode(['success' => true, 'id' => $event->getId()]);
	}
	elseif($_GET['action'] === 'update_event'){
        $event = $entityManager->find('App\Entity\Event', (int)$json['id']);
        try{
            $event->securedUpdateFromJSON($json);
        }
        catch(InvalidArgumentException $e){
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            http_response_code(400);
            die;
        }
        $entityManager->flush();

		echo json_encode(['success' => true]);
	}
	elseif($_GET['action'] === 'remove_event'){
        $event = $entityManager->find('App\Entity\Event', (int)$json['id']);
        $entityManager->remove($event);
        $entityManager->flush();

		echo json_encode(['success' => true]);
	}
	else{
		echo json_encode(['success' => false]);
	}
	die;
}