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
				<button id="get_mysqldump">Télécharger la base de données</button>
			</a><br>
			<i>Réalise un "mysqldump", vous obtiendrez un unique fichier contenant toute la BDD.</i>
		</p>
	</div>

	<div class="basic_div">
		<a href="http://nageurs.localhost/index.php"><button>Retour au site</button></a>
	</div>
</section>