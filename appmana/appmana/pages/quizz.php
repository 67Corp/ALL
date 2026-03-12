<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$active = 'quizz';
$title  = 'Quizz - AppMana';
$extra_css = ['dashboard.css'];

$userId = $_SESSION['user_id'];

// Défis en attente
$pendingChallenges = db()->prepare('
    SELECT ch.id, q.title AS quiz_title, u.display_name AS challenger_name, u.avatar_path, ch.created_at
    FROM challenges ch
    JOIN quizzes q ON ch.quiz_id = q.id
    JOIN users u   ON ch.challenger_id = u.id
    WHERE ch.challenged_id = ? AND ch.status = "pending"
    ORDER BY ch.created_at DESC
');
$pendingChallenges->execute([$userId]);
$pending = $pendingChallenges->fetchAll();

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content">
    <div class="page-header">
        <h1>Quizz &amp; Défis</h1>
        <button class="btn-primary" id="btnChallenge">&#9876; Défier quelqu'un</button>
    </div>

    <!-- DÉFIS EN ATTENTE -->
    <?php if ($pending): ?>
    <div class="alert-section">
        <h3>&#128276; Défis reçus (<?= count($pending) ?>)</h3>
        <div class="challenges-list">
            <?php foreach ($pending as $ch): ?>
            <div class="challenge-card pending">
                <img src="<?= avatar_url($ch['avatar_path']) ?>" alt="" class="challenge-avatar">
                <div class="challenge-info">
                    <strong><?= s($ch['challenger_name']) ?></strong> vous défie sur
                    <em><?= s($ch['quiz_title']) ?></em>
                    <span class="time"><?= time_ago($ch['created_at']) ?></span>
                </div>
                <div class="challenge-actions">
                    <button class="btn-primary btn-sm" onclick="respondChallenge(<?= $ch['id'] ?>, true)">Accepter</button>
                    <button class="btn-danger btn-sm"  onclick="respondChallenge(<?= $ch['id'] ?>, false)">Refuser</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="tabMyQuiz">Mes quizz</button>
        <button class="tab-btn" data-tab="tabHistory">Historique</button>
        <button class="tab-btn" data-tab="tabChallenges">Tous les défis</button>
    </div>

    <!-- Mes quizz -->
    <div id="tabMyQuiz" class="tab-content active">
        <div id="quizGrid" class="quiz-grid">
            <div class="loading-spinner">Chargement...</div>
        </div>
    </div>

    <!-- Historique -->
    <div id="tabHistory" class="tab-content">
        <table class="data-table" id="historyTable">
            <thead>
                <tr><th>Quiz</th><th>Score</th><th>Date</th><th>Type</th></tr>
            </thead>
            <tbody id="historyBody">
                <tr><td colspan="4" class="loading-spinner">Chargement...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Tous les défis -->
    <div id="tabChallenges" class="tab-content">
        <div id="challengesList" class="challenges-list">
            <div class="loading-spinner">Chargement...</div>
        </div>
    </div>

</main>
</div>

<!-- MODAL : Défier -->
<div class="modal-overlay hidden" id="modalChallenge">
    <div class="modal">
        <div class="modal-header">
            <h2>&#9876; Lancer un défi</h2>
            <button class="modal-close" data-modal="modalChallenge">&times;</button>
        </div>
        <div class="form-group">
            <label>Choisir le quiz</label>
            <select id="challengeQuiz"></select>
        </div>
        <div class="form-group">
            <label>Défier</label>
            <select id="challengeUser"></select>
        </div>
        <button class="btn-primary" id="btnSendChallenge">Envoyer le défi &#128293;</button>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?= csrf_token() ?>';
const APP_URL    = '<?= APP_URL ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script src="<?= APP_URL ?>/assets/js/quiz.js"></script>
</body>
</html>
