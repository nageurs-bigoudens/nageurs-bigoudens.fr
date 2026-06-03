<?php
// src/service/UserDataService.php

declare(strict_types=1);

class UserDataService
{
	static private string $var = '../var';

	static public function createZip(string $zip_name, array $source_directories, array $pattern_to_target_in_user_data = []): string
	{
		$file_path = self::$var . '/' . $zip_name;
		$Zip = new ZipArchive();

		if($Zip->open($file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE){ // ou ZipArchive::FL_OVERWRITE, à comparer à ZipArchive::OVERWRITE
			throw new RuntimeException("Création ou ouverture du fichier demandé impossible à cause d'une erreur de permissions sur le serveur.");
		}

		// recherche récursive dans les dossiers dans $source_directories, c'est comme le paramètre "-r" dans la console
		$counter = 0;
		foreach($source_directories as $path){
		    $directory = new RecursiveDirectoryIterator($path);
		    $iterator = new RecursiveIteratorIterator($directory);
		    
			foreach($iterator as $info){
				if($info->getFilename() != "." && $info->getFilename() != ".."){ // chemins inutiles . et ..
					$Zip->addGlob($info->getPathname(), 0, array(''));
					$counter++;
				}
			}
	    }
	    // recherche à la racine avec des pattern de noms de fichiers (optionnel)
		foreach($pattern_to_target_in_user_data as $one_pattern){
			$Zip->addGlob($one_pattern, 0, array(''));
		}

	    $Zip->close();
	    if($counter > 0){
	    	//chmod($file_path, 0666);
	    	return $zip_name;
	    }
	    else{
	    	throw new RuntimeException("Téléchargement des fichiers impossible, aucun fichier n'a été trouvé sur le serveur.");
	    }
	}
}