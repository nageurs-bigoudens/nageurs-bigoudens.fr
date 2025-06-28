<?php
// src/controller/post.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\NodeData;
use App\Entity\Page;
use App\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;

if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['admin'] === true)
{
    /* -- formulaires HTML classiques -- */
    if($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded')
    {
        /* -- nouvelle page -- */
        if(isset($_POST['page_name']) && $_POST['page_name'] !== null
            && isset($_POST['page_name_path']) && $_POST['page_name_path'] !== null
            && isset($_POST['page_location']) && $_POST['page_location'] !== null
            && isset($_POST['page_description']) && $_POST['page_description'] !== null
            && isset($_POST['new_page_hidden']) && $_POST['new_page_hidden'] === '')
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
            $node = new Node(
                'head',
                null, [],
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
            header("Location: " . new URL(['page' => $page->getPagePath(), 'action' => 'modif_page']));
            die;
        }
        
        /* -- suppression d'une page -- */
        elseif(isset($_POST['page_id']) && $_POST['page_id'] !== null
            && isset($_POST['submit_hidden']) && $_POST['submit_hidden'] === '')
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


        /* -- mode Modification d'une page -- */

        // modification des titres, chemins et descriptions
        elseif(isset($_POST['page_menu_path']) && $_POST['page_menu_path'] !== null
            && isset($_POST['page_id']) && $_POST['page_id'] !== null
            && isset($_POST['page_name_path_hidden']) && $_POST['page_name_path_hidden'] === '')
        {
            $director = new Director($entityManager, true);
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
            header("Location: " . new URL(['page' => $page->getPagePath(), 'action' => 'modif_page']));
            die;
        }
        // ajout d'un bloc dans une page
        elseif(isset($_POST['bloc_title']) && $_POST['bloc_title'] !== null
            && isset($_POST['bloc_select']) && $_POST['bloc_select'] !== null
            && isset($_POST['bloc_title_hidden']) && $_POST['bloc_title_hidden'] === '') // contrôle anti-robot avec input hidden
        {
            $director = new Director($entityManager, true); // on a besoin de page_path qui dépend de menu_data
            $page = Director::$page_path->getLast();
            $director->findUniqueNodeByName('main');
            $director->findItsChildren();
            $main = $director->getNode();
            $position = count($main->getChildren()) + 1; // position dans la fraterie

            $blocks = ['blog', 'grid', 'calendar', 'galery', 'form']; // même liste dans FormBuilder.php
            if(!in_array($_POST["bloc_select"], $blocks, true)) // 3è param: contrôle du type
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
                $entityManager->persist($bulk_data[0]);
            }

            $bloc = new Node(
                $_POST["bloc_select"],
                null, [],
                $position,
                $main,
                $page);
            $data = new NodeData(
                ['title' => trim(htmlspecialchars($_POST["bloc_title"]))],
                $bloc);

            $entityManager->persist($bloc);
            $entityManager->persist($data);
            $entityManager->flush();
            header("Location: " . new URL(['page' => $_GET['page'], 'action' => 'modif_page']));
            die;
        }
        // suppression d'un bloc de page
        elseif(isset($_POST['delete_bloc_id']) && $_POST['delete_bloc_id'] !== null
            && isset($_POST['delete_bloc_hidden']) && $_POST['delete_bloc_hidden'] === '') // contrôle anti-robot avec input hidden
        {
            $director = new Director($entityManager, true);
            $director->findUniqueNodeByName('main');
            $director->findItsChildren();
            //$director->findNodeById((int)$_POST['delete_bloc_id']);
            $main = $director->getNode();
            $bloc;
            foreach($main->getChildren() as $child){
                if($child->getId() === (int)$_POST['delete_bloc_id']){
                    $bloc = $child;
                    break;
                }
            }
            $main->removeChild($bloc); // réindex le tableau $children au passage
            $main->reindexPositions();

            $entityManager->remove($bloc); // suppression en BDD
            $entityManager->flush();
            header("Location: " . new URL(['page' => $_GET['page'], 'action' => 'modif_page']));
            die;
        }


        /* -- page Menu et chemins -- */

        // création d'une entrée de menu avec une URL
        elseif(isset($_POST["label_input"]) && isset($_POST["url_input"]) && isset($_POST["location"])){
            Director::$menu_data = new Menu($entityManager);
            $previous_page = Director::$menu_data->findPageById((int)$_POST["location"]); // (int) à cause de declare(strict_types=1);
            $parent = $previous_page->getParent();

            $page = new Page(
                trim(htmlspecialchars($_POST["label_input"])),
                filter_var($_POST["url_input"], FILTER_VALIDATE_URL),
                true, true, false,
                $previous_page->getPosition(),
                $parent); // peut et DOIT être null si on est au 1er niveau

            // on a donné à la nouvelle entrée la même position qu'à la précédente,
            // addChild l'ajoute à la fin du tableau "children" puis on trie
            // exemple avec 2 comme position demandée: 1 2 3 4 2 devient 1 2 3 4 5 et la nouvelle entrée sera en 3è position
            if($parent == null){
                $parent = Director::$menu_data;
            }
            $parent->addChild($page); // true pour réindexer les positions en BDD
            $parent->reindexPositions();

            $entityManager->persist($page);
            $entityManager->flush();
            header("Location: " . new URL(['page' => $_GET['from']]));
            die;
        }
        // suppression d'une entrée de menu avec une URL
        elseif(isset($_POST['delete']) && isset($_POST['x']) && isset($_POST['y'])){ // 2 params x et y sont là parce qu'on a cliqué sur une image
            Director::$menu_data = new Menu($entityManager);
            $page = Director::$menu_data->findPageById((int)$_POST["delete"]);
            $parent = $page->getParent();
            if($parent == null){
                $parent = Director::$menu_data;
            }

            $parent->removeChild($page); // suppression de $children avant de trier
            $parent->reindexPositions();

            $entityManager->remove($page); // suppression en BDD
            $entityManager->flush();
            header("Location: " . new URL(['page' => $_GET['from']]));
            die;
        }
        elseif(isset($_GET['action']) && $_GET['action'] === 'modif_mdp'
            && isset($_POST['login']) && isset($_POST['old_password']) && isset($_POST['new_password'])
            && isset($_POST['modify_password_hidden']) && empty($_POST['modify_password_hidden']))
        {
            changePassword($entityManager);
            header("Location: " . new URL(['page' => $_GET['from']]));
            die;
        }
        else{
            header("Location: " . new URL(['error' => 'paramètres inconnus']));
            die;
        }
    }

    /* -- requêtes AJAX -- */
    else{
        require '../src/controller/ajax.php';
    }

    require '../src/controller/ajax_calendar_admin.php';
}

require '../src/controller/ajax_calendar_visitor.php';
