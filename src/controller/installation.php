<?php
// src/controller/installation.php

declare(strict_types=1);

use App\Entity\Page;
use App\Entity\Node;
use App\Entity\NodeData;
use App\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

function installation(): void
{
	/* -- extensions PHP -- */
	$extensions = [];
	foreach($extensions as $extension){
        if(!extension_loaded($extension))
        {
            echo("l'extension " . $extension . ' est manquante<br>');
        }
    }
    if(!extension_loaded('imagick') && !extension_loaded('gd')){
        echo("il manque une de ces extensions au choix: imagick (de préférence) ou gd<br>");
    }

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
        //echo("danger<br>pas de .htaccess dans config<br>prévenez le respondable du site");
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

// création de la page d'accueil à la toute 1ère visite du site
// les informations ici ne sont pas demandées à l'utilisateur pour l'instant (on verra ça plus tard)
function makeStartPage(EntityManager $entityManager){
	/* -- table page -- */
	// paramètres: name_page, end_of_path, reachable, in_menu, parent
	$accueil = new Page('Accueil', 'accueil', true, true, NULL);
	$connection = new Page('Connexion', 'connexion', true, false, NULL);
	$article = new Page('Article', 'article', true, false, NULL);
	$edit_page = new Page("Modification d'une page", 'modif_page', true, false, NULL);
	$new_page = new Page('Nouvelle page', 'nouvelle_page', true, false, NULL);
	$edit_paths = new Page("Menu et chemins", 'menu_chemins', true, false, NULL);
	
	/* -- table node -- */
	// paramètres: name_node, article_timestamp, attributes, position, parent, page, article
	$head_accueil = new Node('head', NULL, ['css_array' => ['body', 'head', 'nav', 'main', 'foot'], 'js_array' => ['main']], 1, NULL, $accueil, NULL);
	$header = new Node('header', NULL, [], 2, NULL, NULL, NULL);
	$nav = new Node('nav', NULL, [], 1, $header, NULL, NULL);
	$main = new Node('main', NULL, [], 3, NULL, NULL, NULL);
	$footer = new Node('footer', NULL, [], 4, NULL, NULL, NULL);
	$breadcrumb = new Node('breadcrumb', NULL, [], 1, $footer, NULL, NULL);
	$head_login = new Node('head', NULL, ["stop" => true, 'css_array' => ['body', 'head', 'nav', 'main'], 'js_array' => ['main']], 1, NULL, $connection, NULL);
	$login = new Node('login', NULL, [], 1, $main, $connection, NULL);
	$head_article = new Node('head', NULL, ['css_array' => ['body', 'head', 'nav', 'main', 'foot'], 'js_array' => ['main']], 1, NULL, $article, NULL);

	/* -- table image -- */
	// paramètres: file_name, file_path, file_path_mini, mime_type, alt
	$favicon = new Image("favicon48x48.png", NULL, "assets/favicon48x48.png", "image/png", "favicon");
	$logo = new Image("logo-120x75.jpg", NULL, "assets/logo-120x75.jpg", "image/png", "head_logo");
	$facebook = new Image("facebook.svg", NULL, "assets/facebook.svg", "image/svg+xml", "facebook");
	$instagram = new Image("instagram.svg", NULL, "assets/instagram.svg", "image/svg+xml", "instagram");
	$fond_piscine = new Image("fond-piscine.jpg", "assets/fond-piscine.jpg", NULL, "images/jpg", "fond-piscine");

	/* -- table node_data -- */
	// paramètres: data, node
	$head_accueil_data = new NodeData(["description" => "Club, École de natation et Perfectionnement", "title" => "Les Nageurs Bigoudens"], $head_accueil, new ArrayCollection([$favicon]));
	$header_data = new NodeData(["description" => "Club, École de natation et Perfectionnement", "title" => "Les Nageurs Bigoudens", "facebook_link" => "https://www.facebook.com/nageursbigoudens29120", "instagram_link" => "https://www.instagram.com/nageursbigoudens/"], $header, new ArrayCollection([$logo, $facebook, $instagram, $fond_piscine]));
	$footer_data = new NodeData(["adresse" => "17, rue Raymonde Folgoas Guillou, 29120 Pont-l’Abbé", "contact_nom" => "Les Nageurs Bigoudens", "e_mail" => "nb.secretariat@orange.fr"], $footer);
	$head_login_data = new NodeData(["description" => "Connexion", "title" => "Connexion"], $head_login, new ArrayCollection([$favicon]));
	$head_article_data = new NodeData(["description" => "", "title" => ""], $head_article, new ArrayCollection([$favicon]));

    $entityManager->persist($accueil);
	$entityManager->persist($connection);
	$entityManager->persist($article);
	$entityManager->persist($edit_page);
	$entityManager->persist($new_page);
	$entityManager->persist($edit_paths);
	$entityManager->persist($head_accueil);
	$entityManager->persist($header);
	$entityManager->persist($nav);
	$entityManager->persist($main);
	$entityManager->persist($footer);
	$entityManager->persist($breadcrumb);
	$entityManager->persist($head_login);
	$entityManager->persist($login);
	$entityManager->persist($head_article);
	$entityManager->persist($favicon);
	$entityManager->persist($logo);
	$entityManager->persist($facebook);
	$entityManager->persist($instagram);
	$entityManager->persist($fond_piscine);
	$entityManager->persist($head_accueil_data);
	$entityManager->persist($header_data);
	$entityManager->persist($footer_data);
	$entityManager->persist($head_login_data);
	$entityManager->persist($head_article_data);
    $entityManager->flush();

	header('Location: ' . new URL);
	die;
}