<?php
// src/service/Installation.php

declare(strict_types=1);

class Installation
{
	static public function phpDependancies(): void
	{
		$flag = false;
		$extensions = ['pdo_mysql', 'mbstring', 'ctype', 'json', 'tokenizer', 'imagick']; // les 5 premières sont pour doctrine
		// ajouter plus tard zlib pour la compression des backups
		foreach($extensions as $extension){
	        if(!extension_loaded($extension)){
	            echo("<p>l'extension <b>" . $extension . "</b> est manquante.</p>");
	            $flag = true;
	        }
	    }
	    if(!class_exists(DOMDocument::class)){ // théoriquement plus fiable que extension_loaded()
	    	echo("<p>l'extension <b>dom</b> est manquante.</p>");
	    	$flag = true;
	    }

	    /*if(!extension_loaded('imagick') && !extension_loaded('gd')){
	        echo("<p>il manque une de ces extensions au choix pour le traitement des images: <b>imagick</b> (de préférence) ou <b>gd</b>.</p>");
	        $flag = true;
	    }*/
	    // si imagick n'est pas disponible, essayer gd (reste encore à coder)

	    if($flag){
	    	echo '<p>Réalisez les actions nécéssaires sur le serveur ou contactez l\'administrateur du site.<br>
	    		Quand le problème sera résolu, il vous suffira de <a href="#">recharger la page<a>.</p>';
		    die;
	    }
	}

	static public function checkFilesAndFoldersRights(): void
	{
		// -- droits des fichiers et dossiers --
		$droits_dossiers = 0777;

		$flag = false;
	    if(!file_exists('user_data')){
	    	try{
		    	mkdir('user_data/');
		        chmod('user_data/', $droits_dossiers);
		    }
		    catch(Exception $e){
		    	echo '<p style="color: red;">Le dossier public/user_data introuvable et le serveur n\'a pas la permission de le créer.<br>
		    	Pour faire ça bien:<br>sudo -u "serveur web" mkdir /chemin/du/site/public/user_data</p>';
		    	echo $e;
		    	$flag = true;
		    }
	    }
	    if(!file_exists('../var')){
	    	try{
		    	mkdir('../var');
		    	chmod('../var', $droits_dossiers);
		    }
		    catch(Exception $e){
		    	echo $e;
		    	$flag = true;
		    }
	    }
	    if(!file_exists('../var/backups')){
	    	try{
		    	mkdir('../var/backups');
		    	chmod('../var/backups', $droits_dossiers); // autoriser à la fois le serveur et les scripts dans bin/
		    }
		    catch(Exception $e){
		    	echo $e;
		    	$flag = true;
		    }
	    }

	    // droits 600 pour celui-ci
	    if(!file_exists('../config/config.ini')){
	    	// aide à la création du config.ini
	    	echo '<p>Le fichier config/config.ini est introuvable.</p>';
	    	echo '<p>Il doit obligatoirement contenir les codes de la base de données, le protocole http ou https (et éventuellement le port) utilisé pour créer les liens internes.<br>
	    		Un modèle est disponible, il s\'agit du fichier config/config-template.ini</p>
	    		<p>Ce fichier a une importance critique. Si vous le pouvez faites en sorte que le serveur en soit le propriétaire et donner lui des droits 600.</p>';
	    	$flag = true;
	    }
	    /*else{
	    	// propriétaire du fichier
	    	if(fileowner('../config/config.ini') != posix_geteuid()){
	    		echo "<p>le fichier config/config.ini n'appartient pas au serveur.</p>";
	    	}
	    	else{
	    		// droits du config.ini
				if(substr(sprintf('%o', fileperms('../config/config.ini')), -4) != 600){
					echo '<p>Attention, le </p>';
					//chmod('../config/config.ini', $droits_fichiers);
				}
	    	}
	    }*/
	    if($flag){
	    	die;
	    }

	    // tester les liens internes
    	//

    	// le test de connexion à la BDD est dans le doctrine bootstrap
	}
}