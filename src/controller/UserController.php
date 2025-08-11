<?php
// src/controller/PasswordController.php
//
// test mot de passe et captcha

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\Log;

class UserController
{
	static public function existUsers(EntityManager $entityManager): bool
	{
	    // optimiser ça si possible, on veut juste savoir si la table est vide ou non
		$users = $entityManager->getRepository(User::class)->findAll();
		
		if(count($users) === 0) // table vide
		{
			$_SESSION['user'] = '';
			$_SESSION['admin'] = false;

			return false;
		}
		else{
			return true;
		}
	}

	static public function createUser(EntityManager $entityManager)
	{
		// test mauvais paramètres
		if(!isset($_POST['login']) || empty($_POST['login'])
        || !isset($_POST['password']) || empty($_POST['password'])
        || !isset($_POST['password_confirmation']) || empty($_POST['password_confirmation'])
        || !isset($_POST['create_user_hidden']) || !empty($_POST['create_user_hidden']))
		{
			header('Location: ' . new URL);
			die;
		}

		unset($_SESSION['user']);

		$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
		$captcha_try = isset($_POST['captcha']) ? Captcha::controlInput($_POST['captcha']) : 0;
		unset($_SESSION['captcha']);
		
		$error = '';
		if($captcha_try == 0)
		{
			$error = 'error_non_valid_captcha';
		}
		elseif($captcha_solution == 0) // ne peut pas arriver, si?
		{
			$error = 'captcha_server_error';
		}
		elseif($captcha_try != $captcha_solution) // le test!
		{
			$error = 'bad_solution_captcha';
		}
		elseif($_POST['password'] !== $_POST['password_confirmation'])
		{
			$error = 'different_passwords';
		}
		else
		{
			$login = self::removeSpacesTabsCRLF(htmlspecialchars($_POST['login']));
			$password = self::removeSpacesTabsCRLF(htmlspecialchars($_POST['password']));
			
			// self::removeSpacesTabsCRLF prévient la validation par erreur d'une chaine "vide"

			// conformité
			if(!empty($password) && !empty($login)
				&& $password === $_POST['password'] && $login === $_POST['login'])
			{
				// enregistrement et redirection
				$password = password_hash($password, PASSWORD_DEFAULT);
				$user = new App\Entity\User($login, $password);
				$entityManager->persist($user);
				$entityManager->flush();
				
				header('Location: ' . new URL);
				die;
			}
			else{
				$error = 'forbidden_characters';
			}
		}

		$url = empty($error) ? new URL : new URL(['error' => $error]);
		header('Location: ' . $url);
		die;
	}

	static public function connect(EntityManager $entityManager): void
	{
		if($_SESSION['admin']) // déjà connecté?
		{
			header('Location: ' . new URL);
			die;
		}

		$_SESSION['user'] = '';
		$_SESSION['admin'] = false;

		$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
		$captcha_try = isset($_POST['captcha']) ? Captcha::controlInput($_POST['captcha']) : 0;
		unset($_SESSION['captcha']);

		$error = '';
		if($captcha_try == 0)
		{
			$error = 'error_non_valid_captcha';
		}
		elseif($captcha_solution == 0) // pas censé se produire
		{
			$error = 'captcha_server_error';
		}
		elseif($captcha_try != $captcha_solution) // le test!
		{
			$error = 'bad_solution_captcha';
		}
		elseif(!isset($_POST['login']) || empty($_POST['login'])
			|| !isset($_POST['password']) || empty($_POST['password']))
		{
			$error = 'bad_login_or_password';
		}
		else // c'est OK
		{
			$login = trim($_POST['login']);
			$password = trim($_POST['password']);
			$user = self::getUser($login, $entityManager);

			// enregistrement et redirection
			if(!empty($user) && $login === $user->getLogin() && password_verify($password, $user->getPassword()))
			{
				$log = new Log(true);
				$entityManager->persist($log);
				$entityManager->flush();
				
				// protection fixation de session, si l'attaquant crée un cookie de session, il est remplacé
				session_regenerate_id(true);

				$_SESSION['user'] = $login;
				$_SESSION['admin'] = true;

				$url = new URL(isset($_GET['from']) ? ['page' => $_GET['from']] : []);
				isset($_GET['id']) ? $url->addParams(['id' => $_GET['id']]) : '';
				header('Location: ' . $url);
				die;
			}
			else
			{
				$log = new Log(false);
				$entityManager->persist($log);
				$entityManager->flush();
				$error = 'bad_login_or_password';
			}
		}

		// tous les cas sauf connexion réussie
		sleep(1); // défense basique à la force brute
		$url = new URL(isset($_GET['from']) ? ['page' => 'connexion', 'from' => $_GET['from']] : []);
		isset($_GET['id']) ? $url->addParams(['id' => $_GET['id']]) : '';
		!empty($error) ? $url->addParams(['error' => $error]) : '';
		header('Location: ' . $url);
		die;
	}

