<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

// GET liste
if ($method === 'GET' && $action === 'list') {
    $themeId = (int)($_GET['theme_id'] ?? 0);
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 12;

    $where = 'WHERE c.user_id = ?';
    $params = [$userId];
    if ($themeId) { $where .= ' AND c.theme_id = ?'; $params[] = $themeId; }

    $total = db()->prepare("SELECT COUNT(*) FROM courses c $where");
    $total->execute($params);
    $count = (int)$total->fetchColumn();

    $pg = paginate($count, $perPage, $page);
    $params[] = $pg['offset'];
    $params[] = $perPage;

    $stmt = db()->prepare("
        SELECT c.id, c.title, c.description, c.is_public, c.created_at,
               t.name AS theme_name, t.color_hex,
               COUNT(s.id) AS summary_count
        FROM courses c
        LEFT JOIN themes t ON c.theme_id = t.id
        LEFT JOIN summaries s ON s.course_id = c.id
        $where
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT ?, ?
    ");
    $stmt->execute($params);

    json_response(true, ['courses' => $stmt->fetchAll(), 'pagination' => $pg]);
}

// GET détail
if ($method === 'GET' && $action === 'get') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare('SELECT c.*, t.name AS theme_name, t.color_hex FROM courses c LEFT JOIN themes t ON c.theme_id = t.id WHERE c.id = ? AND c.user_id = ?');
    $stmt->execute([$id, $userId]);
    $course = $stmt->fetch();
    if (!$course) json_response(false, null, 'Cours introuvable.', 404);

    // Résumés du cours
    $stmt2 = db()->prepare('SELECT * FROM summaries WHERE course_id = ? ORDER BY created_at DESC');
    $stmt2->execute([$id]);
    $course['summaries'] = $stmt2->fetchAll();

    json_response(true, $course);
}

// POST créer
if ($method === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!verify_csrf($input['csrf_token'] ?? '')) json_response(false, null, 'CSRF invalide.', 403);

    $title    = trim($input['title'] ?? '');
    $themeId  = (int)($input['theme_id'] ?? 0);
    $desc     = trim($input['description'] ?? '');
    $isPublic = (int)($input['is_public'] ?? 0);

    if (!$title || !$themeId) json_response(false, null, 'Titre et thème requis.', 400);

    $stmt = db()->prepare('INSERT INTO courses (user_id, theme_id, title, description, is_public) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $themeId, $title, $desc, $isPublic]);

    json_response(true, ['id' => db()->lastInsertId()]);
}

// POST sauvegarder un résumé
if ($method === 'POST' && $action === 'save_summary') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!verify_csrf($input['csrf_token'] ?? '')) json_response(false, null, 'CSRF invalide.', 403);

    $courseId   = (int)($input['course_id'] ?? 0);
    $title      = trim($input['title'] ?? '');
    $rawContent = trim($input['raw_content'] ?? '');
    $savedParts = $input['saved_parts'] ?? null;
    $sourceUrl  = trim($input['source_url'] ?? '');

    if (!$courseId || !$rawContent) json_response(false, null, 'Données manquantes.', 400);

    // Vérifier propriété du cours
    $stmt = db()->prepare('SELECT id FROM courses WHERE id = ? AND user_id = ?');
    $stmt->execute([$courseId, $userId]);
    if (!$stmt->fetch()) json_response(false, null, 'Cours introuvable.', 404);

    $stmt2 = db()->prepare('INSERT INTO summaries (course_id, user_id, title, raw_content, saved_parts, source_url) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt2->execute([
        $courseId, $userId, $title ?: null, $rawContent,
        $savedParts ? json_encode($savedParts) : null,
        $sourceUrl ?: null,
    ]);

    json_response(true, ['id' => db()->lastInsertId()]);
}

// DELETE supprimer cours
if ($method === 'DELETE' && $action === 'delete') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare('DELETE FROM courses WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    json_response(true, null);
}

json_response(false, null, 'Action inconnue.', 400);
