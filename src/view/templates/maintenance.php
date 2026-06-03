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
			<i>Obtenir un fichier SQL à conserver sur votre ordinateur. Une sauvegarde (désignée par "auto") est réalisée à chaque visite de cette page.</i>
		</p>
	</div>
	<div class="basic_div">
		<p>Restaurer la base de données à partir d'un fichier SQL.<br>
			<i>Attention, l'actuelle BDD sera écrasée! (à l'exception de la table <?= TABLE_PREFIX ?>user)</i>
		</p>
		<form action="<?= new URL(['from' => 'maintenance', 'action' => 'restore_database']) ?>" method="post">
			<label for="selected_sql">Utiliser une sauvegarde conservée sur le serveur:</label><br>
			<select id="selected_sql" name="selected_sql">
				<?= $backup_options ?>
			</select>
			<input type="hidden" name="hidden" value="">
			<input type="submit" value="Valider" onclick="return confirm('Voulez-vous vraiment restaurer la base de données? Toutes les données seront supprimées et remplacées par les nouvelles.')">
		</form>
		<form enctype="multipart/form-data" action="<?= new URL(['from' => 'maintenance', 'action' => 'restore_database']) ?>" method="post">
			<label for="uploaded_sql">Utiliser un fichier sur votre ordinateur:</label><br>
			<input id="uploaded_sql" type="file" accept=".sql" name="uploaded_sql">
			<input type="hidden" name="hidden" value="">
			<input type="submit" value="Valider" onclick="return confirm('Voulez-vous vraiment restaurer la base de données? Toutes les données seront supprimées et remplacées par les nouvelles.')">
		</form>
	</div>
	<div class="basic_div">
		<p>
			<a href="<?= new URL(['from' => 'maintenance', 'action' => 'get_all_media']) ?>">
				<button id="get_all_media">Récupérer l'ensemble des fichiers mutimedia</button>
			</a><br>
			<i>Toutes vos photos et vos documents dans un .zip</i>
		</p>
		<p>
			<button onclick="openExplorer()" style="color: grey">Explorateur de fichiers</button><br>
			<i>Gérer les fichiers multimedia, fonction actuellement indisponible</i>
		</p>
	</div>

	<div class="basic_div">
		<a href="http://nageurs.localhost/index.php"><button>Retour au site</button></a>
	</div>
</section>