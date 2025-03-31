            <footer>
<?= $breadcrumb ?>
                <div>
                    <p class="contact"><?= $contact_nom ?><br>
                        <?= $adresse ?><br>
                        <a href="mailto:<?= $e_mail ?>"><?= $e_mail ?></a></p>
                    <p class="footer_logo"><img src="<?= $logo_footer ?>" alt="logo"><p>
                </div>
            </footer>
                <div class="<?= $empty_admin_zone ?>"></div>
                <div class="<?= $div_admin ?>">
<?= $zone_admin ?>
                </div>
        </div>
    </body>
</html>