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
	    Model::$menu = new Menu($entityManager);
	    Model::$page_path = new Path();
	    $page = Model::$page_path->getLast();
	    $path = htmlspecialchars($_POST['page_menu_path']);

	    // mise en snake_case: filtre caractères non-alphanumériques, minuscule, doublons d'underscore, trim des underscores
	    $path = trim(preg_replace('/_+/', '_', strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $path))), '_');
	    $page->setEndOfPath($path);
	    foreach(Model::$menu->getChildren() as $child){
	        if($child->getEndOfPath() === Model::$page_path->getArray()[0]->getEndOfPath()){
	            $child->fillChildrenPagePath(); // MAJ de $page_path
	        }
	    }
	    $entityManager->flush();
	    header("Location: " . new URL(['page' => $page->getPagePath(), 'mode' => 'page_modif']));
	    die;
	}

	static public function setPageDescription(EntityManager $entityManager, array $json): void
	{
		$page = $entityManager->find('App\Entity\Page', $json['page_id']);
		$page->setDescription(htmlspecialchars($json['description']));
		$entityManager->flush();
		echo json_encode(['success' => true, 'description' => $page->getDescription()]);
		die;
	}

	static public function newPage(EntityManager $entityManager, array $post): void
	{
	    // titre et chemin
	    Model::$menu = new Menu($entityManager);
	    $previous_page = Model::$menu->findPageById((int)$post["page_location"]); // (int) à cause de declare(strict_types=1);
	    $parent = $previous_page->getParent();

	    $page = new Page(
	        trim(htmlspecialchars($post["page_name"])),
	        trim(htmlspecialchars($post["page_name_path"])),
	        trim(htmlspecialchars($post["page_description"])),
	        true, true, false,
	        $previous_page->getPosition(),
	        $parent); // peut et DOIT être null si on est au 1er niveau

	    // on a donné à la nouvelle entrée la même position qu'à la précédente,
	    // addChild l'ajoute à la fin du tableau "children" puis on trie
	    // exemple avec 2 comme position demandée: 1 2 3 4 2 devient 1 2 3 4 5 et la nouvelle entrée sera en 3è position
	    if($parent == null){
	        $parent = Model::$menu;
	    }
	    $parent->addChild($page);
	    $parent->reindexPositions();

	    $page->setPagePath(ltrim($parent->getPagePath() . '/' . $page->getEndOfPath(), '/'));

	    $entityManager->persist($page);
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
	    $model = new Model($entityManager);
	    $model->makeMenuAndPaths(); // on a besoin de page_path qui dépend de menu
	    $page = Model::$page_path->getLast();
	    $model->findUniqueNodeByName('main');
	    $model->findItsChildren();
	    $main = $model->getNode();
	    $position = count($main->getChildren()) + 1; // position dans la fraterie

	    if(!in_array($_POST["bloc_select"], array_keys(Blocks::$blocks), true)) // 3è param: contrôle du type
	    {
	        header("Location: " . new URL(['page' => $_GET['page'], 'error' => 'bad_bloc_type']));
	        die;
	    }

	    if(in_array($_POST["bloc_select"], ['calendar', 'form'])){
	        $page->addCSS($_POST["bloc_select"]);
	        if($_POST["bloc_select"] === 'form'){
	            $page->addJS($_POST["bloc_select"]);
	        }
	        $entityManager->persist($page);
	    }

	    $block = new Node($_POST["bloc_select"], $position, $main, $page);
	    $data = new NodeData(['title' => trim(htmlspecialchars($_POST["bloc_title"]))], $block);

	    // valeurs par défaut
	    if($_POST["bloc_select"] === 'post_block'){
	    	$data->setPresentation('fullwidth');
	    }
	    elseif($_POST["bloc_select"] === 'news_block'){
	    	$data->setPresentation('grid');
	    }
	    elseif($_POST["bloc_select"] === 'galery'){
	    	$data->setPresentation('mosaic'); // un jour on mettra carousel
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
	    $model = new Model($entityManager);
	    $model->makeMenuAndPaths();
	    $model->findUniqueNodeByName('main');
	    $model->findItsChildren();
	    $main = $model->getNode();

	    $block = null;
	    $type = '';
	    $nb_same_type = 0;
	    foreach($main->getChildren() as $child){
	        if($child->getId() === (int)$_POST['delete_bloc_id']){
	            $block = $child;
	            $type = $block->getName();
	        }
	        if($child->getName() === $type){
	        	$nb_same_type++;
	        }
	    }

	    // nettoyage fichiers CSS et JS si on retire le derner bloc de ce type
	    if($nb_same_type === 1 && in_array($block->getName(), ['calendar', 'form'])){
	    	$page = $block->getPage();
	    	$page->removeCSS($block->getName());
	    	if($block->getName() === 'form'){
	    		$page->removeJS($block->getName());
	    	}
	    }
	    
	    if(!empty($block)){ // si $block est null c'est que le HTML a été modifié volontairement
	        $main->removeChild($block); // réindex le tableau $children au passage
	        $main->reindexPositions();
	        if(isset($page)){
	        	$entityManager->persist($page);
	        }
	        $entityManager->remove($block);
	        $entityManager->flush();
	    }

	    header("Location: " . new URL(['page' => $_GET['page'], 'mode' => 'page_modif']));
	    die;
	}
	
	static public function renameBloc(EntityManager $entityManager, array $json): void
	{
		if(isset($json['bloc_title']) && $json['bloc_title'] !== null && isset($json['bloc_id']) && is_int($json['bloc_id'])){
            $model = new Model($entityManager);
            $model->findNodeById($json['bloc_id']);

            // le titre (du JSON en BDD) est récupéré sous forme de tableau, modifié et renvoyé
            $data = $model->getNode()->getNodeData()->getData();
            $data['title'] = htmlspecialchars($json['bloc_title']);
            $model->getNode()->getNodeData()->updateData('title', htmlspecialchars($json['bloc_title']));

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
    		$model = new Model($entityManager);
    		$model->makeMenuAndPaths(); // true pour $model->findItsChildren();
    		$model->findUniqueNodeByName('main');
            $model->findItsChildren();
            $main = $model->getNode();
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

	static public function changeArticlesOrder(EntityManager $entityManager, array $json): void
	{
		if(isset($json['id']) && isset($json['chrono_order'])){
			$model = new Model($entityManager);
			$model->findNodeById($json['id']);

			if($json['chrono_order'] === 'chrono'){
				$chrono_order = true;
			}
			elseif($json['chrono_order'] === 'antichrono'){
				$chrono_order = false;
			}
			else{
				echo json_encode(['success' => false]);
				die;
			}
			$model->getNode()->getNodeData()->setChronoOrder($chrono_order);
			$entityManager->flush();
			
			echo json_encode(['success' => true, 'chrono_order' => $json['chrono_order']]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}

	static public function changePresentation(EntityManager $entityManager, array $json): void
	{
		if(isset($json['id']) && isset($json['presentation'])){
			$model = new Model($entityManager);
			$model->findNodeById($json['id']);

			if(in_array($json['presentation'], array_keys(Blocks::$presentations))){
				$model->getNode()->getNodeData()->setPresentation($json['presentation']);
				$entityManager->flush();

				$response_data = ['success' => true, 'presentation' => $json['presentation']];
				if($json['presentation'] === 'grid'){
					$response_data['cols_min_width'] = $model->getNode()->getNodeData()->getColsMinWidth();
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
			$model = new Model($entityManager);
			$model->findNodeById($json['id']);
			$model->getNode()->getNodeData()->setColsMinWidth((int)$json['cols_min_width']); // attention conversion?

			$entityManager->flush();
			echo json_encode(['success' => true, 'cols_min_width' => $json['cols_min_width']]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
	static public function changePaginationLimit(EntityManager $entityManager, array $json): void
	{
		if(isset($json['id']) && isset($json['pagination_limit'])){
			$model = new Model($entityManager);
			$model->findNodeById($json['id']);
			$old_limit = $model->getNode()->getNodeData()->getPaginationLimit() ?? 12;
			$model->getNode()->getNodeData()->setPaginationLimit((int)$json['pagination_limit']); // attention conversion?

			$entityManager->flush();

			echo json_encode(['success' => true, 'old_limit' => $old_limit, 'new_limit' => $json['pagination_limit']]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
}