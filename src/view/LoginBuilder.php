<?php
// src/view/LoginBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class LoginBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        // déjà connecté?
        if($_SESSION['admin'])
        {
            header('Location: ' . new URL);
            die;
        }

        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';

        $captcha = new Captcha;
        $_SESSION['captcha'] = $captcha->getSolution(); // enregistrement de la réponse du captcha pour vérification
        
        //$this->html .= $header;

        $error_messages = [
            'error_non_valid_captcha' => 'Erreur au test anti-robot, veuillez saisir un nombre entier.',
            'captcha_server_error' => 'captcha_server_error',
            'bad_solution_captcha' => 'Erreur au test anti-robot, veuillez réessayer.',
            'bad_login_or_password' => 'Mauvais identifiant ou mot de passe, veuillez réessayer.', // ne pas indiquer où est l'erreur
            'forbidden_characters' => 'Caractères interdits: espaces, tabulations, sauts CR/LF.'
        ];

        $link_form = new URL(['action' => 'connection']);
        isset($_GET['from']) ? $link_form->addParams(['from' => $_GET['from']]) : '';
        isset($_GET['id']) ? $link_form->addParams(['id' => $_GET['id']]) : '';

        $link_exit = new URL;
        isset($_GET['from']) ? $link_exit->addParams(['page' => $_GET['from'] ]) : '';
        isset($_GET['id']) ? $link_exit->addParams(['id' => $_GET['id']]) : '';

        $error = isset($_GET['error']) ? $error_messages[$_GET['error']] : '';

        ob_start();
        require $viewFile;
        $this->html = ob_get_clean(); // nouveau contenu

        //$this->html .= <p style='color: red;'>Ce site utilise un cookie « obligatoire » lorsque vous êtes connecté ainsi que sur cette page.<br>Il sera supprimé à votre déconnexion ou dès que vous aurez quitté le site.</p>;
        
        //$this->html .= $footer;
    }
}
