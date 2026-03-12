<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$active = 'questions';
$title  = 'Questions - AppMana';

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content">
    <div class="page-header">
        <h1>Recherche &amp; Questions</h1>
    </div>

    <!-- GRANDE BARRE DE RECHERCHE -->
    <div class="search-hero">
        <div class="search-hero-input">
            <textarea id="questionInput" rows="2" placeholder="Posez une question ou cherchez un concept... (ex: Qu'est-ce que la responsabilité contractuelle ?)"></textarea>
            <div class="search-hero-actions">
                <label class="toggle-ai">
                    <input type="checkbox" id="useAI" checked>
                    <span>Recherche IA</span>
                </label>
                <button id="btnSearch" class="btn-primary">Rechercher</button>
            </div>
        </div>

        <!-- Tags prédictifs -->
        <div class="search-tags">
            <span class="tag-label">Thèmes :</span>
            <button class="tag-chip" onclick="appendSearch('droit des contrats')">Droit des contrats</button>
            <button class="tag-chip" onclick="appendSearch('gestion financière')">Gestion financière</button>
            <button class="tag-chip" onclick="appendSearch('management d\'équipe')">Management d'équipe</button>
            <button class="tag-chip" onclick="appendSearch('sécurité informatique')">Sécurité info</button>
            <button class="tag-chip" onclick="appendSearch('droit du travail')">Droit du travail</button>
            <button class="tag-chip" onclick="appendSearch('comptabilité générale')">Comptabilité</button>
            <button class="tag-chip" onclick="appendSearch('base de données')">Base de données</button>
        </div>
    </div>

    <!-- RÉSULTATS -->
    <div id="resultsContainer" class="results-container hidden">

        <!-- Résultats DB -->
        <div class="results-section" id="dbResultsSection">
            <h3 class="results-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                Dans vos cours
                <span id="dbCount" class="count-badge">0</span>
            </h3>
            <div id="dbResults"></div>
        </div>

        <!-- Résultats IA -->
        <div class="results-section" id="aiResultsSection">
            <h3 class="results-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                Réponse Gemini IA
                <span class="ai-badge">Gemini Pro</span>
            </h3>
            <div id="aiResult" class="ai-answer"></div>
        </div>

    </div>

    <div id="searchLoading" class="search-loading hidden">
        <div class="spinner"></div>
        <span>Recherche en cours...</span>
    </div>
</main>
</div>

<script>
const CSRF_TOKEN = '<?= csrf_token() ?>';
const APP_URL    = '<?= APP_URL ?>';

function appendSearch(text) {
    const input = document.getElementById('questionInput');
    input.value = text;
    input.focus();
}
</script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script src="<?= APP_URL ?>/assets/js/search.js"></script>
</body>
</html>
