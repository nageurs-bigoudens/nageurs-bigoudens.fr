<?php declare(strict_types=1); ?>
		<section>
            <h3 class="connexionTitre" >Connexion à l'espace d'administration</h3>
            <div class="login_form">
	            <p class="connexionP" >Veuillez saisir votre identifiant et votre mot de passe.</p>
	            <p style="color: red; font-style: italic;"><?= $error ?></p>
				<form class="connexionFormulaire" method="post" action="<?= $link_form ?>" >
				    <p><label for="login" >Identifiant:</label>
				        <input id="login" type="text" name="login" autofocus required></p>
				    <p><label for="password" >Mot de passe:</label>
				        <input id="password" type="password" name="password" required></p>

				    <p>Montrez que vous n'êtes pas un robot.<br>
	                    <label for="captcha" >Combien font <?= $captcha->getA() ?> fois <?= $captcha->getB() ?>?</label>
	                    <input required type="text" id="captcha" name="captcha" autocomplete="off" size="1">
	                </p>

				    <input type="submit" value="Valider">
				</form>
				<p class='connexionP' >Au fait? Vous n'utilisez pas votre propre ordinateur ou téléphone?<br/>
				    Utilisez la navigation privée.</p>
				<p class="connexionP connexionFooter" >
				    <a href="<?= $link_exit ?>" >
				        <button>Retour au site</button>
				    </a>
				</p>
			</div>
		</section>