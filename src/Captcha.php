<?php
// src/Captcha.php
//
// la solution est stockée dans une unique variable $_SESSION['captcha']
// => on pourrait appliquer le pattern "singleton" (justification = le captcha devient une sorte de ressource partagée)

declare(strict_types=1);

class Captcha
{
	private int $a;
	private int $b;

	public function __construct(){
		$this->a = rand(2, 9);
		$this->b = rand(2, 9);
	}

	public function getA(): string
	{
		return $this->toLettersFrench($this->a);
	}
	public function getB(): string
	{
		return $this->toLettersFrench($this->b);
	}
	public function getSolution(): int
	{
		return ($this->a * $this->b);
	}

	private function toLettersFrench(int $number): string
	{
		return match($number){
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
	
	// (à déplacer dans FormValidation?)
	static public function controlInput(string $input = '0'): int
	{
	    // un POST est une chaîne qu'on doit convertir en nombre dans deux conditions:
	    // test de format: $input est un nombre
	    // test d'intégrité: supprimer les décimales avec (int) ne change pas la valeur du nombre
	    return is_numeric($input) && $input == (int)$input ? (int)$input : 0;
	}
}