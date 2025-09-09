<?php
// src/controller/PageManagementController.php

declare(strict_types=1);

use App\Entity\Page;
use App\Entity\Node;
use App\Entity\NodeData;
use App\Entity\Presentation;
//use App\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class PageManagementController
{
	/* -- partie page -- */
	static public function setPageTitle(EntityManager $entityManager, array $json): void
	{
		$page = $entityManager->find('App\Entity\Page', $json['page_id']);
		$page->setPageName(htmlspecialchars($json['title']));
		$entityManager->flush();
		echo json_encode(['success' => true, 'title' => $page->getPageName()]);
		die;
	}

	static public function updatePageMenuPath(EntityManager $entityManager): void
	{
	    Director::$menu_data = new Menu($entityManager);
	    Director::$page_path = new Path();
	    $page = Director::$page_path->getLast();
	    $path = htmlspecialchars($_POST['page_menu_path']);

	    // mise en snake_case: filtre caractères non-alphanumériques, minuscule, doublons d'underscore, trim des underscores
	    $path = trim(preg_replace('/_+/', '_', strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $path))), '_');
	    $page->setEndOfPath($path);
	    foreach(Director::$menu_data->getChildren() as $child){
	        if($child->getEndOfPath() === Director::$page_path->getArray()[0]->getEndOfPath()){
	            $child->fillChildrenPagePath(); // MAJ de $page_path
	        }
	    }
	    $entityManager->flush();
	    header("Location: " . new URL(['page' => $page->getPagePath(), 'mode' => 'page_modif']));
	    die;
	}

	static public function setPageDescription(EntityManager $entityManager, array $json): void
	{
		$node_data = $entityManager->find('App\Entity\NodeData', $json['node_data_id']);
		$node_data->updateData('description', htmlspecialchars($json['description']));
		$entityManager->flush();
		echo json_encode(['success' => true, 'description' => $node_data->getData()['description']]);
		die;
	}

	static public function newPage(EntityManager $entityManager): void
	{
	    // titre et chemin
	    $director = new Director($entityManager, true);
	    //Director::$menu_data = new Menu($entityManager);
	    $previous_page = Director::$menu_data->findPageById((int)$_POST["page_location"]); // (int) à cause de declare(strict_types=1);
	    $parent = $previous_page->getParent();

	    $page = new Page(
	        trim(htmlspecialchars($_POST["page_name"])),
	        trim(htmlspecialchars($_POST["page_name_path"])),
	        true, true, false,
	        $previous_page->getPosition(),
	        $parent); // peut et DOIT être null si on est au 1er niveau

	    // on a donné à la nouvelle entrée la même position qu'à la précédente,
	    // addChild l'ajoute à la fin du tableau "children" puis on trie
	    // exemple avec 2 comme position demandée: 1 2 3 4 2 devient 1 2 3 4 5 et la nouvelle entrée sera en 3è position
	    if($parent == null){
	        $parent = Director::$menu_data;
	    }
	    $parent->addChild($page);
	    $parent->reindexPositions();

	    $page->setPagePath(ltrim($parent->getPagePath() . '/' . $page->getEndOfPath(), '/'));

	    // noeud "head"
	    $node = new Node('head', [],
	        1, // position d'un head = 1
	        null, // pas de parent
	        $page);
	    $node->useDefaultAttributes(); // fichiers CSS et JS

	    $data = new NodeData([
	        // pas de titre, il est dans $page
	        'description' => trim(htmlspecialchars($_POST["page_description"]))],
	        $node);

	    $bulk_data = $entityManager
	        ->createQuery('SELECT n FROM App\Entity\Image n WHERE n.file_name LIKE :name')
	        ->setParameter('name', '%favicon%')
	        ->getResult();
	    $data->setImages(new ArrayCollection($bulk_data));
	    
	    $entityManager->persist($page);
	    $entityManager->persist($node);
	    $entityManager->persist($data);
	    $entityManager->flush();

	    // page créée, direction la page en mode modification pour ajouter des blocs
	    header("Location: " . new URL(['page' => $page->getPagePath(), 'mode' => 'page_modif']));
	    die;
	}

	static public function deletePage(EntityManager $entityManager): void
	{
	    $page = $entityManager->find('App\Entity\Page', (int)$_POST['page_id']);
	    $nodes = $entityManager->getRepository('App\Entity\Node')->findBy(['page' => $page]);
	    $data = [];
	    foreach($nodes as $node){
	        $data[] = $entityManager->getRepository('App\Entity\NodeData')->findOneBy(['node' => $node]);
	        $entityManager->remove($node);
	    }
	    foreach($data as $one_data){
	        $entityManager->remove($one_data);
	    }
	    $entityManager->remove($page); // suppression en BDD
	    
	    $entityManager->flush();
	    header("Location: " . new URL);
	    die;
	}

	/* partie "blocs" */
	static public function addBloc(EntityManager $entityManager): void
	{
	    $director = new Director($entityManager, true); // on a besoin de page_path qui dépend de menu_data
	    $page = Director::$page_path->getLast();
	    $director->findUniqueNodeByName('main');
	    $director->findItsChildren();
	    $main = $director->getNode();
	    $position = count($main->getChildren()) + 1; // position dans la fraterie

	    if(!in_array($_POST["bloc_select"], Blocks::getNameList(), true)) // 3è param: contrôle du type
	    {
	        header("Location: " . new URL(['page' => $_GET['page'], 'error' => 'bad_bloc_type']));
	        die;
	    }

	    if($_POST["bloc_select"] === 'calendar' || $_POST["bloc_select"] === 'form'){
	        $dql = 'SELECT n FROM App\Entity\Node n WHERE n.page = :page AND n.name_node = :name'; // noeud 'head' de la page
	        $bulk_data = $entityManager
		        ->createQuery($dql)
		        ->setParameter('page', $page)
		        ->setParameter('name', 'head')
		        ->getResult();

	        if(count($bulk_data) != 1){ // 1 head par page
	            header("Location: " . new URL(['page' => $_GET['page'], 'error' => 'head_node_not_found']));
	            die;
	        }

	        $bulk_data[0]->addAttribute('css_array', $_POST["bloc_select"]);
	        if($_POST["bloc_select"] === 'form'){
	            $bulk_data[0]->addAttribute('js_array', $_POST["bloc_select"]);
	        }
	        $entityManager->persist($bulk_data[0]);
	    }

	    $block = new Node($_POST["bloc_select"], [], $position, $main, $page);
	    $data = new NodeData(
	        ['title' => trim(htmlspecialchars($_POST["bloc_title"]))],
	        $block);

	    // valeurs par défaut
	    if($_POST["bloc_select"] === 'post_block'){
	    	$data->setPresentation(Presentation::findPresentation($entityManager, 'fullwidth')); // pas génial l'utilisation de l'index dans la table
	    }
	    elseif($_POST["bloc_select"] === 'news_block'){
	    	$data->setPresentation(Presentation::findPresentation($entityManager, 'grid'));
	    }
	    elseif($_POST["bloc_select"] === 'galery'){
	    	$data->setPresentation(Presentation::findPresentation($entityManager, 'mosaic')); // mieux que carousel pour commencer
	    }
	    // else = null par défaut

	    $entityManager->persist($block);
	    $entityManager->persist($data);
	    $entityManager->flush();
	    header("Location: " . new URL(['page' => $_GET['page'], 'mode' => 'page_modif']));
	    die;
	}

	static public function deleteBloc(EntityManager $entityManager): void
	{
	    $director = new Director($entityManager, true);
	    $director->findUniqueNodeByName('main');
	    $director->findItsChildren();
	    //$director->findNodeById((int)$_POST['delete_bloc_id']);
	    $main = $director->getNode();
	    $bloc = null;
	    foreach($main->getChildren() as $child){
	        if($child->getId() === (int)$_POST['delete_bloc_id']){
	            $bloc = $child;
	            break;
	        }
	    }
	    if(!empty($bloc)){ // si $bloc est null c'est que le HTML a été modifié volontairement
	        $main->removeChild($bloc); // réindex le tableau $children au passage
	        $main->reindexPositions();
	        $entityManager->remove($bloc); // suppression en BDD
	        $entityManager->flush();
	    }

	    header("Location: " . new URL(['page' => $_GET['page'], 'mode' => 'page_modif']));
	    die;
	}
	
	static public function renameBloc(EntityManager $entityManager, array $json): void
	{
		if(isset($json['bloc_title']) && $json['bloc_title'] !== null && isset($json['bloc_id']) && is_int($json['bloc_id'])){
            $director = new Director($entityManager);
            $director->findNodeById($json['bloc_id']);

            // le titre (du JSON en BDD) est récupéré sous forme de tableau, modifié et renvoyé
            $data = $director->getNode()->getNodeData()->getData();
            $data['title'] = htmlspecialchars($json['bloc_title']);
            $director->getNode()->getNodeData()->updateData('title', htmlspecialchars($json['bloc_title']));

            $entityManager->flush();
            echo json_encode(['success' => true, 'title' => $data['title']]);
        }
        else{
			echo json_encode(['success' => false]);
		}
		die;
	}

	static public function SwitchBlocsPositions(EntityManager $entityManager, array $json): void
	{
		if(isset($json['id1']) && is_int($json['id1']) && isset($json['id2']) && is_int($json['id2']) && isset($_GET['page'])){
    		$director = new Director($entityManager, true); // true pour $director->findItsChildren();
    		$director->findUniqueNodeByName('main');
            $director->findItsChildren();
            $main = $director->getNode();
            $main->sortChildren(true); // régénère les positions avant inversion

            $bloc1 = null;
            $bloc2 = null;
            foreach($main->getChildren() as $child){
            	if($child->getId() === $json['id1']){
            		$bloc1 = $child;
            		break;
            	}
            }
            foreach($main->getChildren() as $child){
            	if($child->getId() === $json['id2']){
            		$bloc2 = $child;
            		break;
            	}
            }

	        // inversion
	        $tmp = $bloc1->getPosition();
	        $bloc1->setPosition($bloc2->getPosition());
	        $bloc2->setPosition($tmp);

    		$entityManager->flush();
            echo json_encode(['success' => true]);
    	}
    	else{
			echo json_encode(['success' => false]);
		}
		die;
	}

	static public function changePresentation(EntityManager $entityManager, array $json): void
	{
		if(isset($json['id']) && isset($json['presentation'])){
			$director = new Director($entityManager, false);
			$director->findNodeById($json['id']);

			$presentation = Presentation::findPresentation($entityManager, $json['presentation']);
			if($presentation !== null){
				$director->getNode()->getNodeData()->setPresentation($presentation);

				$entityManager->flush();

				$response_data = ['success' => true, 'presentation' => $json['presentation']];
				if($json['presentation'] === 'grid'){
					$response_data['cols_min_width'] = $director->getNode()->getNodeData()->getColsMinWidth();
				}
				echo json_encode($response_data);
			}
			else{
				echo json_encode(['success' => false]);
			}
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
	static public function changeColsMinWidth(EntityManager $entityManager, array $json): void
	{
		if(isset($json['id']) && isset($json['cols_min_width'])){
			$director = new Director($entityManager, false);
			$director->findNodeById($json['id']);
			$director->getNode()->getNodeData()->setColsMinWidth((int)$json['cols_min_width']); // attention conversion?

			$entityManager->flush();
			echo json_encode(['success' => true, 'cols_min_width' => $json['cols_min_width']]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
}