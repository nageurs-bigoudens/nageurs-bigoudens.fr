<?php
// src/controller/ajax_calendar_visitor.php

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