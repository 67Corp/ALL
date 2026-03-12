<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();
if (is_logged_in()) redirect(APP_URL . '/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalide.';
    } else {
        $username     = trim($_POST['username'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $display_name = trim($_POST['display_name'] ?? '');
        $password     = $_POST['password'] ?? '';
        $confirm      = $_POST['confirm'] ?? '';

        if (strlen($username) < 3 || strlen($username) > 60) $errors[] = 'Nom d\'utilisateur : 3-60 caractères.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors[] = 'Email invalide.';
        if (strlen($password) < 8)                            $errors[] = 'Mot de passe : 8 caractères minimum.';
        if ($password !== $confirm)                           $errors[] = 'Les mots de passe ne correspondent pas.';

        if (!$errors) {
            // Vérifier unicité
            $stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Cet identifiant ou email est déjà utilisé.';
            }
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = db()->prepare('INSERT INTO users (username, email, password_hash, display_name, status) VALUES (?, ?, ?, ?, "pending")');
            $stmt->execute([$username, $email, $hash, $display_name ?: $username]);

            // Notifier les admins
            $admins = db()->query('SELECT id FROM users WHERE role = "admin"')->fetchAll();
            foreach ($admins as $admin) {
                add_notification($admin['id'], 'new_registration',
                    "Nouvelle demande de compte : $username", ['username' => $username]);
            }

            flash('success', 'Votre demande a été envoyée. Un administrateur validera votre compte.');
            redirect(APP_URL . '/pending.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'accès - AppMana</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">AppMana</div>
        <p class="auth-subtitle">Créer un compte</p>

        <?php if ($errors): ?>
        <div class="auth-error">
            <?php foreach ($errors as $e): ?><div><?= s($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" value="<?= s($_POST['username'] ?? '') ?>"
                    placeholder="ex: jean_dupont" required>
            </div>

            <div class="form-group">
                <label>Prénom et Nom</label>
                <input type="text" name="display_name" value="<?= s($_POST['display_name'] ?? '') ?>"
                    placeholder="ex: Jean Dupont">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= s($_POST['email'] ?? '') ?>"
                    placeholder="vous@example.com" required>
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="8 caractères minimum" required>
            </div>

            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary btn-full">Envoyer ma demande</button>
        </form>

        <div class="auth-links">
            <a href="<?= APP_URL ?>/login.php">Déjà un compte ? Se connecter</a>
        </div>
    </div>
</div>

</body>
</html>
