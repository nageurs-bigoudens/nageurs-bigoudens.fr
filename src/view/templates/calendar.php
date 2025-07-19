<?php declare(strict_types=1); ?>
<section class="calendar" id="<?= $this->id_node ?>">
	<script src='js/fullcalendar/packages/core/index.global.min.js'></script>
    <script src='js/fullcalendar/packages/daygrid/index.global.min.js'></script>
    <script src='js/fullcalendar/packages/timegrid/index.global.min.js'></script>
    <script src='js/fullcalendar/packages/list/index.global.min.js'></script>
    <script src='js/fullcalendar/packages/interaction/index.global.min.js'></script>
    <script src='js/fullcalendar/packages/core/locales/fr.global.min.js'></script>
<?php
if($_SESSION['admin'] === true){
    echo '<script src="' . HeadBuilder::versionedFileURL('js', 'calendar_admin') . '"></script>' . "\n";
}
else{
    echo '<script src="' . HeadBuilder::versionedFileURL('js', 'calendar') . '"></script>' . "\n";
}
?>
	<h3><?= $title ?></h3>
	<div id="calendar_zone">
        <div id="calendar"></div>
        
        <!-- si admin -->
        <aside id="event_modal" class="modal"></aside>
    </div>
</section>