<?php
// src/Service/URL.php

declare(strict_types=1);

namespace App\Service;

class URL implements \Stringable
{
	static private string $protocol = 'http://';
	static private string $host = '';
	static private string $port;
	static private string $path = '/index.php';
	private array $params;
	private string $anchor = '';

	public function __construct(array $gets = [], string $anchor = ''){
		$this->params = $gets;
		if($anchor != ''){
			$this->setAnchor($anchor);
		}
	}

	// setters statiques
	static public function setProtocol(string $protocol = 'http'): void
	{
		self::$protocol = $protocol === 'https' ? 'https://' : 'http://';
	}
	static public function setPort(int|string $port = 80): void
	{
		if((int)$port === 443){
			self::$protocol = 'https://';
			self::$port = '';
		}
		elseif((int)$port === 80){
			self::$protocol = 'http://';
			self::$port = '';
		}
		else{
			self::$port = ':' . (string)$port;
		}
	}
	static public function setHost(string $host): void
	{
		self::$host = $host;
	}
	static public function setPath(string $path): void
	{
		self::$path = '/' . ltrim($path, '/');
	}
	
	//setters normaux
	public function addParams(array $gets): void
	{
		// array_merge est préféré à l'opérateur d'union +, si une clé existe déjà la valeur est écrasée
		$this->params = array_merge($this->params, $gets);
	}
	public function setAnchor(string $anchor = ''): void
	{
		if($anchor != ''){
			$this->anchor = '#' . rawurlencode(ltrim($anchor, '#'));// rawurlencode échappe les mêmes caractères
		}
		else{
			$this->anchor = '';
		}
	}

	public function __toString(): string
	{
		return self::$protocol . self::$host . self::$port . self::$path
			. ($this->params ? '?' . http_build_query($this->params) : '')
			. $this->anchor;
		// http_build_query change un tableau comme ['key1' => 'value 1', 'key2' => 'value 2']
		// en chaine: key1=value+1&key2=value+2
		// les caractères ", ', <, >, &, #, = et espaces sont échappés (comme avec rawurlencode pour les ancres)
	}
}