<?php
// src/router.php
//
/* fonctionnement du routeur
=> 1er test, méthode http: GET, POST ou autre chose
=> 2ème test, type de contenu (méthode POST uniquement):
"application/x-www-form-urlencoded" = formulaire
"application/json" = requête AJAX avec fetch()
"multipart/form-data" = upload d'image par tinymce
$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' requête AJAX xhs, non utilisée
=> 3ème test, comme le 2ème test mais uniquement si $_SESSION['admin'] est vrai
*/

declare(strict_types=1);


if($_SERVER['REQUEST_METHOD'] === 'GET'){
    // table "user" vide
    if(!UserController::existUsers($entityManager)){
        require '../src/view/templates/user_create.php';
        die;
    }

    // bouton déconnexion
    if($request->query->has('action') && $request->query->get('action') === 'deconnection')
    {
        UserController::disconnect($entityManager);
    }

    // données du calendrier
    // création du calendrier et changement de dates affichées (boutons flèches mais pas changement de vue)
    if($_SERVER['REQUEST_METHOD'] === 'GET' && $request->query->has('action') && $request->query->get('action') === 'get_events'
        && $request->query->has('start') && $request->query->has('end') && empty($_POST))
    {
        CalendarController::getData($entityManager);
    }

    if($_SESSION['admin'] === true){
        // ...
    }

    // construction d'une page
    $response = new ViewController()->buildView($request, $entityManager);
}


elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
    /* -- contrôleurs appellables par tout le monde -- */

    // table "user" vide
    if(!UserController::existUsers($entityManager))
    {
        UserController::createUser($entityManager);
    }
    
    // requêtes JSON avec fetch()
    if($_SERVER['CONTENT_TYPE'] === 'application/json')
    {
        $data = file_get_contents('php://input');
        $json = json_decode($data, true);

        if(isset($_GET['action']))
        {
            // formulaire de contact
            if($_GET['action'] === 'send_email'){
                ContactFormController::sendVisitorEmail($entityManager, $json);
            }
        }
    }

    // envoi formulaire HTML
    elseif($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded'){
        // tentative de connexion
        if($request->query->has('action') && $request->query->get('action') === 'connection'){
            //$response = 
            UserController::connect($entityManager);
        }
    }


    if($_SESSION['admin'] === true)
    {
        /* -- requêtes AJAX -- */

        // requêtes JSON avec fetch()
        if($_SERVER['CONTENT_TYPE'] === 'application/json')
        {
            $data = file_get_contents('php://input');
            $json = json_decode($data, true);

            if(isset($_GET['action']))
            {
                /* -- manipulation des articles -- */
                if($_GET['action'] === 'editor_submit' && isset($json['id']) && isset($json['content']))
                {
                    ArticleController::editorSubmit($entityManager, $json);
                }
                elseif($_GET['action'] === 'delete_article' && isset($json['id']))
                {
                    ArticleController::deleteArticle($entityManager, $json);
                }
                // inversion de la position de deux noeuds
                elseif($_GET['action'] === 'switch_positions' && isset($json['id1']) && isset($json['id2']))
                {
                    ArticleController::switchPositions($entityManager, $json);
                }
                elseif($_GET['action'] === 'date_submit' && isset($json['id']) && isset($json['date']))
                {
                    ArticleController::dateSubmit($entityManager, $json);
                }

                /* -- bloc Formulaire -- */
                elseif($_GET['action'] === 'recipient_email'){
                    ContactFormController::updateRecipient($entityManager, $json);
                }
                elseif($_GET['action'] === 'test_email'){
                    ContactFormController::sendTestEmail($entityManager, $json);
                }
            }

            /* -- page Menu et chemins -- */
            elseif(isset($_GET['menu_edit']))
            {
                // récupération des données (serait peut-être mieux dans la classe)
                Director::$menu_data = new Menu($entityManager);

                // flèche gauche <=: position = position du parent + 1, parent = grand-parent, recalculer les positions
                if($_GET['menu_edit'] === 'move_one_level_up' && isset($json['id'])){
                    MenuAndPathsController::MoveOneLevelUp($entityManager, $json);
                }

                // flèche droite =>: position = nombre d'éléments de la fraterie + 1, l'élément précédent devient le parent
                if($_GET['menu_edit'] === 'move_one_level_down' && isset($json['id'])){
                    MenuAndPathsController::MoveOneLevelDown($entityManager, $json);
                }

                if($_GET['menu_edit'] === 'switch_positions' && isset($json['id1']) && isset($json['id2'])){
                    MenuAndPathsController::switchPositions($entityManager, $json);
                }

                if($_GET['menu_edit'] === 'displayInMenu' && isset($json['id']) && isset($json['checked'])){
                    MenuAndPathsController::displayInMenu($entityManager, $json);
                }
            }

            /* -- mode Modification d'une page -- */
            // partie "page"
            elseif(isset($_GET['page_edit']))
            {
                // titre de la page
                if($_GET['page_edit'] === 'page_title'){
                    PageManagementController::setPageTitle($entityManager, $json);
                }
                // description dans les métadonnées
                elseif($_GET['page_edit'] === 'page_description'){
                    PageManagementController::setPageDescription($entityManager, $json);
                }
            }

            // partie "blocs"
            elseif($request->query->has('bloc_edit'))
            {
                // renommage d'un bloc
                if($request->query->get('bloc_edit') === 'rename_page_bloc')
                {
                    PageManagementController::renameBloc($entityManager, $json);
                }
                // inversion des positions de deux blocs
                elseif($request->query->get('bloc_edit') === 'switch_blocs_positions')
                {
                    PageManagementController::SwitchBlocsPositions($entityManager, $json);
                }
            }

            /* -- upload d'image dans tinymce par copier-coller -- */
            // collage de HTML contenant une ou plusieurs balises <img>
            if($request->query->has('action') && $request->query->get('action') == 'upload_image_html'){
                ImageUploadController::uploadImageHtml();
            }
            // collage d'une image (code base64 dans le presse-papier) non encapsulée dans du HTML
            elseif($request->query->has('action') && $request->query->get('action') == 'upload_image_base64'){
                ImageUploadController::uploadImageBase64();
            }
            
            /* -- requêtes spécifiques au calendrier -- */
            if($request->query->get('action') === 'new_event'){
                CalendarController::newEvent($json, $entityManager);
            }
            elseif($request->query->get('action') === 'update_event'){
                CalendarController::updateEvent($json, $entityManager);
            }
            elseif($request->query->get('action') === 'remove_event'){
                CalendarController::removeEvent($json, $entityManager);
            }
            else{
                echo json_encode(['success' => false]);
            }
            die;
        }

        // upload d'image dans tinymce avec le plugin (bouton "insérer une image" de l'éditeur)
        elseif(strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false && $request->query->has('action') && $request->query->get('action') === 'upload_image')
        {
            ImageUploadController::imageUploadTinyMce();
        }
        // requêtes XMLHttpRequest
        elseif(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        {
            //echo "requête XMLHttpRequest reçue par le serveur";
            echo json_encode(['success' => false]); // ça marche mais ça marche pas...
            die;
        }

        /* -- envoi formulaire HTML -- */
        elseif($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded')
        {
            /* -- nouvelle page -- */
            if(isset($_POST['page_name']) && $_POST['page_name'] !== null
                && isset($_POST['page_name_path']) && $_POST['page_name_path'] !== null
                && isset($_POST['page_location']) && $_POST['page_location'] !== null
                && isset($_POST['page_description']) && $_POST['page_description'] !== null
                && isset($_POST['new_page_hidden']) && $_POST['new_page_hidden'] === '')
            {
                PageManagementController::newPage($entityManager);
            }
            
            /* -- suppression d'une page -- */
            elseif(isset($_POST['page_id']) && $_POST['page_id'] !== null
                && isset($_POST['submit_hidden']) && $_POST['submit_hidden'] === '')
            {
                PageManagementController::deletePage($entityManager);
            }

            /* -- mode Modification d'une page -- */

            // modification du chemins en snake_case
            elseif(isset($_POST['page_menu_path']) && $_POST['page_menu_path'] !== null
                && isset($_POST['page_id']) && $_POST['page_id'] !== null
                && isset($_POST['page_name_path_hidden']) && $_POST['page_name_path_hidden'] === '')
            {
                PageManagementController::updatePageMenuPath($entityManager);
            }
            // ajout d'un bloc dans une page
            elseif(isset($_POST['bloc_title']) && $_POST['bloc_title'] !== null
                && isset($_POST['bloc_select']) && $_POST['bloc_select'] !== null
                && isset($_POST['bloc_title_hidden']) && $_POST['bloc_title_hidden'] === '') // contrôle anti-robot avec input hidden
            {
                PageManagementController::addBloc($entityManager);
            }
            // suppression d'un bloc de page
            elseif(isset($_POST['delete_bloc_id']) && $_POST['delete_bloc_id'] !== null
                && isset($_POST['delete_bloc_hidden']) && $_POST['delete_bloc_hidden'] === '') // contrôle anti-robot avec input hidden
            {
                PageManagementController::deleteBloc($entityManager);
            }


            /* -- page Menu et chemins -- */

            // création d'une entrée de menu avec une URL
            elseif(isset($_POST["label_input"]) && isset($_POST["url_input"]) && isset($_POST["location"])){
                MenuAndPathsController::newUrlMenuEntry($entityManager);
            }
            // suppression d'une entrée de menu avec une URL
            elseif(isset($_POST['delete']) && isset($_POST['x']) && isset($_POST['y'])){ // 2 params x et y sont là parce qu'on a cliqué sur une image
                MenuAndPathsController::deleteUrlMenuEntry($entityManager);
            }


            /* -- page Mon compte -- */
            elseif($request->query->has('action') && $request->query->get('action') === 'update_username')
            {
                UserController::updateUsername($entityManager);
            }
            elseif($request->query->has('action') && $request->query->get('action') === 'update_password')
            {
                UserController::updatePassword($entityManager);
            }

            // redirection page d'accueil
            else{
                header("Location: " . new URL(['error' => 'paramètres inconnus']));
                die;
            }
        }
    }

    // rien ne match
    header("Location: " . new URL);
    die;
}


else{
    header("Location: " . new URL(['error' => 'tu fais quoi là mec?']));
    die;
}

// enlever le test isset à terme
if(isset($response)){
    $response->send();
}