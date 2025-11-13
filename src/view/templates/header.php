<?php declare(strict_types=1); ?>
<body>
    <div>
	   <header style="background-image: url('<?= $header_background ?? '' ?>');">
            <div id="nav_zone">
                <div class="nav_button">
                    <button onclick="toggleTouchMenu()">MENU</button>
                </div>
                <?= $nav ?>
            </div>
            <div class="header_above">
                <div id="head_favicon">
                    <?= $admin_head_favicon ?>
                </div>
                <div id="header_background">
                    <?= $admin_header_background ?>
                </div>
            </div>
            <div class="header_content">
                <div class="header_left_col">
                    <div id="header_logo">
                        <a href="<?= new URL ?>"><img id="header_logo_content" src="<?= $header_logo ?? '' ?>" alt="header_logo"></a>
                        <?= $admin_header_logo ?>
                    </div>
                </div>
                <div class="header_center_col">
                    <h1 id="header_title">
                        <a id="header_title_content" href="<?= new URL ?>"><?= htmlspecialchars($title ?? '') ?></a>
                        <?= $admin_header_title ?>
                    </h1>
                    <h2 id="header_description">
                        <span id="header_description_content"><?= htmlspecialchars($description ?? '') ?></span>
                        <?= $admin_header_description ?>
                    </h2>
                </div>
                <div class="header_right_col">
                    <div id="header_social">
                        <div id="header_social_content">
                            <?= $social_networks ?>
                        </div>
                    </div>
                    <?= $breadcrumb ?? '' ?>
                </div>
            </div>
<?php if($_SESSION['admin']){ ?>
            <script>
                document.getElementById("head_favicon_content").src = window.Config.favicon;
                let head_favicon = new InputFileFavicon('head_favicon');
                let header_background = new InputFileHeaderBackground('header_background');
                let header_logo = new InputFile('header_logo');
                let header_title = new InputText('header_title');
                let header_description = new InputText('header_description');
            </script>
<?php } ?>
        </header>