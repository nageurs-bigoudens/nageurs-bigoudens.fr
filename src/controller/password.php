<?php
// src/controller/password.php
//
// test mot de passe et captcha

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\User;

// exécutée dans installation.php à l'ouverture de chaque page
function existUsers(EntityManager $entityManager)
{
    // lecture
	$users = $entityManager->getRepository(User::class)->findAll();
	
	// cas particulier table vide
	if(count($users) === 0)
	{
		$_GET = [];
		$_SESSION['user'] = '';
		$_SESSION['admin'] = false;

		// création d'un utilisateur, puis rechargement de la page
		createPassword($entityManager);
	}
}


function createPassword(EntityManager $entityManager)
{
	// fonction exécutée à priori deux fois d'affilée: affichage puis traitement de la saisie

	// II - traitement
	unset($_SESSION['user']);

	$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
	$captcha = isset($_POST['captcha']) ? controlCaptchaInput($_POST['captcha']) : 0;

	$error = '';
	if(!isset($_POST['captcha'])) // page rechargée
	{
		//$error = '';
	}
	elseif($captcha == 0)
	{
		$error = 'error_non_valid_captcha';
	}
	elseif($captcha_solution == 0)
	{
		//$error = '';
	}
	elseif($captcha != $captcha_solution) // le test!
	{
		$error = 'bad_solution_captcha';
	}
	elseif(!isset($_POST['password']) || empty($_POST['password'])
		|| (!isset($_POST['login']) || empty($_POST['login'])))
	{
		$error = 'bad_login_or_password';
	}
	else
	{
		// -> caractères HTML dangereux supprimés
		$login = Security::secureString($_POST['login']);
		$password = Security::secureString($_POST['password']);
		
		// -> prévenir la validation par erreur d'une chaine "vide"
		$login = removeSpacesTabsCRLF($login);
		$password = removeSpacesTabsCRLF($password);

		// conformité
		if(isset($password) && isset($login)
			&& $password == $_POST['password'] && $login == $_POST['login'])
		{
			// enregistrement et redirection
			$password = password_hash($password, PASSWORD_DEFAULT);
			$user = new App\Entity\User($login, $password);
			$entityManager->persist($user);
			$entityManager->flush();
			
			header('Location: ' . new URL);
			exit();
		}
		else
		{
			$error = 'bad_password';
		}
	}
	
	// inséré dans $captchaHtml puis dans $formulaireNouveauMDP
	$captcha = createCaptcha();
	// enregistrement de la réponse du captcha pour vérification
	$_SESSION['captcha'] = $captcha[2]; // int


	// I - affichage
	$title = 'Bienvenue nageur bigouden';
	$subHeading = 'Veuillez choisir les codes que vous utiliserez pour gérer le site.';

	// même vue que la fonction changerMotDePasse()
	require('../src/view/password.php');

	echo($header);
	if($error != '')
	{
		sleep(1);
		echo($error_messages[$error]);
	}
	echo($formulaireNouveauMDP);
	echo($error_messages['forbidden_characters']);
	echo($footer);
	die;
}


function connect(LoginBuilder $builder, EntityManager $entityManager)
{
	// déjà connecté
	if($_SESSION['admin'])
	{
		header('Location: ' . new URL);
		die;
	}

	// II - traitement
	$_SESSION['user'] = '';
	$_SESSION['admin'] = false;

	$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
	$captcha = isset($_POST['captcha']) ? controlCaptchaInput($_POST['captcha']) : 0;

	$error = '';
	if(!isset($_POST['captcha'])) // page rechargée
	{
		//$error = '';
	}
	elseif($captcha == 0)
	{
		$error = 'error_non_valid_captcha';
	}
	elseif($captcha_solution == 0)
	{
		//$error = '';
	}
	elseif($captcha != $captcha_solution) // le test!
	{
		$error = 'bad_solution_captcha';
	}
	elseif(!isset($_POST['login']) || empty($_POST['login'])
		|| !isset($_POST['password']) || empty($_POST['password']))
	{
		$error = 'bad_password';
	}
	else // c'est OK
	{
		$login = $_POST['login'];
		$password = $_POST['password'];
		$user = getUser($login, $entityManager);

		// enregistrement et redirection
		if(!empty($user) && $login === $user->getLogin() && password_verify($password, $user->getPassword()))
		{
			session_start();
			$_SESSION['user'] = $login;
			$_SESSION['admin'] = true;
			$link = new URL(isset($_GET['from']) ? ['page' => $_GET['from']] : []);
			isset($_GET['id']) ? $link->addParams(['id' => $_GET['id']]) : '';
			header('Location: ' . $link);
			die;
		}
		else
		{
			$error = 'bad_password';
		}
	}

	// inséré dans $captchaHtml puis dans $formulaireNouveauMDP
	$captcha = createCaptcha();
	// enregistrement de la réponse du captcha pour vérification
	$_SESSION['captcha'] = $captcha[2]; // int

	// I - affichage
	$title = "Connexion";
	$subHeading = "Veuillez saisir votre identifiant (e-mail) et votre mot de passe.";

	require('../src/view/password.php');

	//$builder->addHTML($header);
	if($error != '')
	{
		sleep(1);
		$builder->addHTML($error_messages[$error]);
	}
	$builder->addHTML($formulaireConnexion);
	//$builder->addHTML($warning_messages['message_cookie']);
	$builder->addHTML($warning_messages['private_browsing']);
	$builder->addHTML($footer);

	//die;
}


