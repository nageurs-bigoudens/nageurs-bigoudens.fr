<?php declare(strict_types=1); ?>
<body>
    <div>
	   <header style="background-image: url('<?= $header_background ?? '' ?>');">
            <div id="nav_zone">
                <?= $nav ?>
            </div>
            <div>
                <div id="head_favicon" style="margin: <?= $editing_zone_margin ?>;">
                    <?= $admin_favicon ?>
                </div>
                <div id="header_background">
                    <?= $admin_background ?>
                </div>
            </div>
            <div class="header_content">
                <div class="header_left_col">
                    <div id="header_logo">
                        <a id="header_logo_content" href="<?= new URL ?>"><img src="<?= $header_logo ?? '' ?>" alt="header_logo"></a>
                        <?= $admin_header_logo ?>
                    </div>
                </div>
                <div class="nav_button">
                    <button>MENU</button>
                </div>
                <div class="header_center_col">
                    <h1 id="header_title">
                        <a id="header_title_content" href="<?= new URL ?>"><?= htmlspecialchars($title ?? '') ?></a></span>
                        <?= $admin_header_title ?>
                    </h1>
                    <h2 id="header_description">
                        <span id="header_description_content"><?= htmlspecialchars($description ?? '') ?></span>
                        <?= $admin_header_description ?>
                    </h2>
                </div>
                <div class="header_right_col">
                    <div id="header_social">
                        <?= $social_networks ?>
                        <?= $admin_social_networks ?>
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
                let header_social = new InputFile('header_social');
            </script>
<?php } ?>
        </header>