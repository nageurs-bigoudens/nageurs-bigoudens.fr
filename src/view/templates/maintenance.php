<?php declare(strict_types=1); ?>
<section class="maintenance">
	<h3>Maintenance du site</h3>
	<div class="basic_div">
		<p>
			<button onclick="displayLogs()">Consulter les journaux de connexion</button><br>
			<i>Qui a essayé de se connecter, quand et a-t'il réussi?</i>
		</p>
		<div id="log_table"></div>
		<p><button onclick="cleanLogs()">Effacer les journaux de connexion</button></p>
		
	</div>
	<div class="basic_div">
		<p>
			<a href="<?= new URL(['page' => 'emails']) ?>"><button>Consulter les emails</button></a>
			<i>reçus depuis tous les formulaires de contact</i>
		</p>
	</div>
	<div class="basic_div">
		<p>
			<a href="<?= new URL(['action' => 'get_mysqldump']) ?>">
				<button id="get_mysqldump">Télécharger une sauvegarde de la base de données</button>
			</a><br>
			<i>Obtenir un fichier SQL à conserver sur votre ordinateur. Une sauvegarde est réalisée à chaque visite de cette page.</i>
		</p>
	</div>
	<div class="basic_div">
		<p>Restaurer la base de données à partir d'un fichier SQL.<br>
			<i>Attention l'actuelle BDD sera écrasée!</i>
		</p>
		<p>
			<label for="">Utiliser une sauvegarde conservée sur le serveur</label>
			<select>
				<?= $backup_options ?>
			</select>
		</p>
		<p>
			<label for="restore_sql_dump">Utiliser un fichier sur votre ordinateur:</label>
			<input id="restore_sql_dump" type="file" accept=".sql" name="restore_sql_dump">
			
		</p>
	</div>

	<div class="basic_div">
		<a href="http://nageurs.localhost/index.php"><button>Retour au site</button></a>
	</div>
</section>