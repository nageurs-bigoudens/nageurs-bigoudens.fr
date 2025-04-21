<?php
// src/modele/Position.php
//
// pour Node et Page

declare(strict_types=1);

trait Position
{
	public function sortChildren(bool $reposition = false): void
    {
        // ordre du tableau des enfants
        // inefficace quand des noeuds ont la même position
        
        // tri par insertion avant affichage
        for($i = 1; $i < count($this->children); $i++)
        {
            $tmp = $this->children[$i];
            $j = $i - 1;

            // Déplacez les éléments du tableau qui sont plus grands que la clé
            // à une position devant leur position actuelle
            while ($j >= 0 && $this->children[$j]->getPosition() > $tmp->getPosition()) {
                $this->children[$j + 1] = $this->children[$j];
                $j = $j - 1;
            }
            $this->children[$j + 1] = $tmp;
        }
        
        foreach ($this->children as $child) {
            if (count($child->children) > 0) {
                $child->sortChildren($reposition);
            }
        }

        // nouvelles positions (tableau $children => BDD)
        if($reposition){
            $i = 1;
            foreach($this->children as $child){
                $child->setPosition($i);
                $i++;
            }
        }
    }

    /*private function sortChildren(): void
    {
        $iteration = count($this->children);
        while($iteration > 1)
        {
            for($i = 0; $i < $iteration - 1; $i++)
            {
                if($this->children[$i]->getPosition() > $this->children[$i + 1]->getPosition())
                {
                    $tmp = $this->children[$i];
                    $this->children[$i] = $this->children[$i + 1];
                    $this->children[$i + 1] = $tmp;
                }
            }
            $iteration--;
        }
    }*/
}