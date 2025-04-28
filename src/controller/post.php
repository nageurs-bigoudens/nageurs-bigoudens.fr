<?php
// src/controller/post.php

declare(strict_types=1);

if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['admin'] === true)
{
    /* -- formulaires HTML -- */
    /*if(isset($_POST['from']) // page d'où vient la requête
        && isset($_POST)) // données
    {
        echo "requête envoyée en validant un formulaire";
    }*/

    /* -- requêtes AJAX -- */
    require '../src/controller/ajax.php';
}
