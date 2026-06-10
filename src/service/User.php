<?php
// src/service/User.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;

class User{
	static public function existUsers(EntityManager $entityManager): bool
	{
	    if(!$entityManager // table vide
	        ->createQuery("SELECT u FROM App\Entity\User u")
	        ->setMaxResults(1)
	        ->getOneOrNullResult())
		{
			unset($_SESSION['user']);
			return false;
		}
		else{
			return true;
		}
	}
}