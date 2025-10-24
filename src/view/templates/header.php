<?php declare(strict_types=1); ?>
<body>
    <div>
	   <header style="background-image: url('<?= $header_background ?? '' ?>');">
            <div id="nav_zone">
                <?= $nav ?>
            </div>
            <div class="editing_zone">
                <div id="head_favicon" style="margin: <?= $editing_zone_margin ?>;">
                    <input type="file" id="head_favicon_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff, image/x-icon, image/bmp">
                    <?= $buttons_favicon ?>
                </div>
                <div id="header_background">
                    <input type="file" id="header_background_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff">
                    <?= $buttons_background ?>
                </div>
            </div>
            <div class="header_content">
                <div class="header_left_col">
                    <div id="header_logo">
                        <a href="<?= new URL ?>"><img id="header_logo_img" src="<?= $header_logo ?? '' ?>" alt="header_logo"></a>
                        <input type="file" id="header_logo_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff">
                        <?= $buttons_header_logo ?>
                    </div>
                </div>
                <div class="nav_button">
                    <button>MENU</button>
                </div>
                <div class="site_title">
                    <h1 id="header_title">
                        <a href="<?= new URL ?>"><span id="header_title_span"><?= htmlspecialchars($title ?? '') ?></span></a>
                        <input type="text" id="header_title_input" class="hidden" value="<?= htmlspecialchars($title ?? '') ?>" size="30">
                        <?= $buttons_header_title ?>
                    </h1>
                    <h2 id="header_description">
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
<?php if($_SESSION['admin']){ ?>
            <script>
                let head_favicon = new InputFile('head_favicon');
                let header_background = new InputFile('header_background');
                let header_logo = new InputFile('header_logo');
                let header_title = new InputText('header_title');
                let header_description = new InputText('header_description');
            </script>
<?php } ?>
        </header>