<?php
// src/view/MainBuilder.php

declare(strict_types=1);

use App\Entity\Article;
use App\Entity\Node;
use App\Entity\Presentation;

class MainBuilder extends AbstractBuilder
{
    static public bool $modif_mode = false;

    public function __construct(Node $node)
    {
        $this->html .= "<main>\n";

        // page article: cas particulier où l'article est greffé sur main
        if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
            // pas censé arriver
            if(!isset($_GET['id'])){
                header('Location: ' . new URL);
                die;
            }

            // nouvel article
            if($node->getAdoptedChild() == null){
                $date = new \DateTime;
                $article = new Article('', $date);
                $new = new Node('new', [], 0, null, null, $article);
            }
            // modification
            else{
                $new = $node->getAdoptedChild();
            }
            //$builder_name = $this->snakeToPascalCase($new->getName()) . 'Builder';
            $builder_name = 'NewBuilder';
            $builder = new $builder_name($new);
            $this->html .= $builder->render();
        }
        else{
            // si action = "modif_page", affiche des commandes supplémentaires
            if($_SESSION['admin'] && self::$modif_mode){
                // ajouter un contrôle du champ in_menu
                $this->viewEditBlocks($node);
            }

            // dans tous les cas
            $this->useChildrenBuilder($node);
        }

        $this->html .= "</main>\n";
    }

    // mode modification de page uniquement
    private function viewEditBlocks($node): void
    {
        $options = '';
        foreach(Blocks::$blocks as $key => $value){
            $options .= '<option value= "' . $key . '">' . $value . "</option>\n";
        }

        $head_node = null;
        foreach(ViewController::$root_node->getChildren() as $first_level_node){
            if($first_level_node->getName() === 'head'){
                $head_node = $first_level_node; // normalement c'est le 1er enfant
                break;
            }
        }
        
        // ceci pourrait être déplacé au début des blocs
        $bloc_edit = '';
        foreach($node->getChildren() as $child_node){
            ob_start();
            require self::VIEWS_PATH . 'modify_block.php';
            $bloc_edit .= ob_get_clean();
        }

        ob_start();
        require self::VIEWS_PATH . 'modify_page.php';
        $this->html .= ob_get_clean();
    }

    // utilisée dans modify_block.php
    private function makePresentationOptions(string $presentation): string
    {
        $options = '';
        foreach(Blocks::$presentations as $key => $value){
            $options .= '<option value="' . $key . '" ' . ($presentation === $key ? 'selected' : '') . '>' . $value . '</option>';
        }
        return $options;
    }
}