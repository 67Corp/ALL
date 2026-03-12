<?php
$user      = current_user();
$notifs    = notif_count();
$active    = $active ?? '';
$navLinks  = [
    'cours'     => ['url' => APP_URL . '/pages/cours.php',     'label' => 'Cours'],
    'questions' => ['url' => APP_URL . '/pages/questions.php', 'label' => 'Questions'],
    'quizz'     => ['url' => APP_URL . '/pages/quizz.php',     'label' => 'Quizz'],
];
?>
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= APP_URL ?>/index.php">AppMana</a>
    </div>

    <ul class="navbar-menu">
        <?php foreach ($navLinks as $key => $link): ?>
        <li>
            <a href="<?= $link['url'] ?>" <?= $active === $key ? 'class="active"' : '' ?>>
                <?= $link['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="navbar-search">
        <input type="text" id="navSearch" placeholder="Rechercher..." autocomplete="off">
        <button type="button" id="navSearchBtn">&#128269;</button>
        <div id="searchDropdown" class="search-dropdown hidden"></div>
    </div>

    <div class="navbar-right">
        <a href="<?= APP_URL ?>/pages/notifs.php" class="notif-bell" title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <?php if ($notifs > 0): ?>
            <span class="notif-badge"><?= $notifs ?></span>
            <?php endif; ?>
        </a>

        <div class="navbar-account">
            <img src="<?= avatar_url($user['avatar_path'] ?? null) ?>" alt="Avatar" class="nav-avatar">
            <span><?= s($user['display_name'] ?? '') ?></span>
            <div class="account-dropdown">
                <a href="<?= APP_URL ?>/pages/profil.php">Mon profil</a>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="<?= APP_URL ?>/admin/index.php">Administration</a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/logout.php" class="logout-link">Déconnexion</a>
            </div>
        </div>
    </div>
</nav>
