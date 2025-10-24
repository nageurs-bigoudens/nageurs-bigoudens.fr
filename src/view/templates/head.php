<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <script>window.Config = {page: "<?= CURRENT_PAGE ?>", favicon: "<?= $favicon ?>"};</script>
        <meta charset="utf-8">
        <title><?= $title ?></title>
        <link rel="icon" type="<?= $favicon_type ?>" href="<?= $favicon ?>">
        <meta name="description" content="<?= $description ?>">
        <meta name="viewport" content="width=device-width">
        <?= $css ?>
        <?= $js ?>
    </head>