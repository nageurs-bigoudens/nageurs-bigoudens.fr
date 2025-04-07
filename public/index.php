<?php
// public/index/php

/* installation de composer sur un hébergement mutualisé
1. télécharger le script d'installation: 
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
2. installation: php composer-setup.php
3. supprimer l'installateur: php -r "unlink('composer-setup.php');"
4. utilisation: php composer.phar */

declare(strict_types=1);

// -- prétraitement --
// une nouvelle classe? taper: composer dump-autoload -o
require "../vendor/autoload.php";

// configuration possible par l'utilisateur
Config::load('../config/config.ini');

// les messages d'erreur de déploiement qu'on aime
require('../src/controller/installation.php');
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

// login, mot de passe et captcha
require '../src/controller/password.php';
existUsers($entityManager);

// -- navigation avec les GET --
$current_page = 'accueil';
if(!empty($_GET['page']))
{
    $current_page = htmlspecialchars($_GET['page']);
}
define('CURRENT_PAGE', $current_page);

// -- traitement des POST (formulaires et AJAX) --
require '../src/controller/post.php';

// id des articles
$id = '';
if(!empty($_GET['id']))
{
    //$id = (int)$_GET['id']; // (int) = moyen basique d'éviter les injections
    $id = Security::secureString($_GET['id']);
}

if(isset($_GET['action']) && $_GET['action'] === 'deconnexion')
{
    disconnect($entityManager);
}
elseif(isset($_GET['action']) && $_GET['action'] === 'modif_mdp')
{
    changePassword($entityManager);
}

// -- contrôleurs --
$director = new Director($entityManager, true);
$director->makeRootNode($id);
$node = $director->getNode();

// -- vues --
$view_builder = new ViewBuilder($node);
echo $view_builder->render(); // et voilà!
