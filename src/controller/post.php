<?php
// src/controller/post.php

declare(strict_types=1);

if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['admin'] === true)
{
    /* -- requêtes non AJAX -- */
    // page Menu et chemin
    /*if(isset($_POST['menu_edit_post']) && isset($_POST['id']))
    {
        // <= flèche gauche: le parent devient le grand-parent position = position du parent + 1, recalculer les positions des enfants restants
        if($_POST['menu_edit_post'] == 'move_one_level_up'){
            Director::$menu_data = new Menu($entityManager);
            $page = Director::$menu_data->findPageById((int)$_POST['id']);

            $parent = $page->getParent(); // peut être null
            if($parent === null){
                // 1er niveau: ne rien faire
                header('Location: ' . new URL(['page' => 'menu_chemins']));
                die;
            }
            else{
                $page->setPosition($parent->getPosition() + 1); // nouvelle position

                // 2ème niveau: le parent devient $menu_data, puis null après tri
                if($parent->getParent() === null){
                    // connexion dans les deux sens
                    $page->setParent(Director::$menu_data); // => pour la persistance
                    Director::$menu_data->addChild($page); // => pour sortChildren

                    //Director::$menu_data->sortChildren(true); // positions décaléees des nouveaux petits frères
                    $page->getParent()->sortChildren(true); // positions décaléees des nouveaux petits frères

                    $page->setParent(null);
                }
                // 3ème niveau et plus
                else{
                    $page->setParent($parent->getParent()); // nouveau parent
                    $page->getParent()->sortChildren(true); // positions décaléees des nouveaux petits frères
                }
                //$parent->sortChildren(true); // positions des enfants restants, inutile si la fonction est récursive?
                echo $page->getPosition();
                //die;
            }
            $entityManager->flush();

            header('Location: ' . new URL(['page' => 'menu_chemins']));
            die;
        }
        // => flèche droite: l'élément précédent devient le parent, position = nombre d'éléments de la fraterie + 1
        elseif($_POST['menu_edit_post'] == 'move_one_level_down')
        {
            Director::$menu_data = new Menu($entityManager);
            $page = Director::$menu_data->findPageById((int)$_POST['id']);

            $parent = $page->getParent(); // peut être null
            if($parent == null){
                $parent = Director::$menu_data;
            }

            $parent->sortChildren(true); // trie et réindexe par sécurité: 1, 2, 3...
            if($page->getPosition() > 1){
                foreach($parent->getChildren() as $child){
                    echo $child->getPageName();
                    if($child->getPosition() === $page->getPosition() - 1){
                        $page->setParent($child);
                        break;
                    }
                }
                $page->setPosition(count($page->getParent()->getChildren()) + 1);
            }
            $entityManager->flush();

            header('Location: ' . new URL(['page' => 'menu_chemins']));
            die;
        }
        else{
            // you talking to me?
            die;
        }
    }*/

    /* -- requêtes AJAX -- */
    require '../src/controller/ajax.php';

    // formulaires HTML
    /*if(isset($_POST['from']) // page d'où vient la requête
        && isset($_POST)) // données
    {
        echo "requête envoyée en validant un formulaire";
    }*/
}
