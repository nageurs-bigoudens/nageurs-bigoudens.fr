<?php
// src/view/LoginBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class LoginBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        global $entityManager;

        // une classe Password ce serait pas mal!!
        connect($this, $entityManager);
    }
}
