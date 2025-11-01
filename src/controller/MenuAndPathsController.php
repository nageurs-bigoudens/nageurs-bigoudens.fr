<?php
// src/controller/MenuAndPathsController.php

declare(strict_types=1);

use App\Entity\Page;
use Doctrine\ORM\EntityManager;

class MenuAndPathsController
{
    static public function newUrlMenuEntry(EntityManager $entityManager): void
    {
        Model::$menu = new Menu($entityManager);
        $previous_page = Model::$menu->findPageById((int)$_POST["location"]); // (int) à cause de declare(strict_types=1);
        $parent = $previous_page->getParent();

        $url_input = trim($_POST["url_input"]); // faire htmlspecialchars à l'affichage
        if(!filter_var($url_input, FILTER_VALIDATE_URL) || !str_starts_with($url_input, 'http')){
            header("Location: " . new URL(['page' => $_GET['from'], 'error' => 'invalide_url']));
            die;
        }

        $page = new Page(
            trim(htmlspecialchars($_POST["label_input"])),
            $url_input, '',
            true, true, false,
            $previous_page->getPosition(),
            $parent); // peut et DOIT être null si on est au 1er niveau

        // on a donné à la nouvelle entrée la même position qu'à la précédente,
        // addChild l'ajoute à la fin du tableau "children" puis on trie
        // exemple avec 2 comme position demandée: 1 2 3 4 2 devient 1 2 3 4 5 et la nouvelle entrée sera en 3è position
        if(!$parent){
            $parent = Model::$menu;
        }
        $parent->addChild($page); // true pour réindexer les positions en BDD
        $parent->reindexPositions();

        $entityManager->persist($page);
        $entityManager->flush();
        header("Location: " . new URL(['page' => $_GET['from']]));
        die;
    }

    // on pourrait utiliser FormValidation ici
    static public function editUrl(EntityManager $entityManager, array $json): void
    {
        $url_data = trim($json['input_data']); // garder htmlspecialchars pour l'affichage
        $page = $entityManager->find('App\Entity\Page', $json['id']);

        if(!$page){
            echo json_encode(['success' => false, 'message' => "id invalide"]);
        }
        elseif(!in_array($json['field'], ['url_name', 'url_content'])){
            echo json_encode(['success' => false, 'message' => "champ invalide"]);
        }
        elseif($json['field'] === 'url_content' && (!filter_var($url_data, FILTER_VALIDATE_URL) || !str_starts_with($url_data, 'http'))){
            echo json_encode(['success' => false, 'message' => "la chaîne envoyée n'est pas une URL valide"]);
        }
        else{
            if($json['field'] === 'url_name'){
                $page->setPageName($url_data);
            }
            elseif($json['field'] === 'url_content'){
                $page->setEndOfPath($url_data);
            }
            $entityManager->flush();
            echo json_encode(['success' => true, 'url_data' => $url_data]);
        }
        die;
    }

    static public function deleteUrlMenuEntry(EntityManager $entityManager): void
    {
        Model::$menu = new Menu($entityManager);
        $page = Model::$menu->findPageById((int)$_POST["delete"]);
        $parent = $page->getParent();
        if($parent == null){
            $parent = Model::$menu;
        }

        $parent->removeChild($page); // suppression de $children avant de trier
        $parent->reindexPositions();

        $entityManager->remove($page); // suppression en BDD
        $entityManager->flush();
        header("Location: " . new URL(['page' => $_GET['from']]));
        die;
    }

	static public function MoveOneLevelUp(EntityManager $entityManager, array $json): void
	{
		$id = $json['id'];
		$page = Model::$menu->findPageById((int)$id);

        $parent = $page->getParent(); // peut être null
        if($parent === null){
            // 1er niveau: ne rien faire
            echo json_encode(['success' => false]);
            die;
        }
        // BDD
        else{
            $page->setPosition($parent->getPosition() + 1); // nouvelle position

            // 2ème niveau: le parent devient $menu, puis null après tri
            if($parent->getParent() === null){
                // connexion dans les deux sens
                $page->setParent(Model::$menu); // => pour la persistance
                
                //Model::$menu->addChild($page); // => pour sortChildren
                $page->getParent()->addChild($page); // => pour sortChildren
                $page->getParent()->sortChildren(true); // positions décaléees des nouveaux petits frères
                $page->setParent(null);

                // affichage
                $page->setPagePath($page->getEndOfPath());
                $page->fillChildrenPagePath();
            }
            // 3ème niveau et plus
            else{
                $page->setParent($parent->getParent()); // nouveau parent
                $page->getParent()->addChild($page); // => pour sortChildren
                $page->getParent()->sortChildren(true); // positions décaléees des nouveaux petits frères
                $page->fillChildrenPagePath($page->getParent()->getPagePath());
            }
            $entityManager->flush();
            
            // affichage
            $parent->removeChild($page);
            $nav_builder = new NavBuilder();
	        $menu_builder = new MenuBuilder(null, false);
			echo json_encode(['success' => true, 'nav' => $nav_builder->render(), 'menu_buttons' => $menu_builder->render()]);
			die;
        }
	}

	static public function MoveOneLevelDown(EntityManager $entityManager, array $json): void
	{
		$id = $json['id'];
		$page = Model::$menu->findPageById((int)$id);

        $parent = $page->getParent(); // peut être null
        if($parent == null){
            $parent = Model::$menu;
        }

        // BDD
        $parent->sortChildren(true); // trie et réindexe par sécurité: 1, 2, 3...
        if($page->getPosition() > 1){
            foreach($parent->getChildren() as $child){
                if($child->getPosition() === $page->getPosition() - 1){
                    $page->setParent($child);
                    break;
                }
            }
            $page->setPosition(count($page->getParent()->getChildren()) + 1);
        }
        $entityManager->flush();

        // affichage
		$parent->removeChild($page);
        $page->getParent()->addChild($page);
        $page->fillChildrenPagePath($page->getParent()->getPagePath()); // variable non mappée $page_path
        $nav_builder = new NavBuilder();
		$menu_builder = new MenuBuilder(null, false);

		echo json_encode(['success' => true, 'nav' => $nav_builder->render(), 'menu_buttons' => $menu_builder->render()]);
		die;
	}

	static public function SwitchPositions(EntityManager $entityManager, array $json): void
	{
		$id1 = $json['id1'];
        $id2 = $json['id2'];

        // vérifier qu'ils ont le même parent
        $page1 = Model::$menu->findPageById((int)$id1);
        $page2 = Model::$menu->findPageById((int)$id2);

        // double le contrôle fait en JS
        if($page1->getParent() === $page2->getParent()) // comparaison stricte d'objet (même instance du parent?)
        {
        	// inversion
	        $tmp = $page1->getPosition();
	        $page1->setPosition($page2->getPosition());
	        $page2->setPosition($tmp);
	        Model::$menu->sortChildren(true); // modifie tableau children 
	        $entityManager->flush();
	        
	        // nouveau menu
	        $nav_builder = new NavBuilder();
	        echo json_encode(['success' => true, 'nav' => $nav_builder->render()]);
        }
        else{
        	echo json_encode(['success' => false]);
        }
	    die;
	}

	static public function displayInMenu(EntityManager $entityManager, array $json): void
	{
		$id = $json['id'];
		$checked = $json['checked'];

		$page = Model::$menu->findPageById((int)$id);
		if($page->isHidden() === $checked){
			$page->setHidden(!$checked);
			$entityManager->flush();

			// nouveau menu
			$nav_builder = new NavBuilder();
			echo json_encode(['success' => true, 'nav' => $nav_builder->render()]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
}