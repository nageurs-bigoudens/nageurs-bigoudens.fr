<?php
// src/model/Model.php
//
// à l'occaz, faire des classes métiers: NodeModel = celle-ci
// puis PageModel, puis éventuellement UserModel, EmailModel (le calendrier a déjà EventDTO)

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\Page;
use App\Entity\Node;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class Model
{
	private EntityManager $entityManager;
    static public Menu $menu; // pour NavBuilder
    static public ?Path $page_path = null; // pour $current dans NavBuilder et pour BreadcrumbBuilder
	private Page $page;
	private ?Node $node;
    private Node $article;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
        $this->node = new Node; // instance mère "vide" ne possédant rien d'autre que des enfants
	}

    // à déplacer dans Path ou un truc comme ça?
    // couper Model en deux classes NodeModel et PageModel?
    public function makeMenuAndPaths(): void // lit la table "page"
    {
        self::$menu = new Menu($this->entityManager);
        self::$page_path = new Path();
        $this->page = self::$page_path->getLast();
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
	public function getWholePageData(Request $request): void // lit la table "node" + jointures
    {
        $id = CURRENT_PAGE === 'article' ? htmlspecialchars($request->query->get('id')) : '';

        if($id === ''){ // page "normale"
            // récupérer tous les noeuds sauf les articles
            $dql = "SELECT n FROM App\Entity\Node n WHERE n.name_node != 'new' AND n.name_node != 'post' AND (n.page = :page OR n.page IS null)";
            $bulk_data = $this->entityManager
                ->createQuery($dql)
                ->setParameter('page', $this->page)
                ->getResult();

            foreach($bulk_data as $parent_block){
                // groupes d'articles triés par bloc, permet de paginer par bloc                
                if(Blocks::hasPresentation($parent_block->getName())){ // = post_block ou news_block
                    $bulk_data = array_merge($bulk_data, $this->getNextArticles($parent_block, $request)[0]);
                }

                // emails
                if($parent_block->getName() === 'show_emails'){
                    $parent_block->getNodeData()->setEmails($this->getAllEmails());
                }
            }
        }
        else{ // page "article"
            $dql = 'SELECT n FROM App\Entity\Node n WHERE n.page = :page OR n.page IS null OR n.id_node = :id';
            $bulk_data = $this->entityManager
                ->createQuery($dql)
                ->setParameter('page', $this->page)
                ->setParameter('id', $id)
                ->getResult();
        }

        $this->makeNodeTree($bulk_data);
    }

    // récupération d'articles
    public function getNextArticles(Node $parent_block, Request $request): array
    {
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
        $limit = $parent_block->getNodeData()->getPaginationLimit(); // = 12 par défaut si = null en BDD
        $this->paginateWithCursor($qb, $parent_block, $request->query->get('last_article'));
        $result = $qb->getQuery()->getResult();

        // il reste des articles à récupérer SI on vient d'en récupérer trop
        // ET on gère le cas particulier de $limit <= 0
        $truncated = false;
        if(count($result) > $limit && $limit > 0){ // si nb résultat > limit > 0
            $truncated = true;
            array_pop($result); // compenser le $limit + 1 dans paginateWithCursor
        }
        
        return [$result, $truncated]; // besoin exceptionnel de retourner deux valeurs
    }

    private function paginateWithCursor(QueryBuilder $qb, Node $parent_block, ?string $last_article): void
    {
        //var_dump($last_article);
        $limit = $parent_block->getNodeData()->getPaginationLimit(); // = 12 par défaut si = null en BDD

        if($limit > 0){ // si 0 ou moins pas de pagination
            // nombres de "pages" d'articles
            $nb_pages = $this->getNumberOfPages($parent_block, $limit);
            $parent_block->getNodeData()->setNumberOfPages($nb_pages > 1 ? $nb_pages : 1);

            // adaptation de la requête
            if($parent_block->getName() === 'post_block'){
                $qb->andWhere('n.position > :last_position')
                   ->setParameter('last_position', empty($last_article) ? 0 : $last_article)
                   ->setMaxResults($limit + 1);
            }
            elseif($parent_block->getName() === 'news_block'){
                $cursor_start = $parent_block->getNodeData()->getChronoOrder() ? '1970-01-01' : '9999-12-31';
                $qb->andWhere($parent_block->getNodeData()->getChronoOrder() ? 'a.date_time > :date_time' : 'a.date_time < :date_time')
                    ->setParameter('date_time', empty($last_article) ? $cursor_start : $last_article)
                    ->setMaxResults($limit + 1);
            }
        }
    }

    // le Paginator de doctrine le fait aussi si on décidait de s'en servir
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
        foreach($bulk_data as $node){
            // premier niveau
            if($node->getParent() == null){
                $this->node->addChild($node);

                // spécifique page article
                if($node->getName() === 'main' && $this->page->getEndOfPath() == 'article'){
                    $main = $node;
                }
            }
            // autres niveaux
            else{
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
    public function findWhateverNode(string $field, string $value): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('n')
            ->from('App\Entity\Node', 'n')
            ->where("n.$field = :value") // avec le querybuilder, ce truc sale reste sécurisé
            ->setParameter('value', $value);
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        if($result === null){
            return false;
        }
        else{
            $this->node = $result;
            return true;
        }
    }
    public function getWhatever(string $class, string $field, string $value): array
    {
        // penser au entityManager "repository"
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('n')
            ->from($class, 'n')
            ->where("n.$field = :value")
            ->setParameter('value', $value);
        return $queryBuilder->getQuery()->getResult();
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

    private function getAllEmails(): array
    {
        $dql = 'SELECT e FROM App\Entity\Email e';
        return $this->entityManager
            ->createQuery($dql)
            //->setParameter('page', $this->page)
            ->getResult();
    }
    //private function getEmails(string $sender): array
}
