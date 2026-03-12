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

// POST créer un défi
if ($method === 'POST' && $action === 'create') {
    $input       = json_decode(file_get_contents('php://input'), true);
    if (!verify_csrf($input['csrf_token'] ?? '')) json_response(false, null, 'CSRF invalide.', 403);

    $quizId      = (int)($input['quiz_id'] ?? 0);
    $challengedId = (int)($input['challenged_id'] ?? 0);

    if ($challengedId === $userId) json_response(false, null, 'Vous ne pouvez pas vous défier vous-même.', 400);

    // Vérifier que le quiz existe
    $stmt = db()->prepare('SELECT id FROM quizzes WHERE id = ?');
    $stmt->execute([$quizId]);
    if (!$stmt->fetch()) json_response(false, null, 'Quiz introuvable.', 404);

    // Vérifier que l'utilisateur cible existe et est actif
    $stmt2 = db()->prepare('SELECT id, display_name FROM users WHERE id = ? AND status = "active"');
    $stmt2->execute([$challengedId]);
    $target = $stmt2->fetch();
    if (!$target) json_response(false, null, 'Utilisateur introuvable.', 404);

    $stmt3 = db()->prepare('INSERT INTO challenges (quiz_id, challenger_id, challenged_id) VALUES (?, ?, ?)');
    $stmt3->execute([$quizId, $userId, $challengedId]);
    $challengeId = db()->lastInsertId();

    // Notification
    $challenger = db()->prepare('SELECT display_name FROM users WHERE id = ?');
    $challenger->execute([$userId]);
    $challengerName = $challenger->fetchColumn();

    add_notification($challengedId, 'challenge_invite',
        "$challengerName vous défie sur un quiz !",
        ['challenge_id' => $challengeId, 'quiz_id' => $quizId]
    );

    json_response(true, ['challenge_id' => $challengeId]);
}

// POST répondre à un défi
if ($method === 'POST' && $action === 'respond') {
    $input       = json_decode(file_get_contents('php://input'), true);
    $challengeId = (int)($input['challenge_id'] ?? 0);
    $accept      = (bool)($input['accept'] ?? false);

    $stmt = db()->prepare('SELECT * FROM challenges WHERE id = ? AND challenged_id = ? AND status = "pending"');
    $stmt->execute([$challengeId, $userId]);
    $challenge = $stmt->fetch();
    if (!$challenge) json_response(false, null, 'Défi introuvable.', 404);

    $newStatus = $accept ? 'accepted' : 'declined';
    db()->prepare('UPDATE challenges SET status = ? WHERE id = ?')->execute([$newStatus, $challengeId]);

    // Notifier le challenger
    $user = db()->prepare('SELECT display_name FROM users WHERE id = ?');
    $user->execute([$userId]);
    $name = $user->fetchColumn();

    $msg = $accept ? "$name a accepté votre défi !" : "$name a refusé votre défi.";
    add_notification($challenge['challenger_id'], 'challenge_response', $msg, ['challenge_id' => $challengeId]);

    json_response(true, ['status' => $newStatus, 'quiz_id' => $challenge['quiz_id']]);
}

// GET liste des défis
if ($method === 'GET' && $action === 'list') {
    $stmt = db()->prepare('
        SELECT ch.id, ch.status, ch.created_at,
               q.title AS quiz_title,
               u1.display_name AS challenger_name, u1.avatar_path AS challenger_avatar,
               u2.display_name AS challenged_name, u2.avatar_path AS challenged_avatar,
               u3.display_name AS winner_name,
               ch.challenger_id, ch.challenged_id
        FROM challenges ch
        JOIN quizzes q  ON ch.quiz_id = q.id
        JOIN users u1   ON ch.challenger_id  = u1.id
        JOIN users u2   ON ch.challenged_id  = u2.id
        LEFT JOIN users u3 ON ch.winner_id   = u3.id
        WHERE ch.challenger_id = ? OR ch.challenged_id = ?
        ORDER BY ch.created_at DESC
        LIMIT 30
    ');
    $stmt->execute([$userId, $userId]);
    json_response(true, $stmt->fetchAll());
}

// GET utilisateurs disponibles pour un défi
if ($method === 'GET' && $action === 'users') {
    $stmt = db()->prepare('SELECT id, display_name, avatar_path FROM users WHERE status = "active" AND id != ? ORDER BY display_name LIMIT 50');
    $stmt->execute([$userId]);
    json_response(true, $stmt->fetchAll());
}

json_response(false, null, 'Action inconnue.', 400);
