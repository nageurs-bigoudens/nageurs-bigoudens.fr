<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Site en maintenance</title>
        <link rel="icon" type="" href="">
        <meta name="description" content="site en maintenance">
        <meta name="viewport" content="width=device-width">
        <link rel="stylesheet" href="css/head.css">
        <link rel="stylesheet" href="css/body.css">
        <link rel="stylesheet" href="css/foot.css">
        <meta http-equiv="refresh" content="60" /> <!-- rafraîchissement automatique toutes les minutes -->
    </head>
    <body>
        <main>
        	<p>Le site est en cours de maintenance.</p>
        	<p>Il devrait être de nouveau accessible rapidement.</p>
            <?= !empty(Config::$email_dest) ? '<p>Contact: ' . Config::$email_dest . '</p>' : '' ?>
        </main>
        <footer>
            <div class="logged_out">
                <button>
                    <a href="<?= new URL(['page' => 'connection']) ?>">Mode admin</a>
                </button>
            </div>
        </footer>
    </body>
</html>