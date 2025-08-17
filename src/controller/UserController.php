<?php
// src/controller/UserController.php
//
/* actuellement un fourre-tout de méthodes en rapport avec les utilisateurs
pour l'améliorer on pourrait appliquer le principe de reponsabilité unique
=> UserController devrait se limiter à gérer des requêtes et réponses (changement transparent pour le routeur)
il instancierait un classe correspondant au formulaire (prend POST et SESSION) et une classe "métier" UserService
=> UserService contiendrait des méthodes utilisant l'objet formulaire pour agir sur la BDD
=> les formulaires peuvent tenir dans une classe "UserUpdateForm" avec des stratégies ou plusieurs, les données y sont validées
=> il est aussi possible de découper UserController en contrôleurs par fonctionnalité:
Auth (connexion, deconnexion), User (infos, choix photo), Account (créer, supprimer compte), Avatar (upload photo...)
*/

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\Log;

class UserController
{
	// account
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

	// account
	static public function createUser(EntityManager $entityManager)
	{
		unset($_SESSION['user']);

		$form = new FormValidation($_POST, 'create_user');
		
		$url = new URL;
		$error = '';
		if($form->validate()){
			$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
			$user = new App\Entity\User($_POST['login'], $password);
			$entityManager->persist($user);
			$entityManager->flush();
		}
		else{
			$error = $form->getErrors()[0]; // la 1ère erreur sera affichée
		}
		
		if(!empty($error)){
			$url->addParams(['error' => $error]);
		}

		header('Location: ' . $url);
		die;
	}

	// auth
	static public function connect(EntityManager $entityManager): void
	{
		if($_SESSION['admin']) // déjà connecté?
		{
			header('Location: ' . new URL);
			die;
		}
		$_SESSION['user'] = '';
		$_SESSION['admin'] = false;

		$form = new FormValidation($_POST, 'connection');

		$error = '';
		if($form->validate()){
			// à mettre dans une classe métier UserService, Authentication, AuthService?
			$user = self::getUser($_POST['login'], $entityManager);
			if(!empty($user) && $_POST['login'] === $user->getLogin() && password_verify($_POST['password'], $user->getPassword()))
			{
				$log = new Log(true);
				
				// protection fixation de session, si l'attaquant crée un cookie de session, il est remplacé
				session_regenerate_id(true);
				$_SESSION['user'] = $_POST['login'];
				$_SESSION['admin'] = true;

				$url = new URL(isset($_GET['from']) ? ['page' => $_GET['from']] : []);
				isset($_GET['id']) ? $url->addParams(['id' => $_GET['id']]) : '';
			}
			else
			{
				$log = new Log(false);
				$error = 'bad_login_or_password';
			}
			$entityManager->persist($log);
			$entityManager->flush();
		}
		else{
			$error = $form->getErrors()[0]; // la 1ère erreur sera affichée
		}
		
		if(!empty($error)){
			sleep(1); // défense basique à la force brute
			$url = new URL(['page' => 'connexion']);
			isset($_GET['from']) ? $url->addParams(['from' => $_GET['from']]) : null;
			isset($_GET['id']) ? $url->addParams(['id' => $_GET['id']]) : null;
			$url->addParams(['error' => $error]);
		}

		header('Location: ' . $url);
		die;
	}

	// auth
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

	// user
	static public function updateUsername(EntityManager $entityManager): void
	{
		if(!$_SESSION['admin']){ // superflux, fait dans le routeur
			self::disconnect();
		}

		$url = new URL(['page' => 'user_edit']);
		isset($_GET['from']) ? $url->addParams(['from' => $_GET['from']]) : null;

		$form = new FormValidation($_POST, 'username_update');

		$error = '';
		if($form->validate()){
			// à mettre dans une classe métier UserService?
			$user = self::getUser($_POST['login'], $entityManager);
			if(password_verify($_POST['password'], $user->getPassword())){
				$user->setLogin($_POST['new_login']);
				$entityManager->flush();
				$_SESSION['user'] = $_POST['new_login'];

				$url->addParams(['success_username' => 'new_login']);
				$error = '';
			}
			else{
				$error = 'bad_login_or_password';
			}
		}
		else{
			$error = $form->getErrors()[0]; // la 1ère erreur sera affichée
		}
		
		if(!empty($error)){
			sleep(1);
			$url->addParams(['error_username' => $error]);
		}
		header('Location: ' . $url);
		die;
	}

	// user
	static public function updatePassword(EntityManager $entityManager): void
	{
		if(!$_SESSION['admin']){ // superflux, fait dans le routeur
			self::disconnect();
		}

		$url = new URL(['page' => 'user_edit']);
		isset($_GET['from']) ? $url->addParams(['from' => $_GET['from']]) : null;

		$form = new FormValidation($_POST, 'password_update');

		$error = '';
		if($form->validate()){
			// à mettre dans une classe métier UserService?
			$user = self::getUser($_POST['login'], $entityManager);
			if(password_verify($_POST['password'], $user->getPassword())){
				$new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
				$user->setPassword($new_password);
				$entityManager->flush();

				$url->addParams(['success_password' => 'new_password']);
				$error = '';
			}
			else{
				$error = 'bad_login_or_password';
			}
		}
		else{
			$error = $form->getErrors()[0]; // la 1ère erreur sera affichée
		}
		
		if(!empty($error)){
			sleep(1);
			$url->addParams(['error_password' => $error]);
		}
		header('Location: ' . $url);
		die;
	}

	// dans une classe mère ou un trait après découpage de UserController?
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

	// dans une classe Form?
	// erreurs à la création des mots de passe
	static private function removeSpacesTabsCRLF(string $chaine): string
	{
		$cibles = [' ', "\t", "\n", "\r"]; // doubles quotes !!
		return(str_replace($cibles, '', $chaine));
	}
}