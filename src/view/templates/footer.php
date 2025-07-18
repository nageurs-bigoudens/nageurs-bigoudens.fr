<?php declare(strict_types=1); ?>
            <footer>
<?= $breadcrumb ?>
                <div>
                    <p class="contact"><?= $contact_nom ?><br>
                        <?= $adresse ?><br>
                        <a href="mailto:<?= $e_mail ?>"><?= $e_mail ?></a></p>
                    <p class="footer_logo"><img src="<?= $footer_logo ?>" alt="logo"><p>
                </div>
                <div class="<?= $empty_admin_zone ?>"></div>
                <div class="<?= $div_admin ?>">
<?= $zone_admin ?>
                </div>
                <div id="toast"></div>
            </footer>
        </div>
    </body>
</html>