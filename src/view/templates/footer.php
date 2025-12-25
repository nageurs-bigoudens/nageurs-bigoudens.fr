<?php declare(strict_types=1); ?>
            <footer>
<?= $breadcrumb ?>
                <div class="data">
                    <div class="contact">
                        <div id="footer_name">
                            <span id="footer_name_content"><?= htmlspecialchars($name ?? '') ?></span>
                            <?= $admin_footer_name ?>
                        </div>
                        <div id="footer_address">
                            <span id="footer_address_content"><?= htmlspecialchars($address ?? '') ?></span>
                            <?= $admin_footer_address ?>
                        </div>
                        <div id="footer_email">
                            <a id="footer_email_content" href="mailto:<?= htmlspecialchars($email ?? '') ?>"><?= htmlspecialchars($email ?? '') ?></a>
                            <?= $admin_footer_email ?>
                        </div>
                    </div>
                    <div id="footer_logo">
                        <a href="<?= new URL ?>"><img id="footer_logo_content" src="<?= $footer_logo ?? '' ?>" alt=""></a>
                        <?= $admin_footer_logo ?>
                    </div>
<?php if($_SESSION['admin']){ ?>
                    <script>
                        let footer_name = new InputText('footer_name');
                        let footer_address = new InputText('footer_address');
                        let footer_email = new InputText('footer_email');
                        let footer_logo = new InputFile('footer_logo');
                    </script>
<?php } ?>
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