<?php
// src/modele/Position.php
//
// pour Node et Page

declare(strict_types=1);

trait Position
{
    // tri par insertion du tableau des enfants
	public function sortChildren(bool $reindexation = false): void
    {
        for($i = 1; $i < count($this->children); $i++)
        {
            $tmp = $this->children[$i];
            $j = $i - 1;

            // Déplacez les éléments du tableau qui sont plus grands que la clé à une position devant leur position actuelle
            while($j >= 0 && $this->children[$j]->getPosition() > $tmp->getPosition()) {
                $this->children[$j + 1] = $this->children[$j];
                $j--;
            }
            $this->children[$j + 1] = $tmp;
        }
        
        foreach($this->children as $child) {
            if(count($child->children) > 0) {
                $child->sortChildren($reindexation);
            }
        }

        if($reindexation){
            $this->reindexPositions();
        }
    }

    // nouvelles positions (tableau $children => BDD)
    public function reindexPositions(): void
    {
        $i = 1;
        foreach($this->children as $child){
            $child->setPosition($i);
            $i++;
        }
    }
}