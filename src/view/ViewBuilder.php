<?php
// src/view/ViewBuilder.php
//
// appelle les autres Builder

declare(strict_types=1);

use App\Entity\Node;

class ViewBuilder extends AbstractBuilder
{
    static public Node $root_node;
    public function __construct(Node $root_node)
    {
        self::$root_node = $root_node;
        $this->useChildrenBuilder($root_node);
    }
}
