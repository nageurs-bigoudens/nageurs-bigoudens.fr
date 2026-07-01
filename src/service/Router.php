<?php
// src/service/router.php
//
/* fonctionnement:
=> 1er test, méthode http GET? POST?
=> 2ème test, type de contenu:
"application/x-www-form-urlencoded" = formulaire
"application/json" = requête AJAX avec fetch()
"multipart/form-data" = upload d'image par tinymce
$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' requête AJAX xhs, non utilisée
=> 3ème test, comme le 2ème test mais uniquement si IS_ADMIN est vrai */

/* classes de réponses pour les contrôleurs
Response($html, Response::HTTP_OK)                  page html
JsonResponse(['success' => true, 'data' => $data])  ajax
RedirectResponse('index.php?page=login')            redirection
BinaryFileResponse($filePath)                       téléchargement
StreamedResponse(function () {echo "ligne 1\n";echo "ligne 2\n";})  gros fichier */

// relire ça à l'occaz:
// https://symfony.com/doc/current/introduction/from_flat_php_to_symfony.html

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class Router{
    private Request $request;
    private EntityManager $entityManager;
    private string $route = ''; // défaut page d'accueil

    public function __construct(Request $request, EntityManager $entityManager){
        $this->request = $request;
        $this->entityManager = $entityManager;

        if(!User::existUsers($entityManager)){ // table "user" vide
            $this->route = 'no_user';
        }
    }

    public function dispatch(): Response
    {
        if($this->request->getMethod() === 'GET'){
            // table "user" vide
            if($this->route === 'no_user'){
                ob_start();
                require AbstractBuilder::VIEWS_PATH . 'user_create.php';
                return new Response(ob_get_clean());
            }

            // bouton déconnexion (méthode GET parce que l'utilisateur ne modifie plus de données à partir de là)
            if($this->request->query->get('action') === 'deconnection'){
                return UserController::disconnect(); // retourne un RedirectResponse
            }

            // articles suivants
            if($this->request->query->get('fetch') === 'next_articles'){
                return ArticleController::fetch($this->entityManager, $this->request); // retourne un JsonResponse
            }

            // données du calendrier
            // création du calendrier ou changement de dates affichées (boutons flèches mais pas changement de vue)
            if($this->request->query->get('action') === 'get_events'
                && $this->request->query->has('start') && $this->request->query->has('end') && empty($this->request->getPayload()->all())){ // getPayload ne récupère pas que des POST
                return CalendarController::getData($this->entityManager);
            }

            // pages interdites
            if(!IS_ADMIN && in_array(CURRENT_PAGE, ['menu_paths', 'new_page', 'user_edit', 'emails', 'maintenance'])){
                return new RedirectResponse((string)new URL);
            }

            if(IS_ADMIN){
                if($this->request->query->get('action') === 'get_mysqldump'){
                    return MaintenanceController::getLastDump($this->entityManager);
                }
                if($this->request->query->get('action') === 'get_all_media'){
                    return MaintenanceController::getAllMedia();
                }
            }

            // affichage d'une page
            return ViewController::getWebPage($this->entityManager, $this->request);
        }


        elseif($this->request->getMethod() === 'POST'){
            /* -- contrôleurs appelables par tout le monde -- */

            // table "user" vide
            if($this->route === 'no_user'){
                return UserController::createAdminUser($this->entityManager);
            }
            
            // requête JSON avec fetch()
            if($this->request->headers->get('Content-Type') === 'application/json')
            {
                $json = json_decode($this->request->getContent(), true); // = json_decode(file_get_contents('php://input'), true);

                // formulaire de contact
                if($this->request->query->get('action') === 'send_email'){
                    return ContactFormController::sendVisitorEmail($this->entityManager, $json);
                }
                /*else{
                    return new JsonResponse(['success' => false, 'error' => 'tu fais quoi là mec?'], JsonResponse::HTTP_BAD_REQUEST);
                }*/
            }

            // envoi formulaire HTML
            elseif($this->request->headers->get('Content-Type') === 'application/x-www-form-urlencoded'){
                // tentative de connexion
                if($this->request->query->get('action') === 'connection'){
                    return UserController::connect($this->entityManager);
                }
                /*else{
                    return new RedirectResponse((string)new URL(['error' => 'tu fais quoi là mec?']));
                }*/
            }

            if(IS_ADMIN){
                /* -- requêtes AJAX -- */

                // requêtes JSON avec fetch()
                if($this->request->headers->get('Content-Type') === 'application/json'){
                    $json = json_decode($this->request->getContent(), true); // = json_decode(file_get_contents('php://input'), true);

                    if($this->request->query->has('action')){
                        /* -- manipulation des articles -- */
                        if($this->request->query->get('action') === 'editor_submit' && isset($json['id']) && isset($json['content'])){
                            return ArticleController::editorSubmit($this->entityManager, $json);
                        }
                        elseif($this->request->query->get('action') === 'delete_article' && isset($json['id'])){
                            return ArticleController::deleteArticle($this->entityManager, $this->request); // version AJAX
                        }
                        elseif($this->request->query->get('action') === 'switch_positions' && isset($json['id1']) && isset($json['id2'])){
                            return ArticleController::switchPositions($this->entityManager, $json);
                        }
                        elseif($this->request->query->get('action') === 'date_submit' && isset($json['id']) && isset($json['date'])){
                            return ArticleController::dateSubmit($this->entityManager, $json);
                        }

                        switch($this->request->query->get('action')){
                            /* -- bloc Formulaire -- */
                            case 'keep_emails':
                                return ContactFormController::keepEmails($this->entityManager, $json);
                            case 'set_retention_period':
                                return ContactFormController::setEmailsRetentionPeriod($this->entityManager, $json);
                            case 'set_email_param':
                                return ContactFormController::setEmailParam($this->entityManager, $json);
                            case 'test_email':
                                return ContactFormController::sendTestEmail($this->entityManager, $json);

                            /* -- page emails -- */
                            case 'delete_email':
                                return ContactFormController::deleteEmail($this->entityManager, $json);
                            case 'toggle_sensitive_email':
                                return ContactFormController::toggleSensitiveEmail($this->entityManager, $json);

                            /* -- upload d'image dans tinymce par copier-coller -- */
                            // collage de HTML contenant une ou plusieurs balises <img>
                            case 'upload_image_url':
                                return ImageUploadController::uploadImageHtml();
                            // collage d'une image (code base64 dans le presse-papier) non encapsulée dans du HTML
                            case 'upload_image_base64':
                                return ImageUploadController::uploadImageBase64();

                            /* -- requêtes spécifiques au calendrier -- */
                            case 'new_event':
                                return CalendarController::newEvent($json, $this->entityManager);
                            case 'update_event':
                                return CalendarController::updateEvent($json, $this->entityManager);
                            case 'remove_event':
                                return CalendarController::removeEvent($json, $this->entityManager);

                            /* -- mode maintenance -- */
                            case 'get_logs':
                                return MaintenanceController::getLogs($this->entityManager);
                            case 'erase_logs':
                                return MaintenanceController::eraseLogs($this->entityManager);

                            default:
                                return new JsonResponse(['success' => false, 'error' => 'bad parameters'], JsonResponse::HTTP_BAD_REQUEST);
                        }
                    }

                    /* -- site entier (header, footer, favicon) -- */
                    elseif($this->request->query->has('head_foot_text')){
                        return HeadFootController::setTextData($this->entityManager, $this->request->query->get('head_foot_text'), $json);
                    }
                    elseif($this->request->query->has('head_foot_social_check')){
                        return HeadFootController::displaySocialNetwork($this->entityManager, $this->request->query->get('head_foot_social_check'), $json);
                    }

                    /* -- page Menu et chemins -- */
                    elseif($this->request->query->has('menu_edit')){
                        // ne suit pas la règle, faire ça dans un contrôleur?
                        Model::$menu = new Menu($this->entityManager); // récupération des données

                        // flèche gauche <=: position = position du parent + 1, parent = grand-parent, recalculer les positions
                        if($this->request->query->get('menu_edit') === 'move_one_level_up' && isset($json['id'])){
                            return MenuAndPathsController::MoveOneLevelUp($this->entityManager, $json);
                        }
                        // flèche droite =>: position (léments de a fraterie + 1, l'élément précédent devient le parent
                        elseif($this->request->query->get('menu_edit') === 'move_one_level_down' && isset($json['id'])){
                            return MenuAndPathsController::MoveOneLevelDown($this->entityManager, $json);
                        }
                        elseif($this->request->query->get('menu_edit') === 'switch_positions' && isset($json['id1']) && isset($json['id2'])){
                            return MenuAndPathsController::switchPositions($this->entityManager, $json);
                        }
                        elseif($this->request->query->get('menu_edit') === 'display_in_menu' && isset($json['id']) && isset($json['checked'])){
                            return MenuAndPathsController::displayInMenu($this->entityManager, $json);
                        }
                        elseif($this->request->query->get('menu_edit') === 'url_edit' && isset($json['id']) && isset($json['field']) && isset($json['input_data'])){
                            return MenuAndPathsController::editUrl($this->entityManager, $json);
                        }
                        else{
                            return new JsonResponse(['success' => false, 'error' => 'bad parameters'], JsonResponse::HTTP_BAD_REQUEST); // code 400
                        }
                    }

                    /* -- mode Modification d'une page -- */
                    // partie "page"
                    elseif($this->request->query->has('page_edit')){
                        switch($this->request->query->get('page_edit')){
                            case 'page_title':
                                return PageManagementController::setPageTitle($this->entityManager, $json);
                            case 'page_description':
                                return PageManagementController::setPageDescription($this->entityManager, $json);
                            default:
                                return new JsonResponse(['success' => false, 'error' => 'bad parameters'], JsonResponse::HTTP_BAD_REQUEST); // code 400
                        }
                    }

                    // partie "blocs"
                    elseif($this->request->query->has('bloc_edit')){
                        switch($this->request->query->get('bloc_edit')){
                            case 'rename_page_bloc':
                                return PageManagementController::renameBloc($this->entityManager, $json);
                            case 'switch_blocs_positions':
                                return PageManagementController::SwitchBlocsPositions($this->entityManager, $json, $this->request->query->get('page'));
                            case 'change_articles_order':
                                return PageManagementController::changeArticlesOrder($this->entityManager, $json);
                            case 'change_presentation':
                                return PageManagementController::changePresentation($this->entityManager, $json);
                            case 'change_cols_min_width':
                                return PageManagementController::changeColsMinWidth($this->entityManager, $json);
                            case 'change_pagination_limit':
                                return PageManagementController::changePaginationLimit($this->entityManager, $json);
                            default:
                                return new JsonResponse(['success' => false, 'error' => 'bad parameters'], JsonResponse::HTTP_BAD_REQUEST); // code 400
                        }
                    }

                    else{
                        return new JsonResponse(['success' => false, 'error' => 'bad parameters'], JsonResponse::HTTP_BAD_REQUEST); // code 400
                    }
                }

                /* -- upload avec FormData OU formulaire HTML avec fichier -- */
                elseif(str_starts_with($this->request->headers->get('Content-Type'), 'multipart/form-data')){ // = $_SERVER['CONTENT_TYPE']
                    // dans tinymce avec le plugin (bouton "insérer une image" de l'éditeur ou glisser-déposer)
                    if($this->request->query->get('action') === 'upload_image_tinymce'){
                        return ImageUploadController::imageUploadTinyMce();
                    }
                    // dans tinymce, des quatre méthodes: bouton "link", drag & drop, html, base64
                    elseif($this->request->query->get('action') === 'upload_file_tinymce'){
                        return FileUploadController::fileUploadTinyMce();
                    }
                    elseif($this->request->query->has('head_foot_image')){
                        return HeadFootController::uploadAsset($this->entityManager, $this->request->query->get('head_foot_image'));
                    }

                    /* -- page Maintenance -- */
                    elseif($this->request->query->get('action') === 'restore_database' && $this->request->request->get('hidden') === ''
                        && $this->request->files->has('uploaded_sql')){
                        return MaintenanceController::downloadSQL($entityManager, $request);
                    }
                    else{
                        // choix ici entre répondre en JSON ou par une redirection, choix du JSON pour pouvoir passer un message
                        return new JsonResponse(['success' => false, 'error' => 'bad parameters'], JsonResponse::HTTP_BAD_REQUEST); // code 400
                    }
                }

                /* -- formulaire HTML sans fichier -- */
                elseif($this->request->headers->get('Content-Type') === 'application/x-www-form-urlencoded'){
                    if($this->request->query->get('action') === 'delete_article' && $this->request->query->has('id')){
                        return ArticleController::deleteArticle($this->entityManager, $this->request); // version formulaire
                    }

                    /* -- nouvelle page -- */
                    elseif($this->request->request->get('page_name') !== null
                        && $this->request->request->get('page_name_path') !== null
                        && $this->request->request->get('page_location') !== null
                        && $this->request->request->get('page_description') !== null
                        && $this->request->request->get('new_page_hidden') === ''){
                        return PageManagementController::newPage($this->entityManager, $this->request->request);
                    }
                    
                    /* -- suppression d'une page -- */
                    elseif($this->request->request->get('page_id') !== null
                        && $this->request->request->get('submit_hidden') === ''){
                        return PageManagementController::deletePage($this->entityManager, $this->request->request->get('page_id'));
                    }


                    /* -- mode Modification d'une page -- */
                    // modification du chemins en snake_case
                    elseif($this->request->request->get('page_menu_path') !== null
                        && $this->request->request->get('page_id') !== null
                        && $this->request->request->get('page_name_path_hidden') === ''){
                        return PageManagementController::updatePageMenuPath($this->entityManager, $this->request->request->get('page_menu_path'));
                    }
                    // ajout d'un bloc dans une page
                    elseif($this->request->request->get('bloc_title') !== null
                        && $this->request->request->get('bloc_select') !== null
                        && $this->request->request->get('bloc_title_hidden') === ''){ // contrôle anti-robot avec input hidden
                        return PageManagementController::addBloc($this->entityManager, $this->request);
                    }
                    // suppression d'un bloc de page
                    elseif($this->request->request->get('delete_bloc_id') !== null
                        && $this->request->request->get('delete_bloc_hidden') === ''){ // contrôle anti-robot avec input hidden
                        return PageManagementController::deleteBloc($this->entityManager, $this->request);
                    }


                    /* -- page Menu et chemins -- */
                    // création d'une entrée de menu avec une URL
                    elseif($this->request->request->has("label_input") && $this->request->request->has("url_input") && $this->request->request->has("location")){
                        return MenuAndPathsController::newUrlMenuEntry($this->entityManager);
                    }
                    // suppression d'une entrée de menu avec une URL
                    elseif($this->request->request->has('delete') && $this->request->request->has('x') && $this->request->request->has('y')){ // 2 params x et y sont là parce qu'on a cliqué sur une image
                        return MenuAndPathsController::deleteUrlMenuEntry($this->entityManager);
                    }


                    /* -- page Mon compte -- */
                    elseif($this->request->query->get('action') === 'update_username'){
                        return UserController::updateUsername($this->entityManager);
                    }
                    elseif($this->request->query->get('action') === 'update_password'){
                        return UserController::updatePassword($this->entityManager);
                    }

                    /* -- page Maintenance -- */
                    elseif($this->request->query->get('action') === 'restore_database' && $this->request->get('hidden') === '' && $this->request->request->has('selected_sql')){
                        return MaintenanceController::handleBackupSelection($this->entityManager, $this->request);
                    }

                    // redirection page d'accueil
                    return new RedirectResponse((string)new URL(['error' => 'paramètres inconnus']));
                }

                // requêtes XMLHttpRequest
                elseif($this->request->isXmlHttpRequest()){
                    return new JsonResponse(['success' => false]); // noyer le poisson en laissant penser que le site gère les requêtes XHR
                }

                // POST admin ne matchant pas
                return new Response('bad parameters', Response::HTTP_BAD_REQUEST); // code 400
            }

            // POST non-admin ne matchant pas
            return new Response('bad parameters', Response::HTTP_BAD_REQUEST); // code 400
        }

        // méthode HTTP inconnue
        else{
            return new RedirectResponse((string)new URL(['error' => 'tu fais quoi là mec?']));
        }
    }
}