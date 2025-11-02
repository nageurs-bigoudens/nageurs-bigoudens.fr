<?php
// src/installation.php

declare(strict_types=1);

use App\Entity\Page;
use App\Entity\Node;
use App\Entity\NodeData;
use App\Entity\Asset;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

function phpDependancies()
{
	$flag = false;
	//$extensions = ['pdo_mysql', 'mbstring', 'ctype', 'json', 'tokenizer', 'zip', 'dom']; // les 5 premières sont pour doctrine
	$extensions = ['pdo_mysql', 'mbstring', 'ctype', 'json', 'tokenizer'];
	foreach($extensions as $extension){
        if(!extension_loaded($extension))
        {
            echo("<p>l'extension <b>" . $extension . '</b> est manquante</p>');
            $flag = true;
        }
    }
    if(!extension_loaded('imagick') && !extension_loaded('gd')){
        echo("<p>il manque une de ces extensions au choix pour le traitement des images: <b>imagick</b> (de préférence) ou <b>gd</b>.</p>");
        $flag = true;
    }
    if($flag){
    	echo '<p>Réalisez les actions nécéssaires sur le serveur ou contactez l\'administrateur du site.<br>
    		Quand le problème sera résolu, il vous suffira de <a href="#">recharger la page<a>.</p>';
	    die;
    }
}

// inutilisée pour l'instant
function installation(): void
{
    /* -- droits des fichiers et dossiers -- */
    $droits_dossiers = 0700;
    $droits_fichiers = 0600;

    // accès interdit en HTTP
	if(!file_exists('../config/.htaccess')){
		$contenu = <<< HTACCESS
<Files "config.ini">
	Order Allow,Deny
	Deny from all
</Files>
HTACCESS;

		$fichier = fopen('../config/.htaccess', 'w');
        fputs($fichier, $contenu);
        fclose($fichier);
        chmod('../config/.htaccess', $droits_fichiers);
        //echo("danger<br>pas de .htaccess dans config<br>prévenez le responsable du site");
	    //die;
	}

	// accès limité en local (600) pour config.ini
	if(substr(sprintf('%o', fileperms('../config/config.ini')), -4) != 600){
		chmod('../config/config.ini', $droits_fichiers);
	}

	// création de data et sous-dossiers
    if(!file_exists('../data')){
        mkdir('../data/');
        chmod('../data/', $droits_dossiers);
    }
    if(!touch('../data')){
    	echo("dossier data non autorisé en écriture");
    	die;
    }
    $sous_dossiers = array('images', 'images-mini', 'videos');
    foreach ($sous_dossiers as $sous_dossier){
    	if(!file_exists('../data/' . $sous_dossier)){
	        mkdir('../data/' . $sous_dossier);
	        chmod('../data/' . $sous_dossier, $droits_dossiers);
	    }
	    if(!touch('../data/' . $sous_dossier)){
	    	echo("dossier data non autorisé en écriture");
	    	die;
	    }
    }
}

// création d'un site minimal avec une page d'accueil à la toute 1ère visite du site
// fonctiona appelée après la première requête envoyée en BDD,
// en l'occurence dans Menu parce que count($bulk_data) === 0
function fillStartingDatabase(EntityManager $entityManager){
	/* -- table page -- */
	// paramètres: name_page, end_of_path, reachable, in_menu, hidden, position, parent
	$accueil = new Page('Accueil', 'accueil', "Page d'accueil", true, true, false, 1, NULL);
	$article = new Page('Article', 'article', "", true, false, false, NULL, NULL);
	$connection = new Page('Connexion', 'connection', "Connexion", true, false, false, NULL, NULL);
	$my_account = new Page('Mon compte', 'user_edit', "Mon compte", true, false, false, NULL, NULL);
	$menu_paths = new Page("Menu et chemins", 'menu_paths', "Menu et chemins", true, false, false, NULL, NULL);
	$menu_paths->addCSS('menu');
	$menu_paths->addJS('menu');
	$new_page = new Page('Nouvelle page', 'new_page', "Nouvelle page", true, false, false, NULL, NULL);
	$new_page->addCSS('new_page');
	$new_page->addJS('new_page');
	
	/* -- table node -- */
	// paramètres: name_node, article_timestamp, attributes, position, parent, page, article
	$head = new Node('head', 1, NULL, NULL, NULL);
	$header = new Node('header', 2, NULL, NULL, NULL);
	$nav = new Node('nav', 1, $header, NULL, NULL);
	$main = new Node('main', 3, NULL, NULL, NULL);
	$footer = new Node('footer', 4, NULL, NULL, NULL);
	$breadcrumb = new Node('breadcrumb', 2, $header, NULL, NULL);
	$login = new Node('login', 1, $main, $connection, NULL);
	$user_edit = new Node('user_edit', 1, $main, $my_account, NULL);
	$bloc_edit_menu = new Node('menu', 1, $main, $menu_paths, NULL);
	$bloc_new_page = new Node('new_page', 1, $main, $new_page, NULL);

	/* -- table node_data -- */
	// paramètres: data, node, images
	$head_data = new NodeData([], $head);
	$header_data = new NodeData([], $header);
	$footer_data = new NodeData([], $footer);

	/* -- table page -- */
    $entityManager->persist($accueil);
	$entityManager->persist($article);
	$entityManager->persist($connection);
	$entityManager->persist($my_account);
	$entityManager->persist($menu_paths);
	$entityManager->persist($new_page);
	
	/* -- table node -- */
	$entityManager->persist($head);
	$entityManager->persist($header);
	$entityManager->persist($nav);
	$entityManager->persist($main);
	$entityManager->persist($footer);
	$entityManager->persist($breadcrumb);
	$entityManager->persist($login);
	$entityManager->persist($user_edit);
	$entityManager->persist($bloc_edit_menu);
	$entityManager->persist($bloc_new_page);
	
	/* -- table node_data -- */
	$entityManager->persist($head_data);
	$entityManager->persist($header_data);
	$entityManager->persist($footer_data);

    $entityManager->flush();
	header('Location: ' . new URL);
	die;
}