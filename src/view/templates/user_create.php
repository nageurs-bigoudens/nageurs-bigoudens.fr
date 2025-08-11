<?php
declare(strict_types=1);

$error_messages = [
    'error_non_valid_captcha' => 'Erreur au test anti-robot, veuillez saisir un nombre entier.',
    'captcha_server_error' => 'captcha_server_error',
    'bad_solution_captcha' => 'Erreur au test anti-robot, veuillez réessayer.',
    'different_passwords' => 'Les deux mots de passe saisis sont différents',
    'forbidden_characters' => 'Caractères interdits: espaces, tabulations, sauts CR/LF.'
];
$error = isset($_GET['error']) ? $error_messages[$_GET['error']] : '';

$captcha = new Captcha;
$_SESSION['captcha'] = $captcha->getSolution(); // enregistrement de la réponse du captcha pour vérification
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Bienvenue</title>
        <link rel="icon" type="image/png" href="assets/favicon48x48.png">
        <link rel="stylesheet" href="css/body.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <main>
            <section>
                <h3>Bienvenue.</h3>
                <p style="text-align: center;">Veuillez choisir les codes que vous utiliserez pour gérer le site.</p>
                <div class="login_form">
                    <p style="color: red; font-style: italic;"><?= $error ?></p>
                    <form method="post" action="index.php?action=create_user" >
                        <p><label for="login" >Identifiant:</label>
                            <input id="login" type="text" name="login" autofocus required></p>
                        <p><label for="password" >Mot de passe:</label>
                            <input id="password" type="password" name="password" required></p>
                        <p><label for="password_confirmation" >Confirmer le mot de passe:</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required></p>
                        <input type="hidden" name="create_user_hidden">

                        <p>Montrez que vous n'êtes pas un robot.<br>
                            <label for="captcha" >Combien font <?= $captcha->getA() ?> fois <?= $captcha->getB() ?>?</label>
                            <input required type="text" id="captcha" name="captcha" autocomplete="off" size="1">
                        </p>

                        <input type="submit" value="Valider">
                    </form>
                </div>
            </section>
        </main>
    </body>
</html>