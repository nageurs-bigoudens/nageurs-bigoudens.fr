<?php
// src/model/DatabaseSettingUp.php

declare(strict_types=1);

use App\Entity\AppMetadata;
use App\Entity\Page;
use App\Entity\Node;
use App\Entity\NodeData;
use Doctrine\ORM\EntityManager;

/* création d'un site minimal avec une page d'accueil à la toute 1ère visite du site, ne doit surtout pas être exécutée une seconde fois */
class DatabaseSettingUp
{
	static public function run(EntityManager $entityManager): void
	{
		if(!self::isFirstRun($entityManager)){
			return;
		}

		// la BDD n'est pas vierge, on ne touche à rien
		if(!self::areTablesEmpty($entityManager)){
			return;
		}

		self::fillStartingDatabase($entityManager);

		// empêcher la réutilisation de cette fonction
	    self::preventReinstallation($entityManager);

	    // fin de l'installation
	    AppMode::set($entityManager, 'run');

	    // recharger la page?
	    //header('Location: ' . new URL);
	}

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

	static private function fillStartingDatabase(EntityManager $entityManager): void
	{
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
}