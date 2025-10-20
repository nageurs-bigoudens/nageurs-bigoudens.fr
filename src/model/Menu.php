<?php
// src/model/Menu.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\Page;
use Doctrine\Common\Collections\ArrayCollection;

class Menu extends Page
{
	private EntityManager $entityManager;
    private array $other_pages = []; // pages n'apparaissant pas dans le menu

	public function __construct(EntityManager $entityManager){
		$this->children = new ArrayCollection();

		$bulk_data = $entityManager
            ->createQuery('SELECT n FROM App\Entity\Page n WHERE n.parent IS null') // :Doctrine\ORM\Query
            ->getResult(); // :array de Page

        if(count($bulk_data) === 0){
            fillStartingDatabase($entityManager); // => installation.php
        }

        foreach($bulk_data as $first_level_entries){
            // dans le menu
            if($first_level_entries->isInMenu()){
                $this->addChild($first_level_entries);
            }
            // autres pages
            else{
                // attention, seul le premier élément du chemin est pris en compte
                $this->other_pages[] = $first_level_entries;
            }
        }

        foreach($this->getChildren() as $page){
        	$page->fillChildrenPagePath();
        }
	}

    public function getOtherPages(): array
    {
        return $this->other_pages;
    }
}