<?php
// src/installation.php

declare(strict_types=1);

use App\Entity\Page;
use App\Entity\Node;
use App\Entity\NodeData;
use App\Entity\Image;
use App\Entity\Presentation;
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
function makeStartPage(EntityManager $entityManager){
	/* -- table page -- */
	// paramètres: name_page, end_of_path, reachable, in_menu, hidden, position, parent
	$accueil = new Page('Accueil', 'accueil', true, true, false, 1, NULL);
	$article = new Page('Article', 'article', true, false, false, NULL, NULL);
	$connection = new Page('Connexion', 'connection', true, false, false, NULL, NULL);
	$my_account = new Page('Mon compte', 'user_edit', true, false, false, NULL, NULL);
	$menu_paths = new Page("Menu et chemins", 'menu_chemins', true, false, false, NULL, NULL);
	//$edit_page = new Page("Modification d'une page", 'modif_page', true, false, false, NULL, NULL); // pas de page "Modification de la page"
	$new_page = new Page('Nouvelle page', 'nouvelle_page', true, false, false, NULL, NULL);
	
	/* -- table node -- */
	// paramètres: name_node, article_timestamp, attributes, position, parent, page, article
	$head_accueil = new Node('head', NULL, ['css_array' => ['body', 'head', 'nav', 'foot'], 'js_array' => ['main']], 1, NULL, $accueil, NULL);
	$head_article = new Node('head', NULL, ['css_array' => ['body', 'head', 'nav', 'foot'], 'js_array' => ['main']], 1, NULL, $article, NULL);
	$header = new Node('header', NULL, [], 2, NULL, NULL, NULL);
	$nav = new Node('nav', NULL, [], 1, $header, NULL, NULL);
	$main = new Node('main', NULL, [], 3, NULL, NULL, NULL);
	$footer = new Node('footer', NULL, [], 4, NULL, NULL, NULL);
	$breadcrumb = new Node('breadcrumb', NULL, [], 2, $header, NULL, NULL);
	$head_login = new Node('head', NULL, ["stop" => true, 'css_array' => ['body'], 'js_array' => ['main']], 1, NULL, $connection, NULL);
	$login = new Node('login', NULL, [], 1, $main, $connection, NULL);
	$head_my_account = new Node('head', NULL, ["stop" => true, 'css_array' => ['body'], 'js_array' => ['main']], 1, NULL, $my_account, NULL);
	$user_edit = new Node('user_edit', NULL, [], 1, $main, $my_account, NULL);
	$head_edit_menu = new Node('head', NULL, ['css_array' => ['body', 'head', 'nav', 'menu', 'foot'], 'js_array' => ['main', 'menu']], 1, NULL, $menu_paths, NULL);
	$bloc_edit_menu = new Node('menu', NULL, [], 1, $main, $menu_paths, NULL);
	$head_new_page = new Node('head', NULL, ['css_array' => ['body', 'head', 'nav', 'new_page', 'foot'], 'js_array' => ['main', 'new_page']], 1, NULL, $new_page, NULL);
	$bloc_new_page = new Node('new_page', NULL, [], 1, $main, $new_page, NULL);

	/* -- table presentation -- */
	$fullwidth = new Presentation('fullwidth');
	$grid = new Presentation('grid');
	$mosaic = new Presentation('mosaic');
	$carousel = new Presentation('carousel');

	/* -- table image -- */
	// paramètres: file_name, file_path, file_path_mini, mime_type, alt
	$favicon = new Image("favicon48x48.png", NULL, "assets/favicon48x48.png", "image/png", "favicon");
	$facebook = new Image("facebook.svg", NULL, "assets/facebook.svg", "image/svg+xml", "facebook");
	$instagram = new Image("instagram.svg", NULL, "assets/instagram.svg", "image/svg+xml", "instagram");
	$linkedin = new Image("linkedin.svg", NULL, "assets/linkedin.svg", "image/svg+xml", "linkedin");
	$github = new Image("github.svg", NULL, "assets/github.svg", "image/svg+xml", "github");

	/* -- table node_data -- */
	// paramètres: data, node, images
	$head_accueil_data = new NodeData(["description" => "Page d'accueil"], $head_accueil, new ArrayCollection([$favicon]));
	$head_login_data = new NodeData(["description" => "Connexion"], $head_login, new ArrayCollection([$favicon]));
	$head_my_account_data = new NodeData(["description" => "Mon compte"], $head_my_account, new ArrayCollection([$favicon]));
	$head_article_data = new NodeData(["description" => ""], $head_article, new ArrayCollection([$favicon]));
	$head_edit_menu_data = new NodeData(["description" => "Menu et chemins"], $head_edit_menu, new ArrayCollection([$favicon]));
	$head_new_page_data = new NodeData(["description" => "Nouvelle page"], $head_new_page, new ArrayCollection([$favicon]));
	$header_data = new NodeData(["title" => "Titre", "description" => "Sous-titre", "header_logo" => "assets/logo-nb-et-ffn.png", "header_background" => "assets/fond-piscine.jpg",
		"social" => ["facebook" => "https://www.facebook.com", "instagram" => "https://www.instagram.com", "linkedin" => "https://www.linkedin.com"]],
		$header, new ArrayCollection([$facebook, $instagram, $linkedin, $github]));
	$footer_data = new NodeData(["contact_nom" => "Nom", "adresse" => "adresse", "e_mail" => "e-mail", "footer_logo" => "assets/logo-nb-et-ffn.png"], $footer);

	/* -- table page -- */
    $entityManager->persist($accueil);
	$entityManager->persist($article);
	$entityManager->persist($connection);
	$entityManager->persist($my_account);
	$entityManager->persist($menu_paths);
	//$entityManager->persist($edit_page); // pas de page "Modification de la page"
	$entityManager->persist($new_page);
	
	/* -- table node -- */
	$entityManager->persist($head_accueil);
	$entityManager->persist($header);
	$entityManager->persist($nav);
	$entityManager->persist($main);
	$entityManager->persist($footer);
	$entityManager->persist($breadcrumb);
	$entityManager->persist($head_login);
	$entityManager->persist($login);
	$entityManager->persist($head_my_account);
	$entityManager->persist($user_edit);
	$entityManager->persist($head_article);
	$entityManager->persist($head_edit_menu);
	$entityManager->persist($bloc_edit_menu);
	$entityManager->persist($head_new_page);
	$entityManager->persist($bloc_new_page);

	/* -- table presentation -- */
	$entityManager->persist($fullwidth);
	$entityManager->persist($grid);
	$entityManager->persist($mosaic);
	$entityManager->persist($carousel);
	
	/* -- table image -- */
	$entityManager->persist($favicon);
	$entityManager->persist($facebook);
	$entityManager->persist($instagram);
	$entityManager->persist($linkedin);
	$entityManager->persist($github);
	
	/* -- table node_data -- */
	$entityManager->persist($head_accueil_data);
	$entityManager->persist($header_data);
	$entityManager->persist($footer_data);
	$entityManager->persist($head_login_data);
	$entityManager->persist($head_my_account_data);
	$entityManager->persist($head_article_data);
	$entityManager->persist($head_edit_menu_data);
	$entityManager->persist($head_new_page_data);

    $entityManager->flush();
	header('Location: ' . new URL);
	die;
}