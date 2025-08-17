<?php
// src/FormValidation.php

class FormValidation
{
	private array $data; // tableau associatif (probablement $_POST)
	private string $validation_strategy; // à remplacer plus tard par un objet (pattern stratégie) d'interface ValidationStrategy
	private array $errors;
	private bool $validated = false;

	public function __construct(array $data, string $validation_strategy){
		$this->data = $data;
		$this->validation_strategy = $validation_strategy;
	}

	public function validate(): bool
	{
		$this->errors = [];

		// pattern stratégie en une seule classe
		switch($this->validation_strategy){
			case 'email':
				$this->emailStrategy();
				break;
			case 'create_user':
				$this->createUserStrategy();
				break;
			case 'connection':
				$this->connectionStrategy();
				break;
			case 'username_update':
				$this->usernameUpdateStrategy();
				break;
			case 'password_update':
				$this->passwordUpdateStrategy();
				break;
			default:
				http_response_code(500); // c'est un peu comme jeter une exception
				echo json_encode(['success' => false, 'error' => 'server_error']);
				die;
		}

		$this->validated = true;
		return empty($this->errors);
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function getField(string $field): string
	{
		return $this->validated ? $this->data[$field] : '';
	}

	// méthodes de validation
	private function captchaValidate(bool $clean_session = true): void
	{
		$captcha_solution = (isset($_SESSION['captcha']) && is_int($_SESSION['captcha'])) ? $_SESSION['captcha'] : 0;
		$captcha_try = isset($this->data['captcha']) ? Captcha::controlInput($this->data['captcha']) : 0;
		if($clean_session){
			unset($_SESSION['captcha']);
		}
		
		if($captcha_try == 0){
			$error = 'error_non_valid_captcha';
		}
		elseif($captcha_solution == 0){ // ne peut pas arriver, si?
			$error = 'captcha_server_error';
		}
		elseif($captcha_try !== $captcha_solution){
			$this->errors[] = 'bad_solution_captcha';
		}
	}
	
	// erreurs à la création des mots de passe
	static private function removeSpacesTabsCRLF(string $chaine): string
	{
		$cibles = [' ', "\t", "\n", "\r"]; // doubles quotes !!
		return(str_replace($cibles, '', $chaine));
	}


	// stratégies
	private function emailStrategy(): void
	{
		$this->captchaValidate(false);

		if(!isset($this->data['name']) || empty($this->data['name'])
			|| !isset($this->data['email']) || empty($this->data['email'])
			|| !isset($this->data['message']) || empty($this->data['message'])
			|| !isset($this->data['hidden']) || !empty($this->data['hidden'])){
			$this->errors[] = 'missing_fields';
		}
		
		if(!filter_var(trim($this->data['email']), FILTER_VALIDATE_EMAIL)){
			$this->errors[] = 'bad_email_address';
		}

		$this->data['name'] = htmlspecialchars(trim($this->data['name']));
		$this->data['email'] = htmlspecialchars(trim($this->data['email']));
		$this->data['message'] = htmlspecialchars($this->data['message']);
	}
	private function createUserStrategy(): void
	{
		$this->captchaValidate();

		// test mauvais paramètres
		if(!isset($this->data['login']) || empty($this->data['login'])
	        || !isset($this->data['password']) || empty($this->data['password'])
	        || !isset($this->data['password_confirmation']) || empty($this->data['password_confirmation'])
	        || !isset($this->data['create_user_hidden']) || !empty($this->data['create_user_hidden']))
		{
			$this->errors[] = 'bad_login_or_password';
		}

		if($this->data['password'] !== $this->data['password_confirmation']){
			$this->errors[] = 'different_passwords';
		}

		if($this->data['login'] !== self::removeSpacesTabsCRLF(htmlspecialchars($this->data['login']))
			|| $this->data['password'] !== self::removeSpacesTabsCRLF(htmlspecialchars($this->data['password']))){
			$this->errors[] = 'forbidden_characters';
		}
	}
	private function connectionStrategy(): void
	{
		$this->captchaValidate();

		if(!isset($this->data['login']) || empty($this->data['login'])
			|| !isset($this->data['password']) || empty($this->data['password'])
			|| !isset($this->data['connection_hidden']) || !empty($this->data['connection_hidden']))
		{
			$this->errors[] = 'bad_login_or_password';
		}
	}
	private function usernameUpdateStrategy(): void
	{
		$this->captchaValidate();

		if(!isset($this->data['login']) || empty($this->data['login'])
			|| !isset($this->data['password']) || empty($this->data['password'])
			|| !isset($this->data['new_login']) || empty($this->data['new_login'])
			|| !isset($this->data['modify_username_hidden']) || !empty($this->data['modify_username_hidden']))
		{
			$this->errors[] = 'bad_login_or_password';
		}

		$new_login = self::removeSpacesTabsCRLF(htmlspecialchars($this->data['new_login']));
		if($new_login !== $this->data['new_login']){
			$this->errors[] = 'forbidden_characters';
		}

		if($this->data['login'] !== $_SESSION['user']){
			$this->errors[] = 'bad_login_or_password';
		}
		if($this->data['login'] === $new_login){
			$this->errors[] = 'same_username_as_before';
		}
	}
	private function passwordUpdateStrategy(): void
	{
		$this->captchaValidate();

		if(!isset($this->data['login']) || empty($this->data['login'])
			|| !isset($this->data['password']) || empty($this->data['password'])
			|| !isset($this->data['new_password']) || empty($this->data['new_password'])
			|| !isset($this->data['modify_password_hidden']) || !empty($this->data['modify_password_hidden']))
		{
			$this->errors[] = 'bad_login_or_password';
		}

		$new_password = self::removeSpacesTabsCRLF(htmlspecialchars($this->data['new_password']));
		if($new_password !== $this->data['new_password']){
			$this->errors[] = 'forbidden_characters';
		}

		if($this->data['login'] !== $_SESSION['user']){
			$this->errors[] = 'bad_login_or_password';
		}
		if($this->data['password'] === $new_password){
			$this->errors[] = 'same_password_as_before';
		}
	}
}