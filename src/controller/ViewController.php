<?php
// src/view/ViewController.php
//
// génère le HTML avec des Builder

declare(strict_types=1);

use App\Entity\Node;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewController extends AbstractBuilder // ViewController est aussi le premier Builder
{
    static public Node $root_node;

    public function __construct(){} // surcharge celui de AbstractBuilder

    public function buildView(EntityManager $entityManager, Request $request): Response
    {
        /* 1/ accès au modèle */
        $director = new Director($entityManager, true);
        $director->makeRootNode(htmlspecialchars($request->query->get('id') ?? ''));
        self::$root_node = $director->getNode();


        /* 2/ traitement de quelques paramètres */

        // mode modification d'une page activé
        if($_SESSION['admin']
            && $request->query->has('mode') && $request->query->get('mode') === 'page_modif'
            && !in_array(CURRENT_PAGE, ['article', 'nouvelle_page', 'menu_chemins', 'user_edit', 'connection']))
        {
            MainBuilder::$modif_mode = true;
        }

        // page article: mode création et erreurs d'id
        if(CURRENT_PAGE === 'article'){
            if($_SESSION['admin']){
                if(!$request->query->has('id')){
                    return new Response($this->html, 302);
                }
                else{
                    // mode création d'article
                    // l'id du bloc et 'from=' sont vérifiés dans ArticleController::editorSubmit
                    if($request->query->get('id')[0] === 'n' && $request->query->has('from') && !empty($request->query->get('from'))){
                        NewBuilder::$new_article_mode = true;
                    }
                    elseif(self::$root_node->getNodeByName('main')->getAdoptedChild() === null){ // id inconnu
                        return new Response($this->html, 302);
                    }
                }
            }
            elseif($request->query->get('id')[0] === 'n'){ // accès page nouvelle article interdit sans être admin
                return new Response($this->html, 302);
            }
        }


        /* 3/ construction de la page avec builders et vues */
        $this->useChildrenBuilder(self::$root_node);

        return new Response($this->html, 200);
    }
}