<?php declare(strict_types=1); ?>
<section class="<?= $section_class ?>" id="<?= $this->id_node ?>">
	<h3><?= $title ?></h3>
<?= $new_article ?>
	<script>
		var clone<?= $this->id_node ?> = document.currentScript.previousElementSibling.cloneNode(true);
	</script>
	<div class="section_child" style="<?= $cols_min_width ?>">
<?= $content ?>
	</div>
</section>