<?php
declare(strict_types=1);

$calendar_js_files = [
    'fullcalendar/packages/core/index.global.min',
    'fullcalendar/packages/daygrid/index.global.min',
    'fullcalendar/packages/timegrid/index.global.min',
    'fullcalendar/packages/list/index.global.min',
    'fullcalendar/packages/interaction/index.global.min',
    'fullcalendar/packages/core/locales/fr.global.min'
];
if($_SESSION['admin'] === true){
    $calendar_js_files[] = 'calendar_admin';
}
else{
    $calendar_js_files[] = 'calendar';
}
?>
<section class="calendar" id="<?= $this->id_node ?>">
<?php foreach($calendar_js_files as $file){
    echo HeadBuilder::insertJS($file);
} ?>
	<h3><?= $title ?></h3>
	<div id="calendar_zone">
        <div id="calendar"></div>
        
        <!-- si admin -->
        <aside id="event_modal" class="modal"></aside>
    </div>
</section>