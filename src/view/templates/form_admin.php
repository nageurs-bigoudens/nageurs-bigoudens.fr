<?php
// src/view/templates/form_params.php 
declare(strict_types=1);
// note: l'id ici n'est pas celui du noeud bloc mais celui de l'entrée dans node_data correspondante
?>
<div class="admin_form">
	<p>
		<label for="keep_emails_<?= $node->getNodeData()->getId() ?>">Conserver les e-mails en base de données</label>
		<input type="checkbox" id="keep_emails_<?= $node->getNodeData()->getId() ?>" <?= ($keep_emails ?? false) ? 'checked' : '' ?> onclick="keepEmails(<?= $node->getNodeData()->getId() ?>)">
	</p>
	<p><i>Notez que ces enregistrements sont des données personnelles et sont concernés par le RGPD.</i></p>
	<p><a href="<?= new URL(['page' => 'emails']) ?>"><button>Consulter les e-mails enregistrés</button></a></p>
</div>
<div class="admin_form">
	<h3>Paramètres d'envoi</h3>
	<p>
	    <label for="smtp_host_<?= $node->getNodeData()->getId() ?>">Adresse serveur SMTP</label>
	    <input id="smtp_host_<?= $node->getNodeData()->getId() ?>" type="text" name="smtp_host" placeholder="smtp.messagerie.fr" value="<?= htmlspecialchars($smtp_host) ?>">
	    <input type="hidden" id="smtp_host_hidden_<?= $node->getNodeData()->getId() ?>" value="">
	    <button onclick="setEmailParam('smtp_host', <?= $node->getNodeData()->getId() ?>)">Valider</button>
	</p>
	<p>
		<label for="smtp_secure_<?= $node->getNodeData()->getId() ?>">Chiffrement</label>
		<select id="smtp_secure_<?= $node->getNodeData()->getId() ?>" name="smtp_secure" onchange="setEmailParam('smtp_secure', <?= $node->getNodeData()->getId() ?>)">
			<option value="plain_text" >Aucun (port 25)</option>
			<option value="tls" <?php echo htmlspecialchars($smtp_secure) === 'tls' ? 'selected' : '' ?>>StartTLS (port 587)</option>
			<option value="ssl" <?php echo htmlspecialchars($smtp_secure) === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
		</select>
		<input type="hidden" id="smtp_secure_hidden_<?= $node->getNodeData()->getId() ?>" value="">
	</p>
	<p>
	    <label for="smtp_username_<?= $node->getNodeData()->getId() ?>">Identifiant (adresse e-mail)</label>
	    <input id="smtp_username_<?= $node->getNodeData()->getId() ?>" type="email" name="smtp_username" autocomplete="new-password" placeholder="mon-adresse@email.fr" value="<?= htmlspecialchars($smtp_username) ?>">
	    <input type="hidden" id="smtp_username_hidden_<?= $node->getNodeData()->getId() ?>" value="">
	    <button onclick="setEmailParam('smtp_username', <?= $node->getNodeData()->getId() ?>)">Valider</button>
	</p>
	<p>
	    <label for="smtp_password_<?= $node->getNodeData()->getId() ?>">Mot de passe</label>
	    <input id="smtp_password_<?= $node->getNodeData()->getId() ?>" type="password" name="smtp_password" autocomplete="new-password">
	    <input type="hidden" id="smtp_password_hidden_<?= $node->getNodeData()->getId() ?>" value="">
	    <button onclick="setEmailParam('smtp_password', <?= $node->getNodeData()->getId() ?>)">Valider</button>
	</p>
	<p><i>Il s'agit du service qui acheminera les messages envoyés par ce formulaire. Les services d'envoi de courriels nécéssitent généralement de s'y connecter avec un identifiant et un mot de passe. Les adresses d'envoi et de réception peuvent être identiques. Le site web peut ne pas réussir à se connecter à certains fournisseurs.</i></p>
</div>
<div class="admin_form">
	<h3>Paramètres de réception</h3>
	<p>
	    <label for="email_dest_<?= $node->getNodeData()->getId() ?>">Adresse e-mail</label>
	    <input id="email_dest_<?= $node->getNodeData()->getId() ?>" type="email" name="email_dest" placeholder="mon-adresse@email.fr" value="<?= htmlspecialchars($email_dest) ?>">
	    <input type="hidden" id="email_dest_hidden_<?= $node->getNodeData()->getId() ?>" value="">
	    <button onclick="setEmailParam('email_dest', <?= $node->getNodeData()->getId() ?>)">Valider</button>
	</p>
</div>
<div class="admin_form">
	<p><button onclick="sendTestEmail(<?= $node->getNodeData()->getId() ?>)">Envoi d'un e-mail de test</button></p>
	<p><i>Vérifie la connexion au serveur d'envoi. Pour tester la réception, consultez vos e-mails à l'adresse de réception.</i></p>
	<p class="test_email_success_<?= $node->getNodeData()->getId() ?> full_width_column"></p>
</div>