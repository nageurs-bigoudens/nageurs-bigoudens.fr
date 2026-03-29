<?php
// src/service/session.php

// à voir si c'est mieux avec:
//use Symfony\Component\HttpFoundation\Session\Session;

// note: session_regenerate_id(true) se trouve dans UserController::connect

use Doctrine\ORM\EntityManager;

function startSession(EntityManager $entityManager): void
{
	ini_set('session.cookie_samesite', 'Strict');
	ini_set('session.cookie_httponly', 'On');
	ini_set('session.use_strict_mode', 'On');
	ini_set('session.cookie_secure', 'On');
	session_start();
	validateSession($entityManager);
}

function validateSession(EntityManager $entityManager): void
{
	if(defined('IS_ADMIN')){
		return;
	}

	$is_admin = false;

	if(isset($_SESSION['user']['id'])){
	    $user = UserController::getUserById($_SESSION['user']['id'], $entityManager);

	    // visiteur normal
	    if(!$user){
	    	session_unset();
	    	session_destroy();
	    	header('Location: ' . new URL(['message' => 'session_invalide']));
	    	die;
	    }

	    // MAJ de la session avec CERTAINES données
	    $_SESSION['user']['username'] = $user->getLogin();
	    $_SESSION['user']['role'] = $user->getRole();

	    $is_admin = $user->getRole() === 'admin';
	}

	define('IS_ADMIN', $is_admin);

	// si on a un jour besoin d'une variable globale au lieu d'une constante
	//$GLOBALS['is_admin'] = $is_admin; // version modifiable 1
	/*function isAdmin(): bool { // version modifiable 2
	    return $_SESSION['user']['role'] ?? null === 'admin';
	}*/


	// => système de cache à ajouter pour ne pas lire la BDD à chaque fois
	//remplacer ce qui est en haut
    /*$user = $_SESSION['user'] ?? null;
	if (!$user) {
	    // visiteur
	}
	// Vérification périodique (ex: toutes les 5 minutes)
	if (time() - $user['last_check'] > 300) {
	    $user = UserController::getUserById($user['id'], $entityManager);
	    if (!$user) {
	        session_destroy();
	        header('Location: /login.php');
	        exit;
	    }
	    // cache pour ne pas avoir à lire la BDD à chaque page
	    $_SESSION['user'] = [
	        'id' => $user['id'],
	        'role' => $user['role'],
	        'username' => $user['username'],
	        'last_check' => time()
	    ];
	    $user = $_SESSION['user'];
	}
	$is_admin = ($user['role'] === 'admin');*/


	// améliorations possibles: ajouter expiration automatique + protection contre vol de session (IP / user-agent) sans casser ton app.
}

// nettoyage complet
/*function cleanSession(){
	unset($_SESSION['user']); // mémoire vive
	session_destroy(); // fichier côté serveur
	setcookie('PHPSESSID', '', time() - 86400, '/'); // cookie de session
}*/