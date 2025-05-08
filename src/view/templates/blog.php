<?php declare(strict_types=1); ?>
<section class="blog" id="<?= $this->id_node ?>">
	<h3><?= $title ?></h3>
<?= $new_article ?>
	<script>
		var clone<?= $this->id_node ?> = document.currentScript.previousElementSibling.cloneNode(true);
	</script>
	<div>
<?= $content ?>
	</div>
</section>