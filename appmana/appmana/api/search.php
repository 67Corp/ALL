<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gemini.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$q      = trim($_GET['q'] ?? '');
$useAI  = ($_GET['ai'] ?? '0') === '1';
$userId = $_SESSION['user_id'];

if (strlen($q) < 2) {
    json_response(true, ['db' => [], 'ai' => null]);
}

// === Phase 1 : recherche base de données ===
$q_star = '+' . implode(' +', array_filter(explode(' ', preg_replace('/[^\w\s]/u', '', $q))));

$stmt = db()->prepare('
    SELECT "course" AS type, c.id, c.title, SUBSTRING(c.description, 1, 250) AS excerpt,
           t.name AS theme, t.color_hex,
           MATCH(c.title, c.description) AGAINST(? IN BOOLEAN MODE) AS relevance
    FROM courses c
    LEFT JOIN themes t ON c.theme_id = t.id
    WHERE MATCH(c.title, c.description) AGAINST(? IN BOOLEAN MODE)
      AND (c.is_public = 1 OR c.user_id = ?)
    UNION ALL
    SELECT "summary" AS type, s.id, COALESCE(s.title, "Résumé sans titre") AS title,
           SUBSTRING(s.raw_content, 1, 250) AS excerpt,
           t.name AS theme, t.color_hex,
           MATCH(s.title, s.raw_content) AGAINST(? IN BOOLEAN MODE) AS relevance
    FROM summaries s
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN themes t ON c.theme_id = t.id
    WHERE MATCH(s.title, s.raw_content) AGAINST(? IN BOOLEAN MODE)
      AND s.user_id = ?
    ORDER BY relevance DESC
    LIMIT 10
');
$stmt->execute([$q_star, $q_star, $userId, $q_star, $q_star, $userId]);
$dbResults = $stmt->fetchAll();

// Enregistrer dans l'historique
$src = 'db';
$aiResult = null;

// === Phase 2 : Gemini si demandé ou peu de résultats ===
if ($useAI || count($dbResults) < 3) {
    if (GeminiClient::checkLimit($userId)) {
        try {
            $gemini   = new GeminiClient();
            $aiResult = $gemini->answerSearch($q);
            $src = count($dbResults) > 0 ? 'both' : 'gemini';
        } catch (RuntimeException $e) {
            $aiResult = null;
        }
    }
}

// Log
$stmt2 = db()->prepare('INSERT INTO search_history (user_id, query, result_src) VALUES (?, ?, ?)');
$stmt2->execute([$userId, $q, $src]);

json_response(true, ['db' => $dbResults, 'ai' => $aiResult]);
