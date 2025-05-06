<?php
// src/controller/post.php

declare(strict_types=1);

use App\Entity\Page;

if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['admin'] === true)
{
    /* -- formulaires HTML classiques -- */
    if($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded')
    {
        // création d'une entrée de menu avec une URL
        if(isset($_POST["label_input"]) && isset($_POST["url_input"]) && isset($_POST["location"])){
            echo $_POST["label_input"] . '<br>';
            echo $_POST["url_input"] . '<br>';
            echo $_POST["location"] . '<br>'; // id entrée précédente

            Director::$menu_data = new Menu($entityManager);
            $previous_page = Director::$menu_data->findPageById((int)$_POST["location"]); // (int) à cause de declare(strict_types=1);
            $parent = $previous_page->getParent();

            $page = new Page($_POST["label_input"], $_POST["url_input"], true, true, false, $previous_page->getPosition(), $parent);
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
