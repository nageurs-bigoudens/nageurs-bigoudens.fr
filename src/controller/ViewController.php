<?php
// src/controller/ViewController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ViewController
{
    static function getWebPage(EntityManager $entityManager, Request $request): Response
    {
        /* 1/ 1er contrôle des paramètres */

        // mode modification d'une page
        if(IS_ADMIN
            && $request->query->has('mode') && $request->query->get('mode') === 'page_modif'
            && !in_array(CURRENT_PAGE, ['article', 'new_page', 'menu_paths', 'user_edit', 'connection']))
        {
            MainBuilder::$modif_mode = true;
        }
        // page article: mode création et erreurs d'id
        if(CURRENT_PAGE === 'article'){
            if(IS_ADMIN){
                if(!$request->query->has('id')){
                    return new RedirectResponse((string)new URL(['page' => $request->query->get('from') ?? '']));
                }
                else{
                    // mode création d'article
                    // l'id du bloc et 'from=' sont vérifiés dans ArticleController::editorSubmit
                    if($request->query->get('id')[0] === 'n' && $request->query->has('from') && !empty($request->query->get('from'))){
                        NewBuilder::$new_article_mode = true;
                    }
                }
            }
            elseif($request->query->get('id')[0] === 'n'){ // accès page nouvelle article interdit sans être admin
                return new RedirectResponse((string)new URL(['page' => $request->query->get('from') ?? '']));
            }
        }
        // pas de else, l'id dans l'URL n'a pas d'effet ailleurs


        /* 2/ accès au modèle */
        $model = new Model($entityManager);
        $model->makeMenuAndPaths();
        $model->getWholePageData($request);


        /* 3/ 2ème contrôle des paramètres avec les données récupérées */

        // article non trouvé en BDD
        if(CURRENT_PAGE === 'article' && !IS_ADMIN && $model->getNode()->getNodeByName('main')->getAdoptedChild() === null){
            return new RedirectResponse((string)new URL(['page' => $request->query->get('from') ?? '']));
        }


        /* 4/ construction de la page avec builders et vues */
        return new Response((new ViewDirector)->buildHTML($model->getNode()));
    }
}