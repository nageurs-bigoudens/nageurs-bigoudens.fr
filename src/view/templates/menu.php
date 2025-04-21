<section class="menu">
	<h3>Menu et chemins</h3>
	<aside>
		<p><img src="assets/arrow-left.svg"> remonter dans l'arbre</p>
	    <p><img src="assets/arrow-right.svg"> devenir une branche de l'élément précédent</p>
		<p><img src="assets/arrow-up.svg"><img src="assets/arrow-down.svg"> déplacer la branche parmi celles de même niveau</p>
	    <p><input type="checkbox" checked>afficher/cacher</p>
	</aside>
<?= $this->html ?>
	<div class="new_entry_buttons">
		<p>Ajouter une nouvelle entrée dans le menu?</p>
		<button id="new-i..." onclick="openEditor('i...')"><img class="action_icon" src="assets/edit.svg">avec une URL</button>
			...sinon cliquer sur Nouvelle page<img src="assets/arrow-down.svg">dans la barre jaune
	</div>
</section>