	static public function disconnect(): void
	{
		// nettoyage complet
		$_SESSION = []; // mémoire vive
		session_destroy(); // fichier côté serveur
		setcookie('PHPSESSID', '', time() - 86400, '/'); // cookie de session

		// retour même page
		$url = new URL;
		isset($_GET['from']) ? $url->addParams(['page' => $_GET['from']]) : '';
		isset($_GET['id']) ? $url->addParams(['id' => $_GET['id']]) : '';
		header('Location: ' . $url);
		die;
	}

	static public function updateUsername(EntityManager $entityManager): void
	{
		if(!$_SESSION['admin']) // superflux, fait dans le routeur
		{
			self::disconnect();
		}

		$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
		$captcha_try = isset($_POST['captcha']) ? Captcha::controlInput($_POST['captcha']) : 0;
		unset($_SESSION['captcha']);

		$url = new URL(['page' => 'user_edit']);
		isset($_GET['from']) ? $url->addParams(['from' => $_GET['from']]) : null;

		$error = '';
		if(!isset($_POST['old_login']) || empty($_POST['old_login'])
			|| !isset($_POST['password']) || empty($_POST['password'])
			|| !isset($_POST['new_login']) || empty($_POST['new_login'])
			|| !isset($_POST['modify_username_hidden']) || !empty($_POST['modify_username_hidden']))
		{
			$error = 'bad_login_or_password';
		}
		elseif($captcha_try != $captcha_solution) // le test!
		{
			$error = 'bad_solution_captcha';
		}
		else
		{
			// sécurisation de la saisie
			$old_login = $_POST['old_login'];
			$password = $_POST['password'];
			$new_login = self::removeSpacesTabsCRLF(htmlspecialchars($_POST['new_login']));
			// removeSpacesTabsCRLF pour éviter d'enregistrer une chaîne vide

			// tests de conformité
			if($old_login !== $_POST['old_login'] || $password !==  $_POST['password'] || $new_login !== $_POST['new_login'])
			{
				$error = 'forbidden_characters';
			}
			elseif($old_login !== $_SESSION['user']){
				$error = 'bad_login_or_password';
			}
			elseif($old_login === $new_login){
				$error = 'same_username_as_before';
			}
			else
			{
				$user = self::getUser($old_login, $entityManager);

				if(password_verify($password, $user->getPassword()))
				{
					$user->setLogin($new_login);
					$entityManager->flush();
					$_SESSION['user'] = $new_login;

					$url->addParams(['success_login' => 'new_login']);
					$error = '';
				}
				else
				{
					$error = 'bad_login_or_password';
				}
			}
		}

		if(!empty($error)){
			sleep(1);
			$url->addParams(['error_login' => $error]);
		}
		
		header('Location: ' . $url);
		die;
	}

	static public function updatePassword(EntityManager $entityManager): void
	{
		if(!$_SESSION['admin']) // superflux, fait dans le routeur
		{
			self::disconnect();
		}

		$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
		$captcha_try = isset($_POST['captcha']) ? Captcha::controlInput($_POST['captcha']) : 0;
		unset($_SESSION['captcha']);

		$url = new URL(['page' => 'user_edit']);
		isset($_GET['from']) ? $url->addParams(['from' => $_GET['from']]) : null;

		$error = '';
		if(!isset($_POST['login']) || empty($_POST['login'])
			|| !isset($_POST['old_password']) || empty($_POST['old_password'])
			|| !isset($_POST['new_password']) || empty($_POST['new_password'])
			|| !isset($_POST['modify_password_hidden']) || !empty($_POST['modify_password_hidden']))
		{
			$error = 'bad_login_or_password';
		}
		elseif($captcha_try != $captcha_solution) // le test!
		{
			$error = 'bad_solution_captcha';
		}
		else
		{
			// sécurisation de la saisie
			$login = $_POST['login'];
			$old_password = $_POST['old_password'];
			$new_password = self::removeSpacesTabsCRLF(htmlspecialchars($_POST['new_password']));
			// removeSpacesTabsCRLF pour éviter d'enregistrer une chaîne vide

			// tests de conformité
			if($login !== $_POST['login'] || $old_password !==  $_POST['old_password'] || $new_password !== $_POST['new_password'])
			{
				$error = 'forbidden_characters';
			}
			elseif($login !== $_SESSION['user']){
				$error = 'bad_login_or_password';
			}
			elseif($old_password === $new_password){
				$error = 'same_password_as_before';
			}
			else
			{
				$user = self::getUser($login, $entityManager);

				if(password_verify($old_password, $user->getPassword()))
				{
					$new_password = password_hash($new_password, PASSWORD_DEFAULT);
					$user->setPassword($new_password);
					$entityManager->flush();

					$url->addParams(['success_password' => 'new_password']);
					$error = '';
				}
				else
				{
					$error = 'bad_login_or_password';
				}
			}
		}

		if(!empty($error)){
			sleep(1);
			$url->addParams(['error_password' => $error]);
		}
		
		header('Location: ' . $url);
		die;
	}

	static private function getUser(string $login, EntityManager $entityManager): ?User
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

	// erreurs à la création des mots de passe
	static private function removeSpacesTabsCRLF(string $chaine): string
	{
		$cibles = [' ', "\t", "\n", "\r"]; // doubles quotes !!
		return(str_replace($cibles, '', $chaine));
	}
}