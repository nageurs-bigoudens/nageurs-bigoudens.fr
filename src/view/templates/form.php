<?php declare(strict_types=1); ?>
<section class="form" id="<?= $this->id_node ?>">
	<?= $admin_content ?>
	<h3><?= $title ?></h3>
	<div class="form_inputs">
		<label for="email_name">Votre nom</label>
		<input id="email_name" type="text" name="email_name" value="" required>
		
		<label for="email_address">Votre e-mail</label>
		<input id="email_address" type="email" name="email_address" placeholder="mon-adresse@email.fr" value="" required>

		<label for="email_message">Votre message</label>
		<textarea id="email_message" type="text" name="email_message" rows="4" required></textarea>

		<div class="full_width_column">
			<label for="captcha" >Montrez que vous n'êtes pas un robot</label>
		</div>

		<label for="email_captcha" >Combien font <?= $captcha->getA() ?> fois <?= $captcha->getB() ?>?</label>
		<div>
			<input id="email_captcha" type="text" name="email_captcha" size="1" required>
		</div>

		<input id="form_id_hidden" type="hidden" name="form_id_hidden" value="">
		<input id="email_hidden" type="hidden" name="email_hidden">

		<div class="full_width_column">
			<input type="submit" value="Envoyez votre message" onclick="sendVisitorEmail(<?= $node->getNodeData()->getId() ?>)">
		</div>

		<p class="send_email_success full_width_column"></p>
	</div>
</section>