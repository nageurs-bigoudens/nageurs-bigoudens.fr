<?php
// public/index/php

/* plan d'action pour "symfonyfier" le site
A - 
    1/ de vrais contrôleurs: classes et méthodes prenant une requête en entrée et retournant une réponse
    (début de séparation contrôleurs et classes métier, exemple: ViewController/Director)
    2/ routeur structuré: méthodes GET et POST, content-type, admin
    3/ routeur amélioré: pré-routage avec méthodes HTTP: GET, HEAD, POST, PUT, PATCH, DELETE, etc
    4/ réécriture avec les classes Request et Response sans toucher les liens
B - 
    1/ passer à des chemins modernes "ciblant des ressources" genre /chemin/de/la/page
        le mode modification de page doit thérioquement être appelé comme ça: /chemin/de/la/page/modif_page
        apparemment, le from=nom_page pour les formulaires ne se fait pas...
    2/ utiliser le routeur de symfony: nécéssite que tous les contrôleurs soient des classes avec un namespace */

// http-foundation possède aussi une classe Session. intéressant!

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Session\Session;


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

// les messages d'erreur de déploiement qu'on aime
require('../src/installation.php');
phpDependancies();
//installation(); // des mkdir et chmod, chiant en dev

// $entityManager
require '../src/model/doctrine-bootstrap.php'; // isDevMode est sur "true", DSN à adapter

$request = Request::createFromGlobals();

// session
// (symfony/http-foundation pourrait nous aider avec les sessions)
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_httponly', 'On');
ini_set('session.use_strict_mode', 'On');
ini_set('session.cookie_secure', 'On');
session_start();
$_SESSION['admin'] = !isset($_SESSION['admin']) ? false : $_SESSION['admin']; // intialisation sur faux
if($_SESSION['admin'] === false || empty($_SESSION['user'])){ // OUT !!
    $_SESSION['user'] = '';
    $_SESSION['admin'] = false;
}


/* -- partie 2: routage et contrôleurs -- */

define('CURRENT_PAGE', htmlspecialchars($request->query->get('page') ?? 'accueil'));
require '../src/router.php';