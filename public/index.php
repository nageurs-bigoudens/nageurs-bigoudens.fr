<?php
// public/index/php

/* idées pour "symfonyfier" le site
1/ router à la manière de symfony?
2/ utiliser les méthodes HTTP: GET, HEAD, POST, PUT, PATCH, DELETE?
3/ http-foundation possède aussi une classe Session. intéressant!
4/ passer à des chemins modernes "ciblant des ressources" genre /chemin/de/la/page
    le mode modification de page doit thérioquement être appelé comme ça: /chemin/de/la/page/modif_page
    apparemment, le from=nom_page pour les formulaires ne se fait pas...
5/ utiliser le routeur de symfony? => nécéssite que les contrôleurs soient aient tous un namespace
*/

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;


/* -- partie 1: prétraitement --
code à exécuter pour toutes requêtes */

// une nouvelle classe? taper: composer dump-autoload -o
require "../vendor/autoload.php";

// configuration possible par l'utilisateur
Config::load('../config/config.ini');

// générateur de liens
URL::setProtocol(Config::$protocol); // utile si port autre que 80 ou 443
URL::setPort(Config::$port);
URL::setHost($_SERVER['HTTP_HOST'] . Config::$index_path);

// $entityManager
require '../src/model/doctrine-bootstrap.php'; // isDevMode est sur "true", DSN à adapter

// session
require('../src/service/session.php');
startSession($entityManager);

// tests de bon fonctionnement
if(IS_ADMIN){
    Installation::phpDependancies();
    Installation::checkFilesAndFoldersRights();
}
// remplit la BDD initiale, ne fonctionne que si la BDD est vide
DatabaseSettingUp::run($entityManager);

$request = Request::createFromGlobals();


/* -- partie 2: routage et contrôleurs -- */

define('CURRENT_PAGE', htmlspecialchars($request->query->get('page') ?? ''));

//Router::dispatch($request, $entityManager);
//$response = Router::dispatch($request, $entityManager);

$router = new Router($request, $entityManager);
$response = $router->dispatch();
$response->send();

// gestion des erreurs
/*try{
    $response = $router->dispatch();
}
catch(Throwable $e){
    $response = new JsonResponse([
        'success' => false,
        'message' => 'Erreur interne'
    ], 500);
    // mieux utiliser une classe ErrorHandler qui gère les différents types d'erreur
}
$response->send();*/