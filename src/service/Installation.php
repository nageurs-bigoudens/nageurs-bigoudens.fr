<?php
// src/service/Installation.php

declare(strict_types=1);

use App\Entity\AppMetadata;
use App\Entity\Page;
use App\Entity\Node;
use App\Entity\NodeData;
use Doctrine\ORM\EntityManager;

class Installation
{
	static public function phpDependancies(): void
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

	static public function checkFilesAndFoldersRights(): void
	{
		// -- droits des fichiers et dossiers --
	    $droits_dossiers = 0700;
	    $droits_fichiers = 0600;

	    if(!file_exists('user_data')){
	    	// créer le dossier user_data
	    	mkdir('user_data/');
	        chmod('user_data/', $droits_dossiers);
	    	echo '<p style="color: red;">Le dossier public/user_data introuvable et le serveur n\'a pas la permission de le créer.<br>
	    	Pour faire ça bien:<br>sudo -u "serveur web" mkdir /chemin/du/site/public/user_data</p>
	    	<p>Aide: "serveur web" se nomme "www-data" sur debian et ubuntu, il s\'appelera "http" sur d\'autres distributions.</p>';
	    	die;
	    }

	    if(!file_exists('../config/config.ini')){
	    	// aide à la création du config.ini
	    	echo '<p>Le fichier config/config.ini est introuvable.</p>';
	    	echo '<p>Il doit obligatoirement contenir les codes de la base de données, le protocole http ou https (et éventuellement le port) utilisé pour créer les liens internes.<br>
	    		Un modèle est disponible, il s\'agit du fichier config/config-template.ini</p>
	    		<p>Quand vous aurez terminé votre config.ini, donnez-lui par sécurité des droits 600.</p>';
	    	die;
	    }
	    else{
	    	// droits du config.ini
			/*if(substr(sprintf('%o', fileperms('../config/config.ini')), -4) != 600){
				chmod('../config/config.ini', $droits_fichiers);
			}*/

	    	// tester les liens internes
	    	//

	    	// le test de connexion à la BDD est dans le doctrine bootstrap
	    }
	}


	/* création d'un site minimal avec une page d'accueil à la toute 1ère visite du site, ne doit surtout pas être exécutée une seconde fois */

	// protection 1 utilisé à chaque requête
	static private function isFirstRun(EntityManager $entityManager): bool
	{
	    $metadata = $entityManager->getRepository(AppMetadata::class)->find('installed');
	    return !$metadata || $metadata->getValue() !== '1';
	}

	// protection 2, qui vérifie vraiment que les tables concernées sont vides
	static private function areTablesEmpty(EntityManager $entityManager): bool
	{
		$empty = true;
		$entities = ['Page', 'Node', 'NodeData'];
		foreach($entities as $entity){
			$entity = 'App\Entity\\' . $entity; // nécéssaire quand on insère le nom avec une variable

		    if($entityManager
		        ->createQuery("SELECT e FROM $entity e")
		        ->setMaxResults(1)
		        ->getOneOrNullResult()){
		        $empty = false;
		    }
	    }

	    // cas anormal détecté, on remet en place la clé "installed"
	    if(!$empty){
	    	self::preventReinstallation($entityManager);
	    }

	    return $empty;
	}

	// met en place la protection
	static private function preventReinstallation(EntityManager $entityManager): void
	{
		$metadata = $entityManager->getRepository(AppMetadata::class)->find('installed');
		if($metadata){
			$metadata->setValue('1');
		}
		else{
			$metadata = new AppMetadata('installed', '1');
			$entityManager->persist($metadata);
		}
		$entityManager->flush();
	}

	static public function fillStartingDatabase(EntityManager $entityManager): void
	{
		if(!Installation::isFirstRun($entityManager)){
			return;
		}

		// la BDD n'est pas vierge, on ne touche à rien
		if(!self::areTablesEmpty($entityManager)){
			return;
		}

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
		$emails = new Page("Courriels", 'emails', "Consulter les courriels en base de données", true, false, false, NULL, NULL);
		$emails->addCSS('show_emails');
		$emails->addJS('form');
		
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
		$bloc_emails = new Node('show_emails', 1, $main, $emails, NULL);

		/* -- table node_data -- */
		// paramètres: data, node, images
		$head_data = new NodeData([], $head);
		$header_data = new NodeData([], $header);
		$footer_data = new NodeData([], $footer);
		$emails_data = new NodeData([], $bloc_emails);

		/* -- table page -- */
	    $entityManager->persist($accueil);
		$entityManager->persist($article);
		$entityManager->persist($connection);
		$entityManager->persist($my_account);
		$entityManager->persist($menu_paths);
		$entityManager->persist($new_page);
		$entityManager->persist($emails);
		
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
		$entityManager->persist($bloc_emails);
		
		/* -- table node_data -- */
		$entityManager->persist($head_data);
		$entityManager->persist($header_data);
		$entityManager->persist($footer_data);
		$entityManager->persist($emails_data);

	    $entityManager->flush();

	    // empêcher la réutilisation de cette fonction
	    self::preventReinstallation($entityManager);

	    // fin de l'installation
	    AppMode::set($entityManager, 'run');

	    // recharger la page?
	    //header('Location: ' . new URL);
	}
}