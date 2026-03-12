<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'];

// Score moyen sur les 30 derniers jours (par jour)
$stmt1 = db()->prepare('
    SELECT DATE(finished_at) AS day, ROUND(AVG(score)) AS avg_score, COUNT(*) AS quiz_count
    FROM quiz_sessions
    WHERE user_id = ? AND finished_at IS NOT NULL
    GROUP BY DATE(finished_at)
    ORDER BY day ASC
    LIMIT 30
');
$stmt1->execute([$userId]);
$progression = $stmt1->fetchAll();

// Score moyen par thème
$stmt2 = db()->prepare('
    SELECT t.name AS theme, t.color_hex, ROUND(AVG(qs.score)) AS avg_score, COUNT(qs.id) AS quiz_count
    FROM quiz_sessions qs
    JOIN quizzes q  ON qs.quiz_id = q.id
    JOIN courses c  ON q.course_id = c.id
    JOIN themes t   ON c.theme_id = t.id
    WHERE qs.user_id = ? AND qs.finished_at IS NOT NULL
    GROUP BY t.id
    ORDER BY avg_score DESC
');
$stmt2->execute([$userId]);
$byTheme = $stmt2->fetchAll();

// Classement global (top 10 par score moyen)
$stmt3 = db()->prepare('
    SELECT u.display_name, u.avatar_path, ROUND(AVG(qs.score)) AS avg_score, COUNT(qs.id) AS quiz_count
    FROM quiz_sessions qs
    JOIN users u ON qs.user_id = u.id
    WHERE qs.finished_at IS NOT NULL
    GROUP BY u.id
    ORDER BY avg_score DESC
    LIMIT 10
');
$stmt3->execute();
$leaderboard = $stmt3->fetchAll();

// Stats perso globales
$stmt4 = db()->prepare('
    SELECT COUNT(*) AS total_quizzes,
           ROUND(AVG(score)) AS avg_score,
           MAX(score) AS best_score,
           SUM(CASE WHEN score >= 80 THEN 1 ELSE 0 END) AS excellent_count
    FROM quiz_sessions
    WHERE user_id = ? AND finished_at IS NOT NULL
');
$stmt4->execute([$userId]);
$personal = $stmt4->fetch();

// Défis
$stmt5 = db()->prepare('
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) AS won,
        SUM(CASE WHEN status = "completed" AND winner_id != ? THEN 1 ELSE 0 END) AS lost
    FROM challenges
    WHERE (challenger_id = ? OR challenged_id = ?) AND status = "completed"
');
$stmt5->execute([$userId, $userId, $userId, $userId]);
$challenges = $stmt5->fetch();

json_response(true, [
    'progression' => $progression,
    'by_theme'    => $byTheme,
    'leaderboard' => $leaderboard,
    'personal'    => $personal,
    'challenges'  => $challenges,
]);
