<body>
    <div>
	   <header>
            <div class="empty_nav_zone">
                <?= $nav ?>
            </div>

            <div class="header-content">
                <div class="head_logo">
                    <a href="<?= new URL ?>"><img src="<?= $logo ?>" alt="<?= $logo_alt ?>"></a>
                </div>
                <div class="site_title">
                    <a href="<?= new URL ?>"><h1><?= $title ?></h1></a>
                    <h2><?= $description ?></h2>
                </div>
                <div class="social">
                    <a href="<?= $facebook_link ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?= $facebook ?>" alt="<?= $facebook_alt ?>"></a>
                    <a href="<?= $instagram_link ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?= $instagram ?>" alt="<?= $instagram_alt ?>"></a>
                </div>
            </div>
        </header>