function changePassword(EntityManager $entityManager)
{
	// fonction exécutée à priori deux fois d'affilée: affichage puis traitement de la saisie

	// OUT !!
	if(empty($_SESSION['user']) || !$_SESSION['admin'])
	{
		$_SESSION['user'] = '';
		$_SESSION['admin'] = false;
		header('Location: index.php');
		die;
	}

	// II - traitement
	$error = '';
	$success = false;
	if(empty($_POST)) // première fois ou page rechargée
	{
		//
	}
	elseif(!isset($_POST['login']) || empty($_POST['login'])
		|| !isset($_POST['old_password']) || empty($_POST['old_password'])
		|| !isset($_POST['new_password']) || empty($_POST['new_password']))
	{
		$error = 'bad_login_or_password';
	}
	else
	{
		// sécurisation de la saisie
		$new_password = Security::secureString($_POST['new_password']);
		$login = Security::secureString($_POST['login']);
		$old_password = Security::secureString($_POST['old_password']);

		// éviter d'enregistrer une chaîne vide
		$new_password = removeSpacesTabsCRLF($new_password);

		// tests de conformité
		if($login != $_POST['login'] || $old_password !=  $_POST['old_password'] || $new_password != $_POST['new_password'])
		{
			$error = 'forbidden_characters';
		}
		else
		{
			$user = getUser($login, $entityManager);

			if(password_verify($old_password, $user->getPassword()))
			{
				// enregistrement et redirection
				$new_password = password_hash($new_password, PASSWORD_DEFAULT);
				$user->setPassword($new_password);
				$entityManager->flush();
				$success = true;
			}
			else
			{
				$error = 'bad_password';
			}
		}
	}


	// I - affichage
	$title = "Nouveau mot de passe";
	$subHeading = "Veuillez vous identifier à nouveau puis saisir votre nouveau mot de passe.";

	require('../src/view/password.php');

	echo($header);
	if($error != '')
	{
		sleep(1); // sécurité TRÈS insuffisante à la force brute
		echo($error_messages[$error]);
	}
	elseif($success)
	{
		$success = false;
		echo($alertJSNewPassword);
		die;
	}
	echo($formulaireModifMDP);
	echo($footer);
	die;
}


function getUser(string $login, EntityManager $entityManager): ?User
{
	$users = $entityManager->getRepository('App\Entity\User')->findBy(['login' => $login]);
	
	if(count($users) === 0)
	{
		$_SESSION['user'] = '';
		$_SESSION['admin'] = false;
	}

	foreach($users as $user)
	{
		if($user->getLogin() === $login)
		{
			return $user;
		}
	}
	return null;
}


function disconnect(EntityManager $entityManager)
{
	// nettoyage complet
	$_SESSION = []; // mémoire vive
	session_destroy(); // fichier côté serveur
	setcookie('PHPSESSID', '', time() - 4200, '/'); // cookie de session
	$link = new URL(['page' => $_GET['page']]);
	isset($_GET['id']) ? $link->addParams(['id' => $_GET['id']]) : '';
	header('Location: ' . $link);
	die;
}


function createCaptcha(): array
{
	$a = rand(2, 9);
	$b = rand(2, 9);
	return array(toLettersFrench($a), toLettersFrench($b), $a * $b);
}

function toLettersFrench(int $number): string
{
	return match($number)
	{
		2 => 'deux',
		3 => 'trois',
		4 => 'quatre',
		5 => 'cinq',
		6 => 'six',
		7 => 'sept',
		8 => 'huit',
		9 => 'neuf',
		default => '', // erreur
	};
}

// on veut des chiffres
function controlCaptchaInput(string $captcha = '0'): int
{
    // $captcha est un POST donc une chaîne, '2.3' est acceptés
    // (int) supprime les décimales
    return (is_numeric($captcha) && $captcha == (int) $captcha) ? (int) $captcha : 0;
}