#!/usr/bin/env php
<?php
// bin/restore_db.php

declare(strict_types=1);

if($argc === 2){
    if(pathinfo($argv[1])['extension'] !== 'sql'){
    	try{
	    	throw new LogicException("Erreur, charger un fichier au format SQL");
	    }
	    catch(LogicException $e){
	    	echo $e->getMessage() . "\n";
	    	die;
	    }
    }
    $file_name = pathinfo($argv[1])['basename'];
    $file_data = file_get_contents($argv[1]);
}

chdir(dirname(__FILE__)); // /chemin/absolu/bin/restore_db.php
require "../vendor/autoload.php";
Config::load('../config/config.ini');
require '../src/model/doctrine-bootstrap.php';

// backup sur le système de fichiers en paramètre
if($argc === 2){
	// enregistrer le fichier
    if(!file_put_contents(Backup::$backup_dir . '/' . $file_name, $file_data)){
    	try{
    		throw new RuntimeException("Erreur, impossible d'enregistrer le fichier\n");
    	}
    	catch(RuntimeException $e){
	    	echo $e->getMessage() . "\n";
	    	die;
	    }
    }  

    echo "Fichier SQL " . pathinfo($file_name)['basename'] . " enregistré dans var/backups/\n";
}
// choix parmi les backups sur le serveur
elseif($argc === 1){
	// obtenir des fichiers
	try{
		$backup_array = Backup::getBackupList();
	}
	catch(RuntimeException $e){
		$backup_array = [];
		echo $e->getMessage() . "\n";
	}

	$backup_files = [];
	$j = 0;
	for($i = count($backup_array) - 1; $i >= 0; $i--){
		echo ($j + 1) . '. ' . $backup_array[$i] . "\n";
		$backup_files[$j] = $backup_array[$i];
		$j++;
	}

	// saisie utilisateur
	$nb = (int)readline("Choisissez un fichier par son numéro : ") - 1;

	if($nb < 0 || $nb >= count($backup_files)){
		echo "Erreur, saisir un numéro dans le choix proposé.\n";
		die;
	}
	$file_name = $backup_files[$nb];
}
else{
	echo "Erreur: trop de paramètres\n";
	die;
}

try{
	Backup::restoreDatabase($entityManager, $file_name);
}
catch(RuntimeException $e){
	echo $e->getMessage() . "\n";
	die;
}
echo "La base de donnée a été restaurée avec le backup: " . $file_name . "\n";
echo "En cas d'erreur, vous pouvez revenir en arrière en réalisant une nouvelle restauration avec le fichier:\n" . Config::$database . '_' . (new DateTime)->format('Y-m-d') . "_before-restore.sql\n";
