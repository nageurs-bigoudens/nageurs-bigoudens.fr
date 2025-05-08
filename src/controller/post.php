<?php
// src/controller/post.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\NodeData;
use App\Entity\Page;

if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['admin'] === true)
{
    /* -- formulaires HTML classiques -- */
    if($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded')
    {
        /* -- mode Modification d'une page -- */

        // ajout d'un bloc dans une page
        if(isset($_POST['bloc_title']) && isset($_POST['bloc_select'])){
            $director = new Director($entityManager, true); // on a besoin de page_path qui dépend de menu_data
            $page = Director::$page_path->getLast();
            $director->findNodeByName('main');
            $main = $director->getNode();
            $position = count($main->getChildren()) + 1; // position dans la fraterie

            $bloc = new Node(
                trim(htmlspecialchars($_POST["bloc_select"])),
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
                $parent);

            // on indique pour la nouvelle entrée la même position que la précédente, puis addChild l'ajoute à la fin du tableau "children" avant de déclencher un tri
            // exemple avec 2 comme position demandée: 1 2 3 4 2 devient 1 2 3 4 5 et la nouvelle entrée sera en 3è position
            if($parent == null){
                $parent = Director::$menu_data;
            }
            $parent->addChild($page); // true pour réindexer les positions en BDD
            $parent->reindex();

            $entityManager->persist($page);
            $entityManager->flush();
            header("Location: " . new URL(['page' => $_GET['from']]));
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
            $parent->reindex();

            $entityManager->remove($page); // suppression en BDD
            $entityManager->flush();
            header("Location: " . new URL(['page' => $_GET['from']]));
        }
        else{
            header("Location: " . new URL(['error' => 'paramètres inconnus']));
        }
    }

    /* -- requêtes AJAX -- */
    else{
        require '../src/controller/ajax.php';
    }
}
