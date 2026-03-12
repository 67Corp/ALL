<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
header('Content-Type: application/json; charset=utf-8');

$input  = json_decode(file_get_contents('php://input'), true);
if (!verify_csrf($input['csrf_token'] ?? '')) json_response(false, null, 'CSRF invalide.', 403);

$userId = (int)($input['user_id'] ?? 0);
$stmt   = db()->prepare('UPDATE users SET status = "active" WHERE id = ? AND status = "pending"');
$stmt->execute([$userId]);

if ($stmt->rowCount() > 0) {
    add_notification($userId, 'account_approved', 'Votre compte AppMana a été approuvé ! Vous pouvez maintenant vous connecter.');
    json_response(true, null);
}

json_response(false, null, 'Utilisateur introuvable.', 404);
