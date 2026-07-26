<?php
// src/service/FormValidation.php
//
// pattern strategy simplifié, permet évolution future vers le vrai pattern

declare(strict_types=1);

// classe spéciale permettant de bénéficier du typage de classe au lieu de passer une chaîne à FormValidation
enum ValidationStrategy {
	// bloc formulaire de contact
	case EmailSend;
    case EmailParam;
    // formulaires pages spéciales
    case CreateUser;
    case Connection;
    case UsernameUpdate;
    case PasswordUpdate;

    public function method(): string
    {
    	return match($this){
    		self::EmailSend => 'emailStrategy',
		    self::EmailParam => 'emailParamStrategy',
		    self::CreateUser => 'createUserStrategy',
		    self::Connection => 'connectionStrategy',
		    self::UsernameUpdate => 'usernameUpdateStrategy',
		    self::PasswordUpdate => 'passwordUpdateStrategy'
    	};
    }
}

class FormValidation
{
	private array $data; // tableau associatif (probablement $_POST)
	private Closure $callStrategy;
	private array $errors;

	public function __construct(array $data, ValidationStrategy $strategy){
		$this->data = $data;
		$method = $strategy->method();
		$this->callStrategy = fn() => $this->$method();
	}

	public function validate(): bool
	{
		$this->errors = [];
		($this->callStrategy)(); // appel de la callback
		return empty($this->errors);
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function getField(string $field): string
	{
		return $this->data[$field] ?? '';
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
			$this->errors[] = 'error_non_valid_captcha';
		}
		elseif($captcha_solution == 0){ // ne peut pas arriver, si?
			$this->errors[] = 'captcha_server_error';
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

		if(empty($this->data['name']) || empty($this->data['email']) || empty($this->data['message'])
			|| !isset($this->data['hidden']) || !empty($this->data['hidden'])){
			$this->errors[] = 'missing_fields';
		}
		
		elseif(!filter_var(trim($this->data['email']), FILTER_VALIDATE_EMAIL)){
			$this->errors[] = 'bad_email_address';
		}

		$this->data['name'] = htmlspecialchars(trim($this->data['name']));
		$this->data['email'] = htmlspecialchars(trim($this->data['email']));
		$this->data['message'] = htmlspecialchars($this->data['message']);
	}
	private function emailParamStrategy(): void
	{
		if(empty($this->data['id']) || empty($this->data['what_param']) || empty($this->data['value'])
			|| !isset($this->data['hidden']) || !empty($this->data['hidden'])){
			$this->errors[] = 'missing_fields';
		}

		if(!in_array($this->data['what_param'], ['smtp_host', 'smtp_secure', 'smtp_username', 'smtp_password', 'email_dest'])){
			$this->errors[] = 'unknown_parameter';
		}
		elseif($this->data['what_param'] === 'smtp_username' || $this->data['what_param'] === 'email_dest'){
			if(!filter_var($this->data['value'], FILTER_VALIDATE_EMAIL)){
				$this->errors[] = 'invalide_email_address';
			}
		}
	}
	private function createUserStrategy(): void
	{
		$this->captchaValidate();

		// test mauvais paramètres
		if(empty($this->data['login']) || empty($this->data['password']) || empty($this->data['password_confirmation'])
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

		if(empty($this->data['login']) || empty($this->data['password'])
			|| !isset($this->data['connection_hidden']) || !empty($this->data['connection_hidden']))
		{
			$this->errors[] = 'bad_login_or_password';
		}
	}
	private function usernameUpdateStrategy(): void
	{
		$this->captchaValidate();

		if(empty($this->data['login']) || empty($this->data['password']) || empty($this->data['new_login'])
			|| !isset($this->data['modify_username_hidden']) || !empty($this->data['modify_username_hidden']))
		{
			$this->errors[] = 'bad_login_or_password';
		}

		$new_login = self::removeSpacesTabsCRLF(htmlspecialchars($this->data['new_login']));
		if($new_login !== $this->data['new_login']){
			$this->errors[] = 'forbidden_characters';
		}

		if($this->data['login'] !== $_SESSION['user']['username']){
			$this->errors[] = 'bad_login_or_password';
		}
		if($this->data['login'] === $new_login){
			$this->errors[] = 'same_username_as_before';
		}
	}
	private function passwordUpdateStrategy(): void
	{
		$this->captchaValidate();

		if(empty($this->data['login']) || empty($this->data['password']) || empty($this->data['new_password'])
			|| !isset($this->data['modify_password_hidden']) || !empty($this->data['modify_password_hidden']))
		{
			$this->errors[] = 'bad_login_or_password';
		}

		$new_password = self::removeSpacesTabsCRLF(htmlspecialchars($this->data['new_password']));
		if($new_password !== $this->data['new_password']){
			$this->errors[] = 'forbidden_characters';
		}

		if($this->data['login'] !== $_SESSION['user']['username']){
			$this->errors[] = 'bad_login_or_password';
		}
		if($this->data['password'] === $new_password){
			$this->errors[] = 'same_password_as_before';
		}
	}
}