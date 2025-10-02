<?php declare(strict_types=1); ?>
<body>
    <div>
	   <header style="background-image: url('<?= $header_background ?>');">
            <div id="nav_zone">
                <?= $nav ?>
            </div>

            <div class="header-content">
                <div class="head_logo">
                    <a href="<?= new URL ?>"><img src="<?= $header_logo ?>" alt="logo_alt"></a>
                </div>
                <div class="nav_button">
                    <button>MENU</button>
                </div>
                <div class="site_title">
                    <a href="<?= new URL ?>"><h1><?= $title ?></h1></a>
                    <h2><?= $description ?></h2>
                </div>
                <div>
                    <div class="social">
                        <?= $social_networks ?>
                    </div>
                    <?= $breadcrumb ?? '' ?>
                </div>
            </div>
        </header>