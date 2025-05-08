<?php
// src/controller/Director.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\Page;
use App\Entity\Node;

class Director
{
	private EntityManager $entityManager;
    static public Menu $menu_data; // pour NavBuilder
    static public ?Path $page_path = null; // pour $current dans NavBuilder et pour BreadcrumbBuilder
	private Page $page;
	private Node $node;
    private Node $article;

	public function __construct(EntityManager $entityManager, bool $get_menu = false)
	{
		$this->entityManager = $entityManager;
        if($get_menu){
            self::$menu_data = new Menu($entityManager);
            self::$page_path = new Path();
            $this->page = self::$page_path->getLast();
        }
        $this->node = new Node; // instance mère "vide" ne possédant rien d'autre que des enfants
	}

    public function getNode(): Node
    {
        return $this->node;
    }
    public function getArticleNode(): Node
    {
        return $this->article;
    }

    // affichage d'une page ordinaire
	public function makeRootNode(string $id = ''): void
    {
        // on récupère toutes les entrées
        $dql = 'SELECT n FROM App\Entity\Node n WHERE n.page = :page OR n.page IS null';
        if($id == '')
        {
            $bulk_data = $this->entityManager
                ->createQuery($dql)
                ->setParameter('page', $this->page)
                ->getResult();
        }
        else // avec $_GET['id'] dans l'URL
        {
            $dql .= ' OR n.article_timestamp = :id';
            $bulk_data = $this->entityManager
                ->createQuery($dql)
                ->setParameter('page', $this->page)
                ->setParameter('id', $id)
                ->getResult();
        }
        $this->feedRootNodeObjects($bulk_data);
    }

    private function feedRootNodeObjects(array $bulk_data): void // $bulk_data = tableau de Node
    {
        // puis on les range
        // (attention, risque de disfonctionnement si les noeuds de 1er niveau ne sont pas récupérés en 1er dans la BDD)
        foreach($bulk_data as $node)
        {
            // premier niveau
            if($node->getParent() == null)
            {
                $this->node->addChild($node);

                // spécifique page article
                if($node->getName() === 'main' && $this->page->getEndOfPath() == 'article'){
                    $main = $node;
                }
            }
            // autres niveaux
            else
            {
                $node->getParent()->addChild($node);

                // spécifique page article
                if($node->getName() === 'new' && $this->page->getEndOfPath() == 'article'){
                    $new = $node;
                }
            }
        }
        if(isset($new)){
            $main->setAdoptedChild($new);
        }
    }

    // le basique
    public function findNodeById(int $id): void
    {
        $this->node = $this->entityManager->find('App\Entity\Node', $id);
    }

    // récupération d'un article pour modification
    public function makeArticleNode(string $id = '', bool $get_section = false): bool
    {
        if($get_section){
            $dql = 'SELECT n, p FROM App\Entity\Node n LEFT JOIN n.parent p WHERE n.article_timestamp = :id';
        }
        else{
            $dql = 'SELECT n FROM App\Entity\Node n WHERE n.article_timestamp = :id';
        }
        // n est l'article et p son $parent
        $bulk_data = $this->entityManager
            ->createQuery($dql)
            ->setParameter('id', $id)
            ->getResult();

        if(count($bulk_data) === 0){
            return false;
        }

        if($get_section){
            $this->article = $bulk_data[0];
            $this->findNodeById($bulk_data[0]->getParent()->getId());
            $this->makeSectionNode();
        }
        else{
            $this->article = $bulk_data[0];
        }

        return true;
    }

    // récupération des articles d'un bloc <section> à la création d'un article
    public function makeSectionNode(): bool
    {
        $bulk_data = $this->entityManager
            ->createQuery('SELECT n FROM App\Entity\Node n WHERE n.parent = :parent')
            ->setParameter('parent', $this->node)
            ->getResult();

        foreach($bulk_data as $article){
            $this->node->addChild($article); // pas de flush, on ne va pas écrire dans la BDD à chaque nouvelle page
        }
        return true;
    }

    public function findUniqueNodeByName(string $name): void // = unique en BDD, donc sans "page" associée
    {
        $bulk_data = $this->entityManager
            ->createQuery('SELECT n FROM App\Entity\Node n WHERE n.name_node = :name')
            ->setParameter('name', $name)
            ->getResult();
        $this->node = $bulk_data[0];
    }

    public function findItsChildren(): void
    {
        $bulk_data = $this->entityManager
            ->createQuery('SELECT n FROM App\Entity\Node n WHERE n.parent = :parent AND n.page = :page')
            ->setParameter('parent', $this->node)
            ->setParameter('page', $this->page)
            ->getResult();
        foreach($bulk_data as $child){
            $this->node->addChild($child);
        }
    }
}
