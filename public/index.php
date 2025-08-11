<?php
// public/index/php

/* plan d'action pour symfonyfier le site
1/ intégrer les classes Request et Response sans changer modifier les liens
2/ méthodes HTTP: GET, HEAD, POST, PUT, PATCH, DELETE, etc, pour un pré-routage (légères modifications des liens)
3/ passer à des chemins modernes "ciblant des ressources" genre /chemin/de/la/page
    le mode modification de page doit thérioquement être appelé comme ça: /chemin/de/la/page/modif_page
    apparemment, le from=nom_page pour les formulaires ne se fait pas...
4/ utiliser le routeur de symfony: nécéssite que tous les contrôleurs soient des classes avec un namespace
5/ http-foundation possède aussi une classe Session. intéressant!
*/

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