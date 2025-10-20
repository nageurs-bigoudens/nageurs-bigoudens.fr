<?php declare(strict_types=1); ?>
<section class="new_page">
	<h3>Création d'une nouvelle page</h3>
	<div class="form_zone">
		<form method="post" action="<?= new URL ?>">
			<p>
				<label for="page_name">Nom de la page</label>
				<input id="page_name" type="text" name="page_name" onchange="makePageNamePath()" required>
			</p>
			<p>
				<label for="page_name_path">Chemin de l'URL</label>
				<input id="page_name_path" type="text" name="page_name_path" placeholder="ex: nouvelle_page" required>
			</p>
			<p>
				<label for="page_location">Placer la page juste après cette entrée</label>
				<select id="page_location" name="page_location">
					<?= $this->options ?>
				</select>
			</p>
			<input type="hidden" name="new_page_hidden">
			<p>
				<label class="label_textarea" for="page_description">Description qui apparaît sous le titre dans les moteurs de recherche</label>
				<textarea id="page_description" name="page_description" cols="40" rows="3" placeholder="ex: nous faisons ceci et cela, etc"></textarea>
			</p>
			<input type="submit" value="Créer la page">
			<button onclick="window.location.href='<?= new URL ?>';">Retour au site</button><!-- le JS est un hack pour contourner le blocage du bouton par les "required" -->
		</form>
	</div>
</section>