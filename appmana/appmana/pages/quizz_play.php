<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$quizId      = (int)($_GET['quiz_id'] ?? 0);
$challengeId = (int)($_GET['challenge_id'] ?? 0) ?: null;

$stmt = db()->prepare('SELECT q.*, c.title AS course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ?');
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz) { header('Location: ' . APP_URL . '/pages/quizz.php'); exit; }

$title     = 'Quiz : ' . $quiz['title'];
$extra_css = ['dashboard.css'];

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="quiz-play-wrapper">
    <div class="quiz-play-header">
        <h2><?= s($quiz['title']) ?></h2>
        <div class="quiz-meta">
            <span id="questionCounter">Question 1 / <?= (int)$quiz['num_questions'] ?></span>
            <span class="timer-badge" id="timerDisplay">60s</span>
        </div>
    </div>

    <div class="quiz-progress-bar">
        <div id="progressBar" style="width:0%"></div>
    </div>

    <div id="quizBody" class="quiz-body">
        <div class="loading-spinner">Chargement du quiz...</div>
    </div>

    <div class="quiz-nav">
        <button class="btn-secondary" id="btnPrev" disabled>&#8592; Précédent</button>
        <button class="btn-primary" id="btnNext">Suivant &#8594;</button>
        <button class="btn-primary hidden" id="btnSubmit">Terminer &#127937;</button>
    </div>
</div>

<!-- Résultats -->
<div id="quizResults" class="quiz-results hidden">
    <div class="results-card">
        <div class="score-circle" id="scoreCircle">
            <span id="scorePercent">0%</span>
        </div>
        <h2 id="resultsTitle">Résultats</h2>
        <p id="resultsSubtitle"></p>
        <div id="answersReview" class="answers-review"></div>
        <div class="results-actions">
            <a href="<?= APP_URL ?>/pages/quizz.php" class="btn-secondary">Retour aux quizz</a>
            <a href="<?= APP_URL ?>/pages/dashboard.php" class="btn-primary">Voir mes stats</a>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN     = '<?= csrf_token() ?>';
const APP_URL        = '<?= APP_URL ?>';
const QUIZ_ID        = <?= $quizId ?>;
const CHALLENGE_ID   = <?= $challengeId ?? 'null' ?>;
const TOTAL_QUESTIONS = <?= (int)$quiz['num_questions'] ?>;
</script>
<script src="<?= APP_URL ?>/assets/js/quiz.js"></script>
</body>
</html>
