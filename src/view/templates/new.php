<?php declare(strict_types=1); ?>
<article>
    <div class="new_content">
        <div class="article_title_zone">
            <?= $share_button ?>
            <div class="data" id="<?= $id_title ?>">
                <?= $title ?>
            </div>
            <?= $title_buttons ?>
        </div>
        <div class="data new_content_text" id="<?= $id_preview ?>">
            <?= $preview ?>
        </div>
        <?= $preview_buttons ?>
        <div class="data article_content_text" id="<?= $id ?>">
            <?= $content ?>
        </div>
        <?= $article_buttons ?>
        <div class="under_an_article">
            <p>
                <img src="assets/calendar.svg">
                <span class="data" id="<?= $id_date ?>"><?= $date ?></span>
            </p>
        </div>
        <?= $date_buttons ?>
        <div class="article_admin_zone">
            <?= $admin_buttons ?>
        </div>
    </div>
</article>