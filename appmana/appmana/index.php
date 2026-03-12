<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user    = current_user();
$active  = '';
$title   = 'Accueil - AppMana';

// Suggestions basées sur l'historique de l'utilisateur
$stmt = db()->prepare('
    SELECT query FROM search_history
    WHERE user_id = ?
    GROUP BY query
    ORDER BY COUNT(*) DESC
    LIMIT 4
');
$stmt->execute([$user['id']]);
$recent = array_column($stmt->fetchAll(), 'query');

// Suggestions par défaut si pas d'historique
$suggestions = $recent ?: [
    'Qu\'est-ce que le contrat de travail ?',
    'Les bases du management de projet',
    'Initiation au droit des sociétés',
    'Les fondamentaux de la gestion comptable',
];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="hero">
    <h1 class="hero-title">Bonjour, <?= s(explode(' ', $user['display_name'])[0]) ?> &#128075;</h1>
    <p class="hero-subtitle">Posez une question, cherchez un cours ou lancez un quizz.</p>

    <div class="hero-search">
        <textarea id="heroInput" placeholder="Posez votre question ici..." rows="1"></textarea>
        <button id="heroBtn" type="button" title="Envoyer">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
        </button>
    </div>

    <div class="hero-suggestions">
        <?php foreach ($suggestions as $s_text): ?>
        <button class="chip" onclick="fillSearch(this)"><?= s($s_text) ?></button>
        <?php endforeach; ?>
    </div>

    <!-- Résultats de recherche inline -->
    <div id="heroResults" class="hero-results hidden"></div>
</div>

<script>
const CSRF_TOKEN = '<?= csrf_token() ?>';
const APP_URL    = '<?= APP_URL ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script src="<?= APP_URL ?>/assets/js/search.js"></script>
</body>
</html>
