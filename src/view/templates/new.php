<article>
    <div class="new_content">
        <div class="article_title_zone">
            <h4><?= $title ?></h4>
            <?= $share_button ?>
        </div>
        <div class="new_content_text">
            <?= $preview ?>
        </div>
        <div id="<?= $id ?>" class="article_content_text">
            <?= $content ?>
        </div>
        <div class="under_an_article">
            <p><img src="assets/calendar.svg"><?= $date ?></p>
            <?= $from_to_button ?>
        </div>
        <div class="article_admin_zone">
            <?= $admin_buttons ?>
        </div>
    </div>
</article>