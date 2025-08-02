<?php
// /src/controller/CalendarController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\Event;

class CalendarController
{
	static public function getData(EntityManager $entityManager): void
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

	static public function newEvent(array $json, EntityManager $entityManager):void
	{
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
	static public function updateEvent(array $json, EntityManager $entityManager):void
	{
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
	static public function removeEvent(array $json, EntityManager $entityManager):void
	{
		$event = $entityManager->find('App\Entity\Event', (int)$json['id']);
	    $entityManager->remove($event);
	    $entityManager->flush();

		echo json_encode(['success' => true]);
	}
}