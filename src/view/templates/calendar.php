<?php declare(strict_types=1); ?>
<section class="calendar" id="<?= $this->id_node ?>">
    <?= HeadBuilder::insertJS('fullcalendar/index.global.min') ?>
    <?= HeadBuilder::insertJS('fullcalendar/fr.global.min') ?>
    <?= HeadBuilder::insertJS($calendar_js_file) ?>
	<h3><?= $title ?></h3>
	<div id="calendar_zone">
        <div id="calendar"></div>
        <aside id="event_modal" class="modal"></aside>
    </div>
</section>