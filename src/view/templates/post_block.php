<?php declare(strict_types=1); ?>
<section class="<?= $section_class ?>" block-type="<?= $node->getName() ?>" id="<?= $this->id_node ?>">
	<h3><?= $title ?></h3>
<?= $new_article ?>
	<script>
		var clone<?= $this->id_node ?> = document.currentScript.previousElementSibling.cloneNode(true);
	</script>
	<div class="section_child" style="<?= $cols_min_width ?>">
<?= $content ?>
	</div>
	<div class="fetch_articles">
		<button class="<?= $fetch_button_hidden ?>" onclick="fetchArticles(<?= $this->id_node ?>)">Articles suivants</button>
	</div>
</section>