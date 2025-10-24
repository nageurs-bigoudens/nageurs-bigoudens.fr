<?php declare(strict_types=1); ?>
            <footer>
<?= $breadcrumb ?>
                <div class="data">
                    <div class="contact">
                        <div id="footer_name">
                            <span id="footer_name_span"><?= htmlspecialchars($name ?? '') ?></span>
                            <input type="text" id="footer_name_input" class="hidden" value="<?= htmlspecialchars($name ?? '') ?>" size="30">
                            <?= $buttons_footer_name ?>
                        </div>
                        <div id="footer_address">
                            <span id="footer_address_span"><?= htmlspecialchars($address ?? '') ?></span>
                            <input type="text" id="footer_address_input" class="hidden" value="<?= htmlspecialchars($address ?? '') ?>" size="30">
                            <?= $buttons_footer_address ?>
                        </div>
                        <div id="footer_email">
                            <a href="mailto:<?= $email ?>"><span id="footer_email_span"><?= htmlspecialchars($email ?? '') ?></span></a>
                            <input type="text" id="footer_email_input" class="hidden" value="<?= htmlspecialchars($email ?? '') ?>" size="30">
                            <?= $buttons_footer_email ?>
                        </div>
                    </div>
                    <div id="footer_logo">
                        <a href="<?= new URL ?>"><img id="footer_logo_img" src="<?= $footer_logo ?? '' ?>" alt="logo_alt"></a>
                        <input type="file" id="footer_logo_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff">
                        <?= $buttons_footer_logo ?>
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