<?php
// src/controller/Path.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\Page;

class Path extends Page
{
	private array $current_page = []; // tableau d'objets Page

	public function __construct()
	{
        $path_array = explode('/', CURRENT_PAGE);
        try{
        	$this->findPage(Director::$menu_data, $path_array); // remplit $this->current_page
        }
		catch(Exception $e){}
		/*echo "nb d'autres pages: " . count(Director::$menu_data->getOtherPages()) . '<br>';
		echo 'longueur du chemin: ' . count($this->current_page) . '<br>';
		foreach($this->current_page as $current){
			echo $current->getEndOfPath() . ' ';
		}
		die;*/
	}

	// produit un tableau de Page en comparant le chemin demandé avec les données dans Menu
	// succès => une exception est lancée pour sortir des fonctions imbriquées
	// echec => redirection vers la page erreur 404
	private function findPage(Page|Menu $menu, array $path_array)
	{
		// recherche dans les autres pages
		if($menu instanceof Menu){
			foreach($menu->getOtherPages() as $page)
			{
				if($path_array[0] === $page->getEndOfPath())
				{
					$this->current_page[] = $page;
					throw new Exception();
				}
			}
		}
		// recherche dans le menu
		foreach($menu->getChildren() as $page)
		{
			if($path_array[0] === $page->getEndOfPath())
			{
				$this->current_page[] = $page;
				if(count($path_array) > 1)
				{
					array_shift($path_array); // $this->path_array n'est pas modifié, un tableau PHP est passé à une fonction par copie
					$this->findPage($page, $path_array);
				}
				else{
					throw new Exception(); // sortir de tous les findPage() en même temps
				}
			}
		}
		// rien trouvé
		URL::setPath('erreur404.html');
    	header('Location: '. new URL);
    	die;
	}

	public function getString(): string
    {
    	$path_string = "";
    	foreach($this->current_page as $one_page){
    		$path_string .= $one_page->getEndOfPath() . '/';
    	}
        return rtrim($path_string, '/');
    }
    public function getArray(): array
    {
    	return $this->current_page;
    }

    // c'est là qu'on est quoi
    public function getLast(): Page
    {
    	return $this->current_page[count($this->current_page) - 1];
    }
}