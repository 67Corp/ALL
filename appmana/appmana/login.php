<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();

// Déjà connecté → accueil
if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token invalide. Rafraîchissez la page.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Identifiant ou mot de passe incorrect.';
        } elseif ($user['status'] === 'pending') {
            redirect(APP_URL . '/pending.php');
        } elseif ($user['status'] === 'rejected') {
            $error = 'Votre compte a été refusé. Contactez un administrateur.';
        } else {
            login_user($user);
            $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/index.php');
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - AppMana</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">AppMana</div>
        <p class="auth-subtitle">Plateforme d'apprentissage IA</p>

        <?php if ($error): ?>
        <div class="auth-error"><?= s($error) ?></div>
        <?php endif; ?>

        <?php $msg = get_flash('success'); if ($msg): ?>
        <div class="auth-success"><?= s($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label for="identifier">Identifiant ou email</label>
                <input type="text" id="identifier" name="identifier"
                    value="<?= s($_POST['identifier'] ?? '') ?>"
                    placeholder="username ou email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary btn-full">Se connecter</button>
        </form>

        <div class="auth-links">
            <a href="<?= APP_URL ?>/register.php">Demander un accès</a>
        </div>
    </div>
</div>

</body>
</html>
