<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$active     = 'cours';
$title      = 'Cours - AppMana';
$extra_css  = ['dashboard.css'];

$user    = current_user();
$themes  = db()->query('SELECT * FROM themes ORDER BY name')->fetchAll();

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content">
    <div class="page-header">
        <h1>Mes Cours</h1>
        <button class="btn-primary" id="btnNewCourse">+ Nouveau cours</button>
    </div>

    <!-- FILTRES -->
    <div class="filters">
        <button class="filter-btn active" data-theme="">Tous</button>
        <?php foreach ($themes as $t): ?>
        <button class="filter-btn" data-theme="<?= $t['id'] ?>"
                style="--theme-color:<?= s($t['color_hex']) ?>">
            <?= s($t['name']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- GRILLE DE COURS -->
    <div id="coursesGrid" class="courses-grid">
        <div class="loading-spinner">Chargement...</div>
    </div>

    <!-- PAGINATION -->
    <div id="coursesPagination" class="pagination"></div>
</main>
</div>

<!-- MODAL : Nouveau cours -->
<div class="modal-overlay hidden" id="modalNewCourse">
    <div class="modal">
        <div class="modal-header">
            <h2>Nouveau cours</h2>
            <button class="modal-close" data-modal="modalNewCourse">&times;</button>
        </div>

        <!-- ONGLETS -->
        <div class="modal-tabs">
            <button class="tab-btn active" data-tab="tabManual">Créer manuellement</button>
            <button class="tab-btn" data-tab="tabUrl">Depuis une URL</button>
        </div>

        <!-- Onglet Manuel -->
        <div id="tabManual" class="tab-content active">
            <div class="form-group">
                <label>Titre du cours *</label>
                <input type="text" id="courseTitle" placeholder="ex: Introduction au droit des contrats">
            </div>
            <div class="form-group">
                <label>Thème *</label>
                <select id="courseTheme">
                    <?php foreach ($themes as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= s($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description / Contenu</label>
                <textarea id="courseDesc" rows="6" placeholder="Décrivez ou collez le contenu du cours..."></textarea>
            </div>
            <div class="form-group checkbox-group">
                <label><input type="checkbox" id="coursePublic"> Rendre public (visible par tous)</label>
            </div>
            <button class="btn-primary" id="btnSaveCourse">Créer le cours</button>
        </div>

        <!-- Onglet URL -->
        <div id="tabUrl" class="tab-content">
            <div class="form-group">
                <label>URL à analyser</label>
                <input type="url" id="summaryUrl" placeholder="https://...">
            </div>
            <button class="btn-primary" id="btnSummarize">Analyser avec Gemini &#9889;</button>

            <div id="summaryResult" class="summary-result hidden">
                <div class="summary-actions">
                    <h4>Résumé généré</h4>
                    <button class="btn-sm" id="btnSelectAll">Tout sélectionner</button>
                </div>
                <div id="summaryText" class="summary-text" contenteditable="true"></div>

                <div class="form-group" style="margin-top:16px">
                    <label>Associer à un cours (ou en créer un)</label>
                    <select id="summaryCourse">
                        <option value="new">+ Créer un nouveau cours</option>
                    </select>
                </div>
                <div id="newCourseFields">
                    <div class="form-group">
                        <label>Titre</label>
                        <input type="text" id="summaryTitle">
                    </div>
                    <div class="form-group">
                        <label>Thème</label>
                        <select id="summaryTheme">
                            <?php foreach ($themes as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= s($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn-primary" id="btnSaveSummary">&#128190; Sauvegarder dans ma base</button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?= csrf_token() ?>';
const APP_URL    = '<?= APP_URL ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script src="<?= APP_URL ?>/assets/js/search.js"></script>
<script src="<?= APP_URL ?>/assets/js/summarizer.js"></script>
</body>
</html>
