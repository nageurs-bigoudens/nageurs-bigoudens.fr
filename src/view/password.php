<?php
// src/view/password.php
//
// ce fichier contient le HTML de deux pages du site:
// - connexion au mode admin
// - changement de mot de passe
//
// rajouter la page "créationn du mot de passe"?

declare(strict_types=1);

// insertion du captcha
$captchaHtml = '';
if(isset($captcha)) // instance de Captcha?
{
    ob_start();
    ?>
                <p>Montrez que vous n'êtes pas un robot.<br>
                    <label for="captcha" >Combien font <?= $captcha->getA() ?> fois <?= $captcha->getB() ?>?</label>
                    <input required type="text" id="captcha" name="captcha" autocomplete="off" size="1">
                </p>
    <?php
    $captchaHtml = ob_get_clean();
}


// formulaire connexion
$link = new URL(['page' => 'connexion']);
isset($_GET['from']) ? $link->addParams(['from' => $_GET['from']]) : '';
isset($_GET['id']) ? $link->addParams(['id' => $_GET['id']]) : '';
ob_start();
?>
            <form class="connexionFormulaire" method="post" action="<?= $link ?>" >
                <p><label for="login" >Identifiant (E-mail):</label>
                    <input id="login" type="text" name="login" autofocus required></p>
                <p><label for="password" >Mot de passe:</label>
                    <input id="password" type="password" name="password" required></p>

                <?= $captchaHtml ?>

                <input type="submit" value="Valider">
            </form>
<?php
$formulaireConnexion = ob_get_clean();

// formulaire création du mot de passe
ob_start();
?>
            <form class="connexionFormulaire" method="post" action="index.php" >
                <p><label for="login" >Identifiant (e-mail):</label>
                    <input id="login" type="text" name="login" autofocus required></p>
                <p><label for="password" >Mot de passe:</label>
                    <input id="password" type="password" name="password" required></p>

                <?= $captchaHtml ?>

                <input type="submit" value="Valider">
            </form>
<?php
$formulaireNouveauMDP = ob_get_clean();

// formulaire changement de mot de passe
$link = new URL(['action' => 'modif_mdp']);
isset($_GET['from']) ? $link->addParams(['from' => $_GET['from']]) : '';
isset($_GET['id']) ? $link->addParams(['id' => $_GET['id']]) : '';
ob_start();
?>
            <form class="connexionFormulaire" method="post" action="<?= $link ?>" >
                <label for="login" >Identifiant (e-mail):</label>
                <input id="login" type="login" name="login" autofocus required ><br><br>
                <label for="old_password" >Ancien mot de passe:</label>
                <input id="old_password" type="password" name="old_password" required ><br><br>
                <label for="new_password" >Nouveau mot de passe:</label>
                <input id="new_password" type="password" name="new_password" required autocomplete="off">
                <input type="hidden" name="modify_password_hidden">
                <br><br>
                <input type="submit" value="Valider" >
            </form>
<?php
$formulaireModifMDP = ob_get_clean();

// en-tête
ob_start();
?>
<!DOCTYPE html>

<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title><?= $title ?></title>

        <link rel="icon" type="image/png" href="assets/favicon48x48.png">
        <script src="js/main.js" ></script>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body{background-color: #E3F3FF;}
            #bloc_page{text-align: center;}
            .avertissement{color: red;}
        </style>
    </head>

    <body>
        <div id="bloc_page" >
            <h2 class="connexionTitre" ><?= $title ?></h2>
            <p class="connexionP" ><?= $subHeading ?></p>
<?php
$header = ob_get_clean();


$error_messages = [
    'error_non_valid_captcha' => '<p class="avertissement" >Erreur au test anti-robot, veuillez saisir un nombre entier.</p>',
    'bad_solution_captcha' => '<p class="avertissement" >Erreur au test anti-robot, veuillez réessayer.</p>',
    'bad_login_or_password' => '<p class="avertissement" >Mauvais identifiant ou mot de passe, veuillez réessayer.</p>', // ne pas indiquer où est l'erreur
    'forbidden_characters' => '<p class="avertissement" >Caractères interdits: espaces, tabulations, sauts CR/LF.</p>'
];

$warning_messages = [
    'message_disconnect' => "<p class='connexionP' ><i>N'oubliez de cliquer sur 'déconnexion' quand vous aurez fini.</i></p>",
    //'message_cookie' => "<p class='connexionP' style='color: red;'>Ce site utilise un cookie « obligatoire » lorsque vous êtes connecté ainsi que sur cette page.<br>Il sera supprimé à votre déconnexion ou dès que vous aurez quitté le site.</p>",
    'private_browsing' =>"<p class='connexionP' >Au fait? Vous n'utilisez pas votre propre ordinateur ou téléphone?<br/>
    Utilisez la navigation privée.</p>"
];


// confirmation modification du mot de passe
$page = isset($_GET['from']) ? $_GET['from'] : 'accueil';
$id = isset($_GET['id']) ? ', \'' . $_GET['id'] . '\'' : '';
$js = "newPassword('" . $page . "'" . $id . ");";
ob_start();
?>
<script><?= $js ?></script>
<noscript>
    <p class="avertissement" >Le mot de passe a été modifié<br>
        <a href="<?= $link ?>" ><button>Retour au site.</button></a></p>
</noscript>
<?php
$alertJSNewPassword = ob_get_clean();


// bas de la page
$link = new URL();
isset($_GET['from']) ? $link->addParams(['page' => $_GET['from'] ]) : '';
isset($_GET['id']) ? $link->addParams(['id' => $_GET['id']]) : '';
ob_start();
if(isset($_GET['from'])) // exclue la "création du mot de passe"
{
?>
            <p class="connexionP connexionFooter" >
                <a href="<?= $link ?>" >
                    <button>Retour au site.</button>
                </a>
            </p>
<?php
}
$footer = ob_get_clean();