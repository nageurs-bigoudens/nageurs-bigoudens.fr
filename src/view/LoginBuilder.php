<?php
// src/view/LoginBuilder.php

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
