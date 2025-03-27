<?php
// src/controller/URL.php

declare(strict_types=1);

class URL implements Stringable
{
	static private string $protocol = 'http://';
	static private string $host = 'localhost';
	static private string $port;
	static private string $path = '/index.php';
	private array $params;
	private string $anchor = '';

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

	public function __construct(array $gets = [], string $anchor = ''){
		$this->params = $gets;
		if($anchor != ''){
			$this->setAnchor($anchor);
		}
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
			$this->anchor = '#' . ltrim($anchor, '#');
		}
		else{
			$this->anchor = '';
		}
	}

	private function makeParams(): string
	{
		$output = '';
		$first = true;
		
		foreach($this->params as $key => $value) {
			if($first){
				$output .= '?';
				$first = false;
			}
			else{
				$output .= '&';
			}
			$output .= $key . '=' . $value;
		}
		return $output;
	}

	public function __toString(): string
	{
		return self::$protocol . self::$host . self::$port . self::$path . $this->makeParams() . $this->anchor;
	}
}