<?php
// src/view/ViewController.php
//
// génère le HTML avec des Builder

declare(strict_types=1);

use App\Entity\Node;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewController extends AbstractBuilder
{
    static public Node $root_node;

    public function __construct(){}

    public function buildView(Request $request, EntityManager $entityManager): Response
    {
        // accès au modèle
        $director = new Director($entityManager, true);
        $director->makeRootNode(htmlspecialchars($request->query->get('id') ?? ''));
        self::$root_node = $director->getNode();

        // mode modification d'une page activé
        if($_SESSION['admin'] && $request->query->has('page')
            && $request->query->has('action') && $request->query->get('action') === 'modif_page'
            && $request->query->get('page') !== 'connexion' && $request->query->get('page') !== 'article' && $request->query->get('page') !== 'nouvelle_page' && $request->query->get('page') !== 'menu_chemins'){
            // les contrôles de la 2è ligne devraient utiliser un tableau
            MainBuilder::$modif_mode = true;
        }

        // construction de la page
        $this->useChildrenBuilder(self::$root_node);

        return new Response($this->html, 200);
    }
}