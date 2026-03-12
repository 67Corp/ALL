<?php
$user = current_user();
?>
<aside class="sidebar">
    <div class="sidebar-avatar">
        <img src="<?= avatar_url($user['avatar_path'] ?? null) ?>" alt="Avatar">
    </div>
    <div class="sidebar-name"><?= s($user['display_name'] ?? '') ?></div>
    <div class="sidebar-role"><?= ($user['role'] ?? '') === 'admin' ? 'Administrateur' : 'Étudiant' ?></div>

    <hr>

    <ul class="sidebar-menu">
        <li><a href="<?= APP_URL ?>/pages/profil.php">Mon profil</a></li>
        <li><a href="<?= APP_URL ?>/pages/cours.php">Mes cours</a></li>
        <li><a href="<?= APP_URL ?>/pages/quizz.php">Mes résultats</a></li>
        <li><a href="<?= APP_URL ?>/pages/dashboard.php">Performances</a></li>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li><a href="<?= APP_URL ?>/admin/index.php">&#9881; Administration</a></li>
        <?php endif; ?>
        <li><a href="<?= APP_URL ?>/logout.php" class="logout">Déconnexion</a></li>
    </ul>
</aside>
