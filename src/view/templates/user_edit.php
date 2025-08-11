<?php declare(strict_types=1); ?>
		<section>
			<div class="user_edit_header">
				<div class="empty_column"></div>
				<h3 class="connexionTitre" >Mon compte</h3>
				<div>
					<img class="user_icon" src="assets/user_hollow.svg">
					<div><?= $_SESSION['user'] ?></div>
				</div>
			</div>
			<div class="user_edit_flex">
	            <div class="login_form">
		            <p class="connexionP" >Modifier mon nom d'utilisateur.</p>
		            <p style="color: red; font-style: italic;"><?= $error_username ?></p>
		            <p style="color: green; font-style: italic;"><?= $success_username ?></p>
					<form class="connexionFormulaire" method="post" action="<?= $link_user_form ?>" >
					    <p><label for="old_login" >Ancien nom:</label>
					        <input id="old_login" type="text" name="old_login" required></p>
					    <p><label for="password" >Mot de passe:</label>
		                	<input id="password" type="password" name="password" required ></p>
		                <p><label for="new_login" >Nouveau nom:</label>
					        <input id="new_login" type="text" name="new_login" required></p>
					    <input type="hidden" name="modify_username_hidden">

					    <p>Montrez que vous n'êtes pas un robot.<br>
		                    <label for="captcha" >Combien font <?= $captcha->getA() ?> fois <?= $captcha->getB() ?>?</label>
		                    <input required type="text" id="captcha" name="captcha" autocomplete="off" size="1">
		                </p>

					    <input type="submit" value="Valider">
					</form>
				</div>
				<div class="login_form">
					<p class="connexionP" >Modifier mon mot de passe.</p>
		            <p style="color: red; font-style: italic;"><?= $error_password ?></p>
		            <p style="color: green; font-style: italic;"><?= $success_password ?></p>
					<form class="connexionFormulaire" method="post" action="<?= $link_password_form ?>" >
						<p><label for="login" >Nom:</label>
					        <input id="login" type="text" name="login" required></p>
					    <p><label for="old_password" >Ancien mot de passe:</label>
		                	<input id="old_password" type="password" name="old_password" required ></p>
		                <p><label for="new_password" >Nouveau mot de passe:</label>
		                <input id="new_password" type="password" name="new_password" required autocomplete="off"></p>
		                <input type="hidden" name="modify_password_hidden">

					    <p>Montrez que vous n'êtes pas un robot.<br>
		                    <label for="captcha" >Combien font <?= $captcha->getA() ?> fois <?= $captcha->getB() ?>?</label>
		                    <input required type="text" id="captcha" name="captcha" autocomplete="off" size="1">
		                </p>

					    <input type="submit" value="Valider">
					</form>
				</div>
			</div>
			<div class="login_form">
				<p class="connexionP connexionFooter" >
				    <a href="<?= $link_exit ?>" >
				        <button>Retour au site</button>
				    </a>
				</p>
			</div>
		</section>