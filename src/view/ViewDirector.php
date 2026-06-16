<?php
// src/view/ViewDirector.php
//
// génère le HTML avec des Builder

declare(strict_types=1);

use App\Entity\Node;

class ViewDirector extends AbstractBuilder // ViewDirector est le premier Builder
{
    public Node $root_node;

    public function __construct(){} // surcharge celui de AbstractBuilder

    public function buildHTML(Node $root_node): string
    {
        $this->root_node = $root_node;
        $this->useChildrenBuilder($this->root_node);

        if(isset($_SESSION['flash_message'])){
            $this->html .= '<script>window.flash_message = "' . $_SESSION['flash_message'] . '";</script>';
            unset($_SESSION['flash_message']);
        }

        return $this->html;
    }
}