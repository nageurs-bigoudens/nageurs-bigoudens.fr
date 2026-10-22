<?php declare(strict_types=1); ?>
<section class="show_emails">
	<h3>Table "<?= TABLE_PREFIX ?>email" de la base de données</h3>
	<p><i>
		Les e-mails ci-dessous sont des copies de ceux arrivés dans votre boite de messagerie depuis tous les formulaires existant sur le site. Ils sont conservés dans un but pratique (historique, prospection) ou dans tout autre but justifiant leur conservation. Cette conservation est optionnelle et règlementée par la loi européenne RGPD.<br>
		<b>Notez qu'utiliser cette application ne vous garantit pas d'être en conformité avec la loi, c'est un outil vous permettant de faire les choses comme vous l'entendez. Si tout ceci vous gêne, désactivez le stockage des e-mails et supprimez ceux déjà enregistrés (ci-dessous).</b>
	</i></p>
	<h4>Durées de conservation</h4>
	<p><i>
		Ce sont des durées maximales, les données peuvent être supprimées plus tôt ou même immédiatement. Le faire est d'ailleurs une obligation dans le cas où les personnes concernées le demandent.<br>
	</i></p>
	<p><i>
		Les e-mails ordinaires d'un même expéditeur (même adresse e-mail) sont tous supprimés simultanément lorsque le plus récent d'entre eux atteint les 3 ans (utilisateur est considéré inactif).<br>
		Les e-mails sensibles quand à eux sont supprimés 5 ans après être devenus sensibles (durée juridique d'une preuve).
	</i></p>
	<p><i>
		Un nettoyeur supprimant les messages dépassant ces durées est exécuté au moment de votre connexion au mode administrateur.<br>
		Si vos connexions sont rares, il est possible d'automatiser ce nettoyage à l'aide d'une tâche CRON. Pour cela, vous devez configurer le serveur pour qu'il exécute periodiquement la commande "php /chemin/du/site/bin/cron.php".
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