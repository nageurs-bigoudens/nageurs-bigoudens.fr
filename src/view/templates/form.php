<?php declare(strict_types=1); ?>
<section class="form" id="<?= $this->id_node ?>">
	<?= $admin_content ?>
	<h3><?= $title ?></h3>
	<?= $no_recipient_warning ?>
	<form method="post" action="<?= $action_url ?>">
		<label for="email">Votre e-mail</label>
		<input type="email" name="email" placeholder="mon-adresse@email.fr" value="" required>

		<label for="subject">Objet</label>
		<input type="text" name="subject" value="" required>

		<label for="message">Votre message</label>
		<textarea type="text" name="message" rows="4" required></textarea>

		<div class="full_width_column">
			<label for="captcha" >Montrez que vous n'êtes pas un robot</label>
		</div>

		<label for="captcha" >Combien font <?= $captcha->getA() ?> fois <?= $captcha->getB() ?>?</label>
		<div>
			<input type="text" name="captcha" size="1" required>
		</div>

		<input type="hidden" name="form_id" value="">
		<input type="hidden" name="form_hidden">

		<div class="full_width_column">
			<input type="submit" value="Envoyez votre message">
		</div>
	</form>
</section>