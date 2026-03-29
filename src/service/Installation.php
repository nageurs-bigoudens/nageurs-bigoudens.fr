<?php
// src/service/Installation.php

declare(strict_types=1);

class Installation
{
	static public function phpDependancies(): void
	{
		$flag = false;
		$extensions = ['pdo_mysql', 'mbstring', 'ctype', 'json', 'tokenizer', 'imagick']; // les 5 premières sont pour doctrine
		// ajouter plus tard zip pour les backup
		foreach($extensions as $extension){
	        if(!extension_loaded($extension))
	        {
	            echo("<p>l'extension <b>" . $extension . '</b> est manquante</p>');
	            $flag = true;
	        }
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
	    $droits_dossiers = 0700;
	    $droits_fichiers = 0600;

	    if(!file_exists('user_data')){
	    	// créer le dossier user_data
	    	mkdir('user_data/');
	        chmod('user_data/', $droits_dossiers);
	    	echo '<p style="color: red;">Le dossier public/user_data introuvable et le serveur n\'a pas la permission de le créer.<br>
	    	Pour faire ça bien:<br>sudo -u "serveur web" mkdir /chemin/du/site/public/user_data</p>
	    	<p>Aide: "serveur web" se nomme "www-data" sur debian et ubuntu, il s\'appelera "http" sur d\'autres distributions.</p>';
	    	die;
	    }

	    if(!file_exists('../config/config.ini')){
	    	// aide à la création du config.ini
	    	echo '<p>Le fichier config/config.ini est introuvable.</p>';
	    	echo '<p>Il doit obligatoirement contenir les codes de la base de données, le protocole http ou https (et éventuellement le port) utilisé pour créer les liens internes.<br>
	    		Un modèle est disponible, il s\'agit du fichier config/config-template.ini</p>
	    		<p>Quand vous aurez terminé votre config.ini, donnez-lui par sécurité des droits 600.</p>';
	    	die;
	    }
	    else{
	    	// droits du config.ini
			/*if(substr(sprintf('%o', fileperms('../config/config.ini')), -4) != 600){
				chmod('../config/config.ini', $droits_fichiers);
			}*/

	    	// tester les liens internes
	    	//

	    	// le test de connexion à la BDD est dans le doctrine bootstrap
	    }
	}
}