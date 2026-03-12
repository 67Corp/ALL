<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gemini.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, null, 'Méthode non autorisée.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$url   = trim($input['url'] ?? '');
$userId = $_SESSION['user_id'];

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    json_response(false, null, 'URL invalide.', 400);
}

// Vérifier le cache
$urlHash = hash('sha256', strtolower($url));
$stmt    = db()->prepare('SELECT summary_text FROM url_cache WHERE url_hash = ? AND expires_at > NOW()');
$stmt->execute([$urlHash]);
$cached  = $stmt->fetchColumn();

if ($cached) {
    json_response(true, ['summary' => $cached, 'cached' => true]);
}

// Vérifier la limite Gemini
if (!GeminiClient::checkLimit($userId)) {
    json_response(false, null, 'Limite Gemini atteinte (20 appels/heure). Réessayez plus tard.', 429);
}

try {
    $gemini  = new GeminiClient();
    $summary = $gemini->summarizeUrl($url);

    // Mettre en cache
    $stmt2 = db()->prepare('
        INSERT INTO url_cache (url_hash, url, summary_text, expires_at)
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))
        ON DUPLICATE KEY UPDATE summary_text = VALUES(summary_text), expires_at = VALUES(expires_at)
    ');
    $stmt2->execute([$urlHash, $url, $summary, URL_CACHE_DAYS]);

    // Log
    $stmt3 = db()->prepare('INSERT INTO search_history (user_id, query, result_src) VALUES (?, ?, "gemini")');
    $stmt3->execute([$userId, 'URL: ' . $url]);

    json_response(true, ['summary' => $summary, 'cached' => false]);
} catch (RuntimeException $e) {
    json_response(false, null, $e->getMessage(), 500);
}
