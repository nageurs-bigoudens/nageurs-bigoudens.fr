<?php
// src/controller/Director.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\Page;
use App\Entity\Node;
//use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class Director
{
	private EntityManager $entityManager;
    static public Menu $menu_data; // pour NavBuilder
    static public ?Path $page_path = null; // pour $current dans NavBuilder et pour BreadcrumbBuilder
	private Page $page;
	private ?Node $node;
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
	public function getWholePageData(Request $request): void
    {
        $id = CURRENT_PAGE === 'article' ? htmlspecialchars($request->query->get('id')) : '';

        if($id === '') // page "normale"
        {
            // tous les noeuds sauf les articles, tri par page
            $dql = "SELECT n FROM App\Entity\Node n WHERE n.name_node != 'new' AND n.name_node != 'post' AND (n.page = :page OR n.page IS null)";
            $bulk_data = $this->entityManager
                ->createQuery($dql)
                ->setParameter('page', $this->page)
                ->getResult();

            // groupes d'articles triés par bloc, permet de paginer par bloc
            foreach($bulk_data as $parent_block){
                if(Blocks::hasPresentation($parent_block->getName())){ // = post_block ou news_block
                    $qb = $this->entityManager->createQueryBuilder();
                    $qb->select('n')
                       ->from('App\Entity\Node', 'n')
                       ->where('n.parent = :parent')
                       ->setParameter('parent', $parent_block);

                    if($parent_block->getName() === 'post_block'){
                        $qb->orderBy('n.position');
                    }
                    elseif($parent_block->getName() === 'news_block'){
                        $qb->join('n.article', 'a');
                        if($parent_block->getNodeData()->getChronoOrder() ?? false){ // ordre antichrono par défaut
                            $qb->orderBy('a.date_time', 'ASC');
                        }
                        else{
                            $qb->orderBy('a.date_time', 'DESC');
                        }
                    }

                    // pagination
                    $limit = $parent_block->getNodeData()->getPaginationLimit() ?? 0; // 0 par défaut = pas de pagination, sinon 12 rend bien avec des grilles de 2, 3 ou 4 colonnes
                    if($limit > 0){
                        //$this->paginateWithCursor($qb, $request->query->get('last_position') ?? 0, $limit);
                        $qb->andWhere('n.position > :last_position')
                           ->setParameter('last_position', $request->query->get('last_position') ?? 0)
                           ->setMaxResults($limit);

                        $nb_pages = $this->getNumberOfPages($parent_block, $limit); // nombres de "pages" d'articles
                        if($nb_pages > 1){
                            //$parent_block->setNumberOfPages($nb_pages); // => navigation en HTML
                        }
                    }

                    $bulk_data = array_merge($bulk_data, $qb->getQuery()->getResult());
                }
            }
        }
        else // page "article"
        {
            $dql = 'SELECT n FROM App\Entity\Node n WHERE n.page = :page OR n.page IS null OR n.id_node = :id';
            $bulk_data = $this->entityManager
                ->createQuery($dql)
                ->setParameter('page', $this->page)
                ->setParameter('id', $id)
                ->getResult();
        }
        $this->makeNodeTree($bulk_data);
    }

    /*private function paginateWithCursor(QueryBuilder $qb, int $last_position = 0, int $limit = 0): void
    {
        $qb->andWhere('n.position > :last_position')
           ->setParameter('last_position', $last_position)
           ->setMaxResults($limit);
    }*/

    // requête à part n'alimentant pas $bulk_data
    // fonctionnalité offerte par le Paginator de doctrine si on décidait de s'en servir
    private function getNumberOfPages(Node $parent_block, int $limit): int
    {
        $dql = 'SELECT COUNT(n.id_node) FROM App\Entity\Node n WHERE n.parent = :parent';
        $nb_articles = $this->entityManager
            ->createQuery($dql)
            ->setParameter('parent', $parent_block)
            ->getSingleScalarResult();
        return (int)ceil($nb_articles / $limit); // que PHP fasse une division non euclidienne (pas comme en C) nous arrange ici
    }

    private function makeNodeTree(array $bulk_data): void // $bulk_data = tableau de Node
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
                if($this->page->getEndOfPath() == 'article'){
                    if($node->getName() === 'new'){
                        $new = $node;
                    }
                }
            }
        }
        if(isset($new)){
            $main->setAdoptedChild($new);
        }
    }

    // le basique
    public function findNodeById(int $id): bool
    {
        $this->node = $this->entityManager->find('App\Entity\Node', $id);
        return $this->node === null ? false : true;
    }

    // récupération d'un article pour modification
    public function makeArticleNode(string $id = '', bool $get_section = false): bool
    {
        if($get_section){
            $dql = 'SELECT n, p FROM App\Entity\Node n LEFT JOIN n.parent p WHERE n.id_node = :id';
        }
        else{
            $dql = 'SELECT n FROM App\Entity\Node n WHERE n.id_node = :id';
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
