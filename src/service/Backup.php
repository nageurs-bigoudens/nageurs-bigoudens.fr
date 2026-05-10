<?php
// src/service/Backup.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Symfony\Component\Process\Process; // protection injection dans le shell

class Backup
{
	static private string $backup_dir = '../var/backups';
	static private int $amount_to_keep = 30;

	static public function mySQLdump(EntityManager $entityManager): string
	{
		$file_path = self::$backup_dir . '/db_' . Config::$database . '_' . new DateTime()->format('Y-m-d') . '.sql';

		// les versions de mysql sont comme ci: 8.0.36
		// celles de mariadb sont comme ça: 10.11.6-MariaDB
		$version = $entityManager->getConnection()->fetchOne('SELECT VERSION()');
		$engine = stripos($version, 'mariadb') !== false ? 'mariadb-dump' : 'mysqldump';

		$tmp = tempnam('../var', 'tmp_db_codes_'); // crée un fichier avec un nom aléatoire et des droits 600 (concurrence)
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
			chmod($file_path, 0666);
			return $file_path;
		}
		finally{
			// exécuté même quand situé après "return"
			unlink($tmp);
			self::cleanBackups();
		}

		// compression gzip (gros gain de place sur le serveur), nécessite l'extension zlib
		/*try{
			file_put_contents(
			    $file_path . '.gz',
			    gzencode(file_get_contents($file_path), 5), // plus rapide que 9 et taille identique d'après mes essais
			);
			return $file_path . '.gz';
		}
		finally{
			unlink($file_path);
		}*/
	}

	static public function getBackupList(): array
	{
		$backup_array = [];
		foreach(scandir(Backup::$backup_dir) as $file){
		    if($file[0] === '.'){
		        continue;
		    }
		    $backup_array[] = $file;
		}
		return $backup_array;
	}
	static public function getLastBackupName(): string
	{
		$backup_list = self::getBackupList();
		return $backup_list[count($backup_list) - 1];
	}

	static public function cleanBackups(): void {
		$files = glob(self::$backup_dir . '/*.sql');
		usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a)); // filemtime = date de dernière modification
		$files_to_delete = array_slice($files, self::$amount_to_keep);
		foreach($files_to_delete as $file){
		    unlink($file);
		}
	}
}