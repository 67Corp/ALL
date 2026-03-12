<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$title  = 'Administration - AppMana';
$active = '';

// Statistiques globales
$stats = db()->query('
    SELECT
        (SELECT COUNT(*) FROM users WHERE status = "active")   AS active_users,
        (SELECT COUNT(*) FROM users WHERE status = "pending")  AS pending_users,
        (SELECT COUNT(*) FROM courses)                          AS total_courses,
        (SELECT COUNT(*) FROM quizzes)                          AS total_quizzes,
        (SELECT COUNT(*) FROM quiz_sessions WHERE finished_at IS NOT NULL) AS total_attempts
')->fetch();

// Utilisateurs en attente
$pending = db()->query('SELECT * FROM users WHERE status = "pending" ORDER BY created_at DESC')->fetchAll();

// Tous les utilisateurs
$users = db()->query('SELECT u.*, (SELECT COUNT(*) FROM courses WHERE user_id = u.id) AS course_count FROM users ORDER BY created_at DESC LIMIT 50')->fetchAll();

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content">
    <div class="page-header">
        <h1>&#9881; Administration</h1>
    </div>

    <!-- STATS -->
    <div class="stats-cards">
        <div class="stat-card"><div class="stat-icon">&#128100;</div><div class="stat-value"><?= $stats['active_users'] ?></div><div class="stat-label">Utilisateurs actifs</div></div>
        <div class="stat-card <?= $stats['pending_users'] > 0 ? 'stat-alert' : '' ?>"><div class="stat-icon">&#9203;</div><div class="stat-value"><?= $stats['pending_users'] ?></div><div class="stat-label">En attente</div></div>
        <div class="stat-card"><div class="stat-icon">&#128218;</div><div class="stat-value"><?= $stats['total_courses'] ?></div><div class="stat-label">Cours</div></div>
        <div class="stat-card"><div class="stat-icon">&#127358;</div><div class="stat-value"><?= $stats['total_quizzes'] ?></div><div class="stat-label">Quiz générés</div></div>
    </div>

    <!-- COMPTES EN ATTENTE -->
    <?php if ($pending): ?>
    <section class="admin-section">
        <h2>&#128276; Comptes en attente (<?= count($pending) ?>)</h2>
        <div class="admin-table-wrapper">
            <table class="data-table">
                <thead><tr><th>Utilisateur</th><th>Email</th><th>Demandé le</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($pending as $u): ?>
                    <tr id="row-<?= $u['id'] ?>">
                        <td><strong><?= s($u['display_name']) ?></strong> <small>@<?= s($u['username']) ?></small></td>
                        <td><?= s($u['email']) ?></td>
                        <td><?= format_date($u['created_at']) ?></td>
                        <td>
                            <button class="btn-primary btn-sm" onclick="approveUser(<?= $u['id'] ?>)">Approuver</button>
                            <button class="btn-danger btn-sm" onclick="rejectUser(<?= $u['id'] ?>)">Refuser</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- TOUS LES UTILISATEURS -->
    <section class="admin-section">
        <h2>&#128101; Tous les utilisateurs</h2>
        <div class="admin-table-wrapper">
            <table class="data-table">
                <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Cours</th><th>Inscrit</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= s($u['display_name']) ?> <small>@<?= s($u['username']) ?></small></td>
                        <td><?= s($u['email']) ?></td>
                        <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Étudiant' ?></span></td>
                        <td><span class="badge badge-status-<?= $u['status'] ?>"><?= s($u['status']) ?></span></td>
                        <td><?= $u['course_count'] ?></td>
                        <td><?= format_date($u['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</div>

<script>
const CSRF_TOKEN = '<?= csrf_token() ?>';
const APP_URL    = '<?= APP_URL ?>';

async function approveUser(id) {
    if (!confirm('Approuver cet utilisateur ?')) return;
    const r = await fetch(APP_URL + '/api/admin/approve_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: id, csrf_token: CSRF_TOKEN }),
    });
    const d = await r.json();
    if (d.success) {
        document.getElementById('row-' + id)?.remove();
        alert('Compte approuvé !');
    }
}

async function rejectUser(id) {
    if (!confirm('Refuser ce compte ?')) return;
    const r = await fetch(APP_URL + '/api/admin/reject_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: id, csrf_token: CSRF_TOKEN }),
    });
    const d = await r.json();
    if (d.success) {
        document.getElementById('row-' + id)?.remove();
        alert('Compte refusé.');
    }
}
</script>
</body>
</html>
