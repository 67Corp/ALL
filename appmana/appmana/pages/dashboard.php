<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$active    = '';
$title     = 'Performances - AppMana';
$extra_css = ['dashboard.css'];

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content">
    <div class="page-header">
        <h1>&#128200; Mes Performances</h1>
    </div>

    <!-- STATS GLOBALES -->
    <div class="stats-cards">
        <div class="stat-card" id="statTotal">
            <div class="stat-icon">&#127358;</div>
            <div class="stat-value" id="statTotalVal">-</div>
            <div class="stat-label">Quiz complétés</div>
        </div>
        <div class="stat-card" id="statAvg">
            <div class="stat-icon">&#127919;</div>
            <div class="stat-value" id="statAvgVal">-</div>
            <div class="stat-label">Score moyen</div>
        </div>
        <div class="stat-card" id="statBest">
            <div class="stat-icon">&#127942;</div>
            <div class="stat-value" id="statBestVal">-</div>
            <div class="stat-label">Meilleur score</div>
        </div>
        <div class="stat-card" id="statWins">
            <div class="stat-icon">&#9876;</div>
            <div class="stat-value" id="statWinsVal">-</div>
            <div class="stat-label">Défis gagnés</div>
        </div>
    </div>

    <!-- GRAPHIQUES -->
    <div class="charts-grid">

        <div class="chart-card chart-large">
            <h3>Progression (30 derniers jours)</h3>
            <canvas id="chartProgression"></canvas>
        </div>

        <div class="chart-card">
            <h3>Score par thème</h3>
            <canvas id="chartTheme"></canvas>
        </div>

    </div>

    <!-- CLASSEMENT -->
    <div class="chart-card" style="margin-top:24px">
        <h3>&#127942; Classement général</h3>
        <div id="leaderboard" class="leaderboard"></div>
    </div>

</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const CSRF_TOKEN = '<?= csrf_token() ?>';
const APP_URL    = '<?= APP_URL ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
