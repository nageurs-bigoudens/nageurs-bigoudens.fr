<?php declare(strict_types=1); ?>
<section class="menu">
	<h3>Menu et chemins</h3>
	<div class="new_page_button">
		<p>Créer une <a href="<?= new URL(['page' => 'nouvelle_page']) ?>"><button style="color: #ff1d04;">Nouvelle page</button></a>.</p>
	</div>
	<div class="url_form_zone">
		<p>Créer une entrée dans le menu avec une adresse vers un site quelconque:</p>
		<form method="post" action="<?= new URL(['from' => 'menu_chemins']) ?>">
			<p>
				<label for="label_input">Nom dans le menu:</label>
				<input id="label_input" type="text" name="label_input">
			</p>
			<p>
				<label for="url_input">Adresse (collez votre lien):</label>
				<input id="url_input" type="url" name="url_input" placeholder="http://">
			</p>
			<p>
				<label>Placer le lien juste après cette entrée:</label>
				<select id="location" name="location">
					<?= $this->options ?>
				</select>
			</p>
			<input type="submit" onclick="createUrlEntry()" value="Valider">
		</form>
	</div>
	<aside>
		<p>Modifier le menu:</p>
		<div class="controls_explanations">
			<p><img src="assets/arrow-left.svg"> remonter dans l'arbre</p>
		    <p><img src="assets/arrow-right.svg"> devenir une branche de l'élément précédent</p>
			<p><img src="assets/arrow-up.svg"><img src="assets/arrow-down.svg"> déplacer la branche parmi celles de même niveau</p>
		    <p><input type="checkbox" checked> afficher/cacher</p>
		    <p><img src="assets/edit.svg"> modifier un lien</p>
		    <p><img src="assets/delete-bin.svg"> supprimer un lien</p>
		</div>
	</aside>
	<div id="menu_edit_buttons">
<?= $this->html ?>
	</div>
</section>
<section>
	<div class="basic_div">
		<a href="<?= new URL ?>">
			<button>Retour au site</button>
		</a>
	</div>
</section>