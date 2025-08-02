<?php
// src/controller/post_router.php
//
// routage des requêtes des formulaires et AJAX
// n'utilisent que des POST à l'exception d'un GET par fullcalendar
// les contrôleurs des formulaires sont appelés ici,
// ceux des requêtes AJAX sont derrière d'autres routeurs

declare(strict_types=1);


/* appel des contrôleurs dans password.php */
if(isset($_GET['action']) && $_GET['action'] === 'deconnexion')
{
    disconnect($entityManager);
}
elseif(isset($_GET['action']) && $_GET['action'] === 'modif_mdp')
{
    changePassword($entityManager);
}


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    /* -- contrôleurs appellables par tout le monde -- */
    // POST "ajax"
    require '../src/controller/ajax_email.php';

    // POST "form"
    // ...


    if($_SESSION['admin'] === true)
    {
        /* -- requêtes "form" -- */
        if($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded') // moyen approximatif de distinguer les requêtes de formulaires et AJAX
        {
            require '../src/controller/post_functions_admin.php';

            /* -- nouvelle page -- */
            if(isset($_POST['page_name']) && $_POST['page_name'] !== null
                && isset($_POST['page_name_path']) && $_POST['page_name_path'] !== null
                && isset($_POST['page_location']) && $_POST['page_location'] !== null
                && isset($_POST['page_description']) && $_POST['page_description'] !== null
                && isset($_POST['new_page_hidden']) && $_POST['new_page_hidden'] === '')
            {
                newPage($entityManager);
            }
            
            /* -- suppression d'une page -- */
            elseif(isset($_POST['page_id']) && $_POST['page_id'] !== null
                && isset($_POST['submit_hidden']) && $_POST['submit_hidden'] === '')
            {
                deletePage($entityManager);
            }


            /* -- mode Modification d'une page -- */

            // modification du chemins en snake_case
            elseif(isset($_POST['page_menu_path']) && $_POST['page_menu_path'] !== null
                && isset($_POST['page_id']) && $_POST['page_id'] !== null
                && isset($_POST['page_name_path_hidden']) && $_POST['page_name_path_hidden'] === '')
            {
                pageMenuPathUpdate($entityManager);
            }
            // ajout d'un bloc dans une page
            elseif(isset($_POST['bloc_title']) && $_POST['bloc_title'] !== null
                && isset($_POST['bloc_select']) && $_POST['bloc_select'] !== null
                && isset($_POST['bloc_title_hidden']) && $_POST['bloc_title_hidden'] === '') // contrôle anti-robot avec input hidden
            {
                addBloc($entityManager);
            }
            // suppression d'un bloc de page
            elseif(isset($_POST['delete_bloc_id']) && $_POST['delete_bloc_id'] !== null
                && isset($_POST['delete_bloc_hidden']) && $_POST['delete_bloc_hidden'] === '') // contrôle anti-robot avec input hidden
            {
                deleteBloc($entityManager);
            }


            /* -- page Menu et chemins -- */

            // création d'une entrée de menu avec une URL
            elseif(isset($_POST["label_input"]) && isset($_POST["url_input"]) && isset($_POST["location"])){
                newUrlMenuEntry($entityManager);
            }
            // suppression d'une entrée de menu avec une URL
            elseif(isset($_POST['delete']) && isset($_POST['x']) && isset($_POST['y'])){ // 2 params x et y sont là parce qu'on a cliqué sur une image
                deleteUrlMenuEntry($entityManager);
            }

            // modification du mot de passe
            elseif(isset($_GET['action']) && $_GET['action'] === 'modif_mdp'
                && isset($_POST['login']) && isset($_POST['old_password']) && isset($_POST['new_password'])
                && isset($_POST['modify_password_hidden']) && empty($_POST['modify_password_hidden']))
            {
                changePassword($entityManager);
            }
            else{
                header("Location: " . new URL(['error' => 'paramètres inconnus']));
                die;
            }
        }

        /* -- requêtes AJAX -- */
        else{
            require '../src/controller/ajax_admin.php';
            require '../src/controller/ajax_calendar_admin.php';
        }
    }
}
// cas particulier d'un GET ajax non-admin par fullcalendar
elseif($_SERVER['REQUEST_METHOD'] === 'GET'){
    // non-admin
    require '../src/controller/ajax_calendar_visitor.php';

    if($_SESSION['admin'] === true){
        // ...
    }
}