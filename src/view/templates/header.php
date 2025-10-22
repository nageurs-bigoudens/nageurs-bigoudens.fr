<?php declare(strict_types=1); ?>
<body>
    <div>
	   <header style="background-image: url('<?= $header_background ?? '' ?>');">
            <div id="nav_zone">
                <?= $nav ?>
            </div>

            <div class="header-content">
                <div class="header_left_col">
                    <div id="edit_favicon_zone" class="<?= $edit_favicon_hidden ?>">
                        <?= $button_favicon ?>
                    </div>
                    <div>
                        <a href="<?= new URL ?>"><img id="header_logo" src="<?= $header_logo ?>" alt="logo_alt"></a>
                        <?= $button_header_logo ?>
                    </div>
                </div>
                <div class="nav_button">
                    <button>MENU</button>
                </div>
                <div class="site_title">
                    <h1 id="header_title">
                        <script>let header_title = new InputText('header_title');</script>
                        <a href="<?= new URL ?>"><span id="header_title_span"><?= htmlspecialchars($title ?? '') ?></span></a>
                        <input type="text" id="header_title_input" class="hidden" value="<?= htmlspecialchars($title ?? '') ?>" size="30">
                        <?= $buttons_header_title ?>
                    </h1>
                    <h2 id="header_description">
                        <script>let header_description = new InputText('header_description');</script>
                        <span id="header_description_span"><?= htmlspecialchars($description ?? '') ?></span>
                        <input type="text" id="header_description_input" class="hidden" value="<?= htmlspecialchars($description ?? '') ?>" size="30">
                        <?= $buttons_header_description ?>
                    </h2>
                </div>
                <div class="header_right_col">
                    <div class="social">
                        <?= $social_networks ?>
                        <?= $buttons_social_networks ?>
                    </div>
                    <?= $breadcrumb ?? '' ?>
                </div>
            </div>
        </header>