<?php declare(strict_types=1); ?>
<section>
	<h3>Maintenance du site</h3>
	<div class="basic_div">
		<p>
			<button onclick="displayLogs()">Consulter les journaux de connexion</button><br>
			<i>Qui a essayé de se connecter, quand et a-t'il réussi?</i>
		</p>
		<p>
			<button onclick="cleanLogs()">Effacer les journaux de connexion</button>
		</p>
		<div id="log_table"></div>
	</div>
	<div class="basic_div">
		<p>
			<a href="http://nageurs.localhost/index.php?page=emails"><button>Consulter les emails</button></a><br>
			<i>Emails reçus depuis tous les formulaires de contact</i>
		</p>
	</div>

	<div class="basic_div">
		<a href="http://nageurs.localhost/index.php"><button>Retour au site</button></a>
	</div>
</section>