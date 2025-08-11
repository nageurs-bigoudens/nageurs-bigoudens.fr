<?php
// src/view/UserEditBuilder.php
//
// fonctionne avec UserController

declare(strict_types=1);

use App\Entity\Node;

class UserEditBuilder extends AbstractBuilder
{
	public function __construct(Node $node)
	{
		// pour éviter les arnaques
		if(!$_SESSION['admin'])
        {
            header('Location: ' . new URL);
            die;
        }

		$viewFile = self::VIEWS_PATH . $node->getName() . '.php';

        $error_messages = [
            'error_non_valid_captcha' => 'Erreur au test anti-robot, veuillez saisir un nombre entier.',
            'captcha_server_error' => 'captcha_server_error',
            
            'bad_login_or_password' => 'Mauvais identifiant ou mot de passe, veuillez réessayer.', // ne pas indiquer où est l'erreur
            'bad_solution_captcha' => 'Erreur au test anti-robot, veuillez réessayer.',
            'forbidden_characters' => 'Caractères interdits: espaces, tabulations, sauts CR/LF.',
            'same_username_as_before' => 'Nouveau nom identique au précédent.',
            'same_password_as_before' => 'Nouveau mot de passe identique au précédent.'
        ];

        $error_username = isset($_GET['error_login']) ? $error_messages[$_GET['error_login']] : '';
        $success_username = (isset($_GET['success_login']) && $_GET['success_login']) ? 'Identifiant modifié avec succès.' : '';
        $error_password = isset($_GET['error_password']) ? $error_messages[$_GET['error_password']] : '';
        $success_password = (isset($_GET['success_password']) && $_GET['success_password']) ? 'Mot de passe modifié avec succès.' : '';

		$captcha = new Captcha;
        $_SESSION['captcha'] = $captcha->getSolution(); // enregistrement de la réponse du captcha pour vérification

        $link_user_form = new URL(['action' => 'update_username']);
        isset($_GET['from']) ? $link_user_form->addParams(['from' => $_GET['from']]) : '';
        isset($_GET['id']) ? $link_user_form->addParams(['id' => $_GET['id']]) : '';

        $link_password_form = new URL(['action' => 'update_password']);
        isset($_GET['from']) ? $link_password_form->addParams(['from' => $_GET['from']]) : '';
        isset($_GET['id']) ? $link_password_form->addParams(['id' => $_GET['id']]) : '';

        $link_exit = new URL;
        isset($_GET['from']) ? $link_exit->addParams(['page' => $_GET['from'] ]) : '';
        isset($_GET['id']) ? $link_exit->addParams(['id' => $_GET['id']]) : '';

		ob_start();
        require $viewFile;
        $this->html = ob_get_clean(); // nouveau contenu
	}
}