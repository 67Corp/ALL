<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();
$msg = get_flash('success');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte en attente - AppMana</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">
<div class="auth-container">
    <div class="auth-card" style="text-align:center">
        <div class="auth-logo">AppMana</div>
        <div style="font-size:3rem;margin:20px 0">⏳</div>
        <h2 style="color:#fff;margin-bottom:12px">Compte en attente</h2>
        <?php if ($msg): ?><div class="auth-success"><?= s($msg) ?></div><?php endif; ?>
        <p style="color:#8888aa;line-height:1.6">
            Votre demande a bien été reçue.<br>
            Un administrateur validera votre compte dans les plus brefs délais.<br>
            Vous recevrez un email de confirmation.
        </p>
        <div class="auth-links" style="margin-top:24px">
            <a href="<?= APP_URL ?>/login.php">Retour à la connexion</a>
        </div>
    </div>
</div>
</body>
</html>
