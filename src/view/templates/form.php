<?php declare(strict_types=1); ?>
<section class="form" id="<?= $this->id_node ?>">
	<h3><?= $title ?></h3>
	<?= $admin_content ?>
	<div class="form_inputs">
		<label for="email_name_<?= $node->getNodeData()->getId() ?>">Votre nom</label>
		<input id="email_name_<?= $node->getNodeData()->getId() ?>" type="text" name="email_name" value="">
		
		<label for="email_address_<?= $node->getNodeData()->getId() ?>">Votre e-mail</label>
		<input id="email_address_<?= $node->getNodeData()->getId() ?>" type="email" name="email_address" placeholder="mon-adresse@email.fr" value="" onchange="checkCase(<?= $node->getNodeData()->getId() ?>)">

		<label for="email_message_<?= $node->getNodeData()->getId() ?>">Votre message</label>
		<textarea id="email_message_<?= $node->getNodeData()->getId() ?>" type="text" name="email_message" rows="4"></textarea>

		<div class="full_width_column">
			<label for="email_captcha_<?= $node->getNodeData()->getId() ?>" >Montrez que vous n'êtes pas un robot</label>
		</div>

		<label for="email_captcha_<?= $node->getNodeData()->getId() ?>" >Combien font <?= self::$captcha->getA() ?> fois <?= self::$captcha->getB() ?>?</label>
		<div>
			<input id="email_captcha_<?= $node->getNodeData()->getId() ?>" type="text" name="email_captcha" size="1" autocomplete="off">
		</div>

		<input id="form_id_hidden" type="hidden" name="form_id_hidden" value="">
		<input id="email_hidden_<?= $node->getNodeData()->getId() ?>" type="hidden" name="email_hidden">

		<div class="full_width_column">
			<input type="submit" value="Envoyez votre message" onclick="sendVisitorEmail(<?= $node->getNodeData()->getId() ?>)">
		</div>

		<p class="send_email_success_<?= $node->getNodeData()->getId() ?> full_width_column"></p>
	</div>
	<p id="form_warning_<?= $node->getNodeData()->getId() ?>" class="form_warning <?= ($keep_emails ?? false) ? '' : 'hidden' ?>"><i>
		Une copie de votre e-mail (nom, adresse et message) sera conservée dans notre base de données dans le but de pouvoir répondre à votre demande et éventuellement dans un but de prospection. Ces données seront traitées automatiquement par notre serveur et conservées pendant au maximum 3 ans à compter de votre dernier message.<br>
		Ce traitement repose sur votre consentement. Vous pouvez consulter, modifier ou supprimer vos données en base de données sur simple demande.
	</i></p>
</section>