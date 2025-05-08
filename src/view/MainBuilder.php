<?php
// src/view/MainBuilder.php

declare(strict_types=1);

use App\Entity\Article;
use App\Entity\Node;

class MainBuilder extends AbstractBuilder
{
    static bool $modif_mode = false;

    public function __construct(Node $node)
    {
        $this->html .= "<main>\n";

        // cas particulier de la page article où l'article est greffé sur main
        if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
            // pas censé arriver
            if(!isset($_GET['id'])){
                header('Location: ' . new URL);
                die;
            }

            if($node->getAdoptedChild() == null){
                $date = new \DateTime;
                $article = new Article('', $date);
                $new = new Node('new', 'i' . (string)$date->getTimestamp(), [], 0, null, null, $article);
            }
            else{
                $new = $node->getAdoptedChild();
            }
            //$builder_name = $this->snakeToPascalCase($new->getName()) . 'Builder';
            $builder_name = 'NewBuilder';
            $builder = new $builder_name($new);
            $this->html .= $builder->render();
        }
        else{
            // si action = "modif_page", affiche des commandes pour modifier
            if($_SESSION['admin'] && self::$modif_mode){
                // ajouter un contrôle du champ in_menu
                $this->viewEditBlocks($node);
            }

            $this->useChildrenBuilder($node);
        }

        $this->html .= "</main>\n";
    }

    private function viewEditBlocks($node): void
    {
        // blocs disponibles
        $blocs = ['Blog', 'Grille', 'Calendrier', 'Galerie']; // générer ça dynamiquement!
        $blocs_true_names = ['blog', 'grid', 'calendar', 'galery'];

        $options = '';
        for($i = 0; $i < count($blocs); $i++){
            $options .= '<option value= "' . $blocs_true_names[$i] . '">' . $blocs[$i] . "</option>\n";
        }

        // blabla
        /*$this->html .= '<aside class="modif_page_explanations">
            <p>Modification de la structure d\'une page:</p>
            <div>
                <p><img></p>
                <p><img></p>
            </div>
        </aside>' . "\n";*/

        // ajout d'un nouveau bloc
        $this->html .= '<div class="new_bloc">
            <p>Ajouter un bloc dans la page:</p>
            <form method="post" action="' . new URL(['page' => CURRENT_PAGE]) . '">
                <p><label for="bloc_title">Titre</label>
                <input type="text" id="bloc_title" name="bloc_title" required></p>
                <p><label for="bloc_select">Type</label>
                <select id="bloc_select" name="bloc_select" required>'
                . $options . 
                '</select>
                <input type="submit" value="Valider"></p>
            </form>
        </div>' . "\n";
        foreach($node->getChildren() as $child_node){
            //$this->html .=
        }
    }
}
