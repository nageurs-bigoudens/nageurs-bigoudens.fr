<?php declare(strict_types=1); ?>
            <footer>
<?= $breadcrumb ?>
                <div class="data">
                    <div class="contact">
                        <div id="footer_name">
                            <script>let footer_name = new InputText('footer_name');</script>
                            <span id="footer_name_span"><?= htmlspecialchars($name ?? '') ?></span>
                            <input type="text" id="footer_name_input" class="hidden" value="<?= htmlspecialchars($name ?? '') ?>" size="30">
                            <?= $buttons_footer_name ?>
                        </div>
                        <div id="footer_address">
                            <script>let footer_address = new InputText('footer_address');</script>
                            <span id="footer_address_span"><?= htmlspecialchars($address ?? '') ?></span>
                            <input type="text" id="footer_address_input" class="hidden" value="<?= htmlspecialchars($address ?? '') ?>" size="30">
                            <?= $buttons_footer_address ?>
                        </div>
                        <div id="footer_email">
                            <script>let footer_email = new InputText('footer_email');</script>
                            <a href="mailto:<?= $e_mail ?>"><span id="footer_email_span"><?= htmlspecialchars($email ?? '') ?></span></a>
                            <input type="text" id="footer_email_input" class="hidden" value="<?= htmlspecialchars($email ?? '') ?>" size="30">
                            <?= $buttons_footer_email ?>
                        </div>
                    </div>
                    <p class="footer_logo"><img src="<?= $footer_logo ?>" alt="logo"></p>
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