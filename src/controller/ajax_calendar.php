<?php
// src/controller/calendar.php

declare(strict_types=1);

use App\Entity\Event;

// chargement des évènements à la création du calendrier
// et au changement de dates affichées (boutons flèches mais pas changement de vue)
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_events'
    && isset($_GET['start']) && isset($_GET['end']) && empty($_POST))
{
	// bornes début et fin du calendrier affiché à l'heure locale
    // noter que la vue "planning" est similaire à la vue "semaine"
    $start = new DateTime($_GET['start']);
    $end = new DateTime($_GET['end']);
    $start->setTimezone(new DateTimeZone('UTC'));
    $end->setTimezone(new DateTimeZone('UTC'));

    // affichage format ISO à l'heure UTC
    //$date->format('Y-m-d\TH:i:s\Z');

    // on prend les évènements se finissant après le début ou commençant avant la fin de la fourchette
    $dql = 'SELECT e FROM App\Entity\Event e WHERE e.end >= :start AND e.start <= :end';
    $bulk_data = $entityManager->createQuery($dql)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getResult();

    $events = [];
    foreach($bulk_data as $one_entry){
        $event = new EventDTO($one_entry);
        $events[] = $event->toArray();
    }

    header('Content-Type: application/json');
    echo json_encode($events);
    die;
}

// actions sur le calendrier
elseif(isset($_SESSION['admin']) && $_SESSION['admin'] === true
	&& $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json')
{
	$data = file_get_contents('php://input');
	$json = json_decode($data, true);
	
	if($_GET['action'] === 'new_event'){
        $event = new Event($json['title'], $json['start'], $json['end'], $json['allDay'], $json["description"], $json['color']);
        
        $entityManager->persist($event);
        $entityManager->flush();
		
		echo json_encode(['success' => true, 'id' => $event->getId()]);
	}
	elseif($_GET['action'] === 'update_event'){
        $event = $entityManager->find('App\Entity\Event', $json['id']);
        $event->updateFromJSON($json);
        $entityManager->flush();

		echo json_encode(['success' => true]);
	}
	elseif($_GET['action'] === 'remove_event'){
        $event = $entityManager->find('App\Entity\Event', $json['id']);
        $entityManager->remove($event);
        $entityManager->flush();

		echo json_encode(['success' => true]);
	}
	else{
		echo json_encode(['success' => false]);
	}
	die;
}