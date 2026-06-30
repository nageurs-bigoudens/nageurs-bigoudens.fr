<?php
// src/service/Backup.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Symfony\Component\Process\Process; // protection injection dans le shell

class Backup
{
	static public string $backup_dir = '../var/backups';
	static private int $amount_to_keep = 20;

	static public function mySQLdump(EntityManager $entityManager, string $type): string
	{
		$date = new DateTime;
		$file_path = self::$backup_dir . '/' . Config::$database . '_' . $date->format('Y-m-d') . '_' . $type . '.sql';

		// les versions de mysql sont comme ci: 8.0.36
		// celles de mariadb sont comme ça: 10.11.6-MariaDB
		$version = $entityManager->getConnection()->fetchOne('SELECT VERSION()');
		$engine = stripos($version, 'mariadb') !== false ? 'mariadb-dump' : 'mysqldump';

		$tmp = tempnam('../var', 'tmp_db_codes_'); // crée un fichier avec un nom aléatoire et des droits 600 (concurrence)
		// attention, si le dossier de destination n'est pas disponible, le fichier est placé avec les fichiers temporaires
		file_put_contents($tmp, 
			"[client]\n
			user=" . Config::$user . "\n
			password=" . Config::$password . "\n
			host=" . Config::$db_host . "\n");

		$command = new Process([
		    $engine,
		    '--defaults-extra-file=' . $tmp, // pour ne pas enregistrer les codes dans l'historique de la console ou dans les processus de l'OS
		    '--single-transaction',
		    '--quick', // évite d'exploser la RAM si beaucoup de données
		    '--result-file=' . $file_path,
		    Config::$database
		]);

		try{
			// unlink et chmod permettent que le serveur et l'utilisateur appelant bin/mysqldump.php réussissent
			if(file_exists($file_path)){
				unlink($file_path);
			}
			$command->mustRun(); // comme run() mais lance une ProcessFailedException

			//$file_path = self::gzipCompress($file_path); // '.gz' ajouté à la fin

			chmod($file_path, 0666);
			return $file_path;
		}
		finally{
			// exécuté même quand situé après "return"
			unlink($tmp);
			self::cleanBackups();
		}
	}

	// compression gzip (gros gain de place sur le serveur), nécessite l'extension zlib
	static public function gzipCompress(string $file_path): string
	{
		try{
			file_put_contents(
			    $file_path . '.gz',
			    gzencode(file_get_contents($file_path), 5), // plus rapide que 9 et taille identique d'après mes essais
			);
			unlink($file_path);
			$file_path .= '.gz';
		}
		finally{
			return $file_path;
		}
	}

	static public function getBackupList(): array
	{
		$files = scandir(Backup::$backup_dir); // affiche un warning si échoue (à cacher en prod)
		if(!$files){
			throw new RuntimeException("Le serveur a rencontré une erreur:<br>Accès aux backups impossible faute de permissions.");
		}

		$backup_array = [];
		foreach($files as $file){
			if($file[0] === '.'){
				continue;
			}
			$backup_array[] = $file;
		}
		return $backup_array;
	}

	static public function cleanBackups(): void
	{
		$files = glob(self::$backup_dir . '/*.sql');
		//usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a)); // filemtime = date de dernière modification
		arsort($files);

		// tri par nom de BDD puis par date
		$sorted_files = [];
		$list_by_database = []; // pour le nettoyage 2
		foreach($files as $file){
			$exploded = explode('_', basename($file));
			$sorted_files[$exploded[0]][$exploded[1]][] = $file;
			$list_by_database[$exploded[0]][] = $file;
		}

		$date = new DateTime;
		foreach($sorted_files as $db_name => $from_one_database){
			// on garde une "quantité à garder" par BDD
			if(count($from_one_database) > self::$amount_to_keep){
				// nettoyage 1
				foreach($from_one_database as $date_key => $with_same_date){
					// pas touche à aujourd'hui
					if($date_key != $date->format('Y-m-d')){
						self::cleanBackupsByPriority($with_same_date);
					}
				}
				// nettoyage 2
				$files_to_delete = array_slice($list_by_database[$db_name], self::$amount_to_keep);
				foreach($files_to_delete as $file){
				    unlink($file);
				}
			}
		}
	}

	// conserver un seul backup par jour choisi dans cet ordre de préférence: console => before-restore => download => auto
	// cet ordre correspond à: volonté de l'utilisateur => état du jour avant changement => volonté de changement => automatique sans contrôle
	static private function cleanBackupsByPriority(array $files): void
	{
		$priorities = [
			'console' => 1,
			'before-restore' => 2,
			'uploaded' => 3,
			'auto' => 4,
		];
		$best_priority = PHP_INT_MAX;

		// recherche du fichier à conserver
		$to_keep = null;
		foreach($files as $file){
			foreach($priorities as $keyword => $priority){
				if(str_contains(basename($file), $keyword) && $priority < $best_priority){
					$best_priority = $priority;
					$to_keep = $file;
					break;
				}
			}
		}
		// suppression des autres
		foreach($files as $file){
		    if($file !== $to_keep){
		        unlink($file);
		    }
		}
	}

	static public function restoreDatabase(EntityManager $entityManager, string $file_name, array $exclusion_list = []): void
	{
		// création d'un backup de sécurité non écrasable
		$date = new DateTime;
		if(!file_exists(self::$backup_dir . '/' . Config::$database . '_' . $date->format('Y-m-d') . '_before-restore.sql')){
			Backup::mySQLdump($entityManager, 'before-restore');
		}
		
		$version = $entityManager->getConnection()->fetchOne('SELECT VERSION()');
		$engine = stripos($version, 'mariadb') !== false ? 'mariadb' : 'mysql';

		$tables = self::getDBListOfTables($entityManager, $exclusion_list); // choisir les tables à effacer et restaurer, exclure "user"

		$tmp_db_codes = tempnam('../var', 'tmp_db_codes_'); // crée un fichier avec un nom aléatoire et des droits 600 (concurrence)
		file_put_contents($tmp_db_codes, 
			"[client]\n
			user=" . Config::$user . "\n
			password=" . Config::$password . "\n
			host=" . Config::$db_host . "\n");

		//$file_name = self::gzipExtract($file_name); // '.gz' ajouté à la fin

		$command = new Process([
		    $engine, // mariadb ou mysql
		    '--defaults-extra-file=' . $tmp_db_codes, // pour ne pas enregistrer les codes dans l'historique de la console ou dans les processus de l'OS
		    Config::$database
		]);
		$command->setInput(file_get_contents(Backup::$backup_dir . '/' . $file_name)); // l'entrée <

		// utiliser une liste de tables à exclure dynamique
		try{
			// tout effacer
			$tables_with_backquotes = array_map(fn($t) => '`' . $t . '`', $tables);
			if(!empty($tables_with_backquotes)){
			    $sql = "SET FOREIGN_KEY_CHECKS=0; DROP TABLE " . implode(', ', $tables_with_backquotes) . "; SET FOREIGN_KEY_CHECKS=1;";
			    $entityManager->getConnection()->executeStatement($sql);
			}

			// copie des tables à ne pas restaurer
			foreach($exclusion_list as $excluded){
				$entityManager->getConnection()->executeStatement('RENAME TABLE `' . TABLE_PREFIX . $excluded . '` TO `' . TABLE_PREFIX . $excluded . '_dont_touch`;');
			}
			
			// restaurer
			$command->mustRun(); // comme run() mais lance une ProcessFailedException

			// remettre table user comme avant
			foreach($exclusion_list as $excluded){
				$entityManager->getConnection()->executeStatement('DROP TABLE `' . TABLE_PREFIX . $excluded . '`;');
				$entityManager->getConnection()->executeStatement('RENAME TABLE `' . TABLE_PREFIX . $excluded . '_dont_touch` TO `' . TABLE_PREFIX . $excluded . '`;');
			}
		}
		finally{
			// exécuté même quand situé après "return"
			unlink($tmp_db_codes);
		}
	}

	static private function getDBListOfTables(EntityManager $entityManager, array $exclusion_list = []): array
	{
		$tables = $entityManager->getConnection()->createSchemaManager()->listTableNames();
		foreach($tables as $key => $elem){
			foreach($exclusion_list as $excluded){
				if(!is_string($excluded)){ // cas pas du tout censé arriver!
					throw new LogicException("Un nom de table doit etre une chaîne de caractères.");
				}
				if($elem === TABLE_PREFIX . $excluded){
		    		unset($tables[$key]);
		    	}
			}
	    }
	    return $tables;
	}
}