<?php declare(strict_types=1); ?>
<section class="show_emails">
	<h3>Table "<?= TABLE_PREFIX ?>email" de la base de données</h3>
	<p><i>
		Les e-mails ci-dessous sont des copies de ceux arrivés dans votre boite de messagerie. Ils sont conservés dans un but pratique et éventuellement dans un but de prospection, ou dans tout autre but permettant de justifier leur conservation.<br>
		Ils sont "effacés" automatiquement au bout d'un certain temps comme le requièrt le RGPD. Un nettoyeur est exécuté à chaque connexion au mode admin ou éventuellement à l'aide d'une tâche CRON (le serveur doit pour ça être configuré pour exécuter periodiquement la commande "php /chemin/du/site/bin/cron.php").
	</i></p>
	<h4>Durées de conservation</h4>
	<p><i>
		Ce sont des durées maximales, les données peuvent être supprimées plus tôt ou même immédiatement. Le faire est d'ailleurs une obligation dans le cas où leur expéditeur le demande.<br>
		Théoriquement, ce même nettoyage des vieux messages devrait être également réalisé par vous-même dans votre boite de messagerie.
	</i></p>
	<p><i>
		Les e-mails ordinaires d'un même expéditeur (même adresse e-mail) sont tous supprimés simultanément lorsque le plus récent d'entre eux atteint les 3 ans (utilisateur "inactif").<br>
		Les e-mails sensibles quand à eux sont supprimés 5 ans après être devenus sensibles (durée juridique d'une preuve).
	</i></p>
	<h4>Données sensibles</h4>
	<p><i>
		Un e-mail peut-être considéré comme "sensible". Vous pouvez rendre un e-mail sensible lorsqu'il possède une valeur de preuve dans le cas d'un litige.<br>
		Lorsqu'une personne demande la suppression de ses données personnelles du serveur, les e-mails sensibles peuvent être conservés, vous aurez noté que la durée de conservation est calculée différement.
	</i></p>
	<p><i>
		Les spams ne sont pas sensibles, c'est juste de la pollution, supprimez-les!
	</i></p>

	<?= $emails ?>
</section>