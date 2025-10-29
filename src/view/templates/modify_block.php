<?php declare(strict_types=1); ?>
<div class="modify_one_block" id="bloc_edit_<?= $child_node->getId() ?>">
    <div class="block_options">
        <label for="bloc_rename_<?= $child_node->getId() ?>">Type <b><?= Blocks::$blocks[$child_node->getName()] ?? '<i>erreur base de données</i>' ?></b>
        </label>
        <p>
            <input type="text" id="bloc_rename_<?= $child_node->getId() ?>" name="bloc_rename_title" value="<?= $child_node->getNodeData()->getdata()['title'] ?>" required>
            <button onclick="renamePageBloc(<?= $child_node->getId() ?>)">Renommer</button>
        </p>
		<div>
	        <p>
	            <img class="action_icon" onclick="switchBlocsPositions(<?= $child_node->getId() ?>, 'up')" src="assets/arrow-up.svg">
	            <img class="action_icon" onclick="switchBlocsPositions(<?= $child_node->getId() ?>, 'down')" src="assets/arrow-down.svg">
	        </p>
	        <form method="post" action="<?= new URL(['page' => CURRENT_PAGE]) ?>">
	            <input type="hidden" name="delete_bloc_id" value="<?= $child_node->getId() ?>">
	            <input type="hidden" name="delete_bloc_hidden">
	            <input type="submit" value="Supprimer" onclick="return confirm('Voulez-vous vraiment supprimer ce bloc?');">
	        </form>
	    </div>
	</div>
<?php
if($child_node->getName() === 'news_block'){
?>
	<div class="news_order">
		<label>Ordre des articles</label>
		<select id="articles_order_select_<?= $child_node->getId() ?>" onchange="articlesOrderSelect(<?= $child_node->getId() ?>)">
			<option value="antichrono" <?= $order === 'antichrono' ? 'selected' : '' ?>>Antichronologique</option>
			<option value="chrono" <?= $order === 'chrono' ? 'selected' : '' ?>>Chronologique</option>
		</select>
	</div>
<?php
}
if(Blocks::hasPresentation($child_node->getName())){
?>
    <div class="grid_options">
    	<div>
	        <label for="presentation_select_<?= $child_node->getId() ?>">Présentation</label>
	        <select id="presentation_select_<?= $child_node->getId() ?>" onchange="changePresentation(<?= $child_node->getId() ?>)">
				<?= $this->makePresentationOptions($child_node->getNodeData()->getPresentation()) ?>
	    	</select>
    	</div>
    	<div id="cols_min_width_edit_<?= $child_node->getId() ?>" class="<?= ($child_node->getNodeData()->getPresentation() === 'grid' ? '' : 'hidden') ?>">
        	<label for="cols_min_width_select_<?= $child_node->getId() ?>">Largeur minimum </label>
    		<input type="number" id="cols_min_width_select_<?= $child_node->getId() ?>" onchange="changeColsMinWidth(<?= $child_node->getId() ?>)" min="150" max="400" value="<?= $child_node->getNodeData()->getColsMinWidth() ?>"> pixels
		</div>
	</div>
	<div class="pagination_limit">
		<label for="pagination_limit_<?= $child_node->getId() ?>">
			Nombre max d'articles affichés
			<input type="number" id="pagination_limit_<?= $child_node->getId() ?>" name="pagination_limit" onchange="changePaginationLimit(<?= $child_node->getId() ?>)" min="0" max="30" value="<?= $child_node->getNodeData()->getPaginationLimit() ?>">
			<i>(mettre 0 désactive la pagination)</i>
		</label>
	</div>
<?php
}
?>
</div>