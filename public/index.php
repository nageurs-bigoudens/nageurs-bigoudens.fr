<?php
// public/index/php

declare(strict_types=1);


/* -- partie 1: prétraitement -- */

// une nouvelle classe? taper: composer dump-autoload -o
require "../vendor/autoload.php";

// configuration possible par l'utilisateur
Config::load('../config/config.ini');

// les messages d'erreur de déploiement qu'on aime
require('../src/controller/installation.php');
phpDependancies();
//installation(); // des mkdir et chmod, chiant en dev

// $entityManager
require '../src/model/doctrine-bootstrap.php'; // isDevMode est sur "true", DSN à adapter

// générateur de liens
URL::setProtocol(Config::$protocol); // utile si port autre que 80 ou 443
URL::setPort(Config::$port);
URL::setHost($_SERVER['HTTP_HOST'] . Config::$index_path);

// session
//require('controller/Session.php');
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

// login, mot de passe et captcha
require '../src/controller/password.php';
existUsers($entityManager); // si la table user est vide, on en crée un


/* -- partie 2: contrôleurs -- */

// navigation avec des GET
define('CURRENT_PAGE', !empty($_GET['page']) ? htmlspecialchars($_GET['page']) : 'accueil');
$id = '';
if(!empty($_GET['id']))
{
    $id = htmlspecialchars($_GET['id']); // nettoyage qui n'abime pas les id du genre "n16"
}

/* -- routeur des données de formulaires et requêtes AJAX -- */
require '../src/controller/request_router.php';


/* -- affichage d'une page -- */
// mode modification d'une page activé
if($_SESSION['admin'] && isset($_GET['page']) && isset($_GET['action']) && $_GET['action'] === 'modif_page'
    && $_GET['page'] !== 'connexion' && $_GET['page'] !== 'article' && $_GET['page'] !== 'nouvelle_page' && $_GET['page'] !== 'menu_chemins')
{
    // les contrôles de la 2è ligne devraient utiliser un tableau
    MainBuilder::$modif_mode = true;
}

// contrôleur accédant au modèle
$director = new Director($entityManager, true);
$director->makeRootNode($id);
$node = $director->getNode();

// contrôleur principal des vues
$view_builder = new ViewBuilder($node);
echo $view_builder->render(); // et voilà!