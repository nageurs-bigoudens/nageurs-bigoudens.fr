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

URL::setProtocol(Config::$protocol); // utile si port autre que 80 ou 443
URL::setPort(Config::$port);
URL::setHost($_SERVER['HTTP_HOST'] . Config::$index_path);

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


/* -- partie 2: affichage d'une page ou traitement d'un POST -- */

// navigation avec les GET
define('CURRENT_PAGE', !empty($_GET['page']) ? htmlspecialchars($_GET['page']) : 'accueil');

// traitement des POST (formulaires et AJAX)
require '../src/controller/post.php';

// id des articles
$id = '';
if(!empty($_GET['id']))
{
    $id = (int)$_GET['id']; // (int) évite les injections, pas parfait d'après chatgpt
    //$id = Security::quelqueChose($_GET['id']);
}

if(isset($_GET['action']) && $_GET['action'] === 'deconnexion')
{
    disconnect($entityManager);
}
elseif(isset($_GET['action']) && $_GET['action'] === 'modif_mdp')
{
    changePassword($entityManager);
}
elseif($_SESSION['admin'] && isset($_GET['page']) && isset($_GET['action']) && $_GET['action'] === 'modif_page'
    && $_GET['page'] !== 'connexion' && $_GET['page'] !== 'article' && $_GET['page'] !== 'nouvelle_page' && $_GET['page'] !== 'menu_chemins')
{
    // les contrôles de la 2è ligne devraient utiliser un tableau
    MainBuilder::$modif_mode = true;
}

// contrôleur principal
$director = new Director($entityManager, true);
$director->makeRootNode($id);
$node = $director->getNode();

// vues
$view_builder = new ViewBuilder($node);
echo $view_builder->render(); // et voilà!