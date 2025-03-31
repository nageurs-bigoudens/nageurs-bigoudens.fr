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
    static public Path $page_path; // pour BreadcrumbBuilder
	private Page $page;
	private Node $root_node;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
        self::$menu_data = new Menu($entityManager); // Menu est un modèle mais pas une entité
        self::$page_path = new Path();
        $this->page = self::$page_path->getLast();
        $this->root_node = new Node; // instance mère "vide" ne possédant rien d'autre que des enfants
	}

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
        $this->feedObjects($bulk_data);
    }

    public function makeArticleNode(string $id = ''): bool
    {
        $bulk_data = $this->entityManager
            ->createQuery('SELECT n FROM App\Entity\Node n WHERE n.article_timestamp = :id')
            ->setParameter('id', $id)
            ->getResult();

        if(count($bulk_data) === 0){
            return false;
        }
        
        $this->root_node = $bulk_data[0];
        return true;
    }

    private function feedObjects(array $bulk_data): void // $bulk_data = tableau de Node
    {
        // puis on les range
        // (attention, risque de disfonctionnement si les noeuds de 1er niveau ne sont pas récupérés en 1er dans la BDD)
        foreach($bulk_data as $node)
        {
            // premier niveau
            if($node->getParent() == null)
            {
                $this->root_node->addChild($node);

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
            $main->setTempChild($new);
        }
    }

	public function getRootNode(): Node
	{
        return $this->root_node;
    }
}
