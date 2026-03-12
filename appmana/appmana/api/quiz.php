<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gemini.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

// POST générer un quiz depuis un cours
if ($method === 'POST' && $action === 'generate') {
    $input       = json_decode(file_get_contents('php://input'), true);
    if (!verify_csrf($input['csrf_token'] ?? '')) json_response(false, null, 'CSRF invalide.', 403);

    $courseId    = (int)($input['course_id'] ?? 0);
    $numQ        = min(20, max(5, (int)($input['num_questions'] ?? 10)));

    // Récupérer le cours + ses résumés
    $stmt = db()->prepare('
        SELECT c.title, t.name AS theme_name, s.raw_content, s.saved_parts
        FROM courses c
        LEFT JOIN themes t ON c.theme_id = t.id
        LEFT JOIN summaries s ON s.course_id = c.id
        WHERE c.id = ? AND c.user_id = ?
        ORDER BY s.created_at DESC
        LIMIT 5
    ');
    $stmt->execute([$courseId, $userId]);
    $rows = $stmt->fetchAll();

    if (!$rows || !$rows[0]['title']) json_response(false, null, 'Cours introuvable.', 404);

    $courseTitle = $rows[0]['title'];
    $themeName   = $rows[0]['theme_name'] ?? 'général';
    $content     = '';

    foreach ($rows as $row) {
        $parts = $row['saved_parts'] ? json_decode($row['saved_parts'], true) : null;
        $content .= "\n\n" . ($parts ? implode("\n", $parts) : $row['raw_content']);
    }

    if (!trim($content)) {
        json_response(false, null, 'Aucun contenu dans ce cours. Ajoutez des résumés d\'abord.', 400);
    }

    if (!GeminiClient::checkLimit($userId)) {
        json_response(false, null, 'Limite Gemini atteinte. Réessayez plus tard.', 429);
    }

    try {
        $gemini    = new GeminiClient();
        $questions = $gemini->generateQuiz($content, $themeName, $numQ);
    } catch (RuntimeException $e) {
        json_response(false, null, $e->getMessage(), 500);
    }

    // Sauvegarder le quiz
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt2 = $pdo->prepare('INSERT INTO quizzes (course_id, created_by, title, num_questions) VALUES (?, ?, ?, ?)');
        $stmt2->execute([$courseId, $userId, "Quiz : $courseTitle", count($questions)]);
        $quizId = $pdo->lastInsertId();

        $stmt3 = $pdo->prepare('INSERT INTO questions (quiz_id, question_text, options_json, correct_index, explanation, difficulty, position) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($questions as $i => $q) {
            $stmt3->execute([
                $quizId,
                $q['question'],
                json_encode($q['options']),
                (int)$q['correct_index'],
                $q['explanation'] ?? null,
                $q['difficulty']  ?? 'moyen',
                $i,
            ]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(false, null, 'Erreur lors de la sauvegarde du quiz.', 500);
    }

    json_response(true, ['quiz_id' => $quizId, 'question_count' => count($questions)]);
}

// POST démarrer une session de quiz
if ($method === 'POST' && $action === 'start') {
    $input       = json_decode(file_get_contents('php://input'), true);
    $quizId      = (int)($input['quiz_id'] ?? 0);
    $challengeId = (int)($input['challenge_id'] ?? 0) ?: null;

    // Vérifier que le quiz existe
    $stmt = db()->prepare('SELECT id FROM quizzes WHERE id = ?');
    $stmt->execute([$quizId]);
    if (!$stmt->fetch()) json_response(false, null, 'Quiz introuvable.', 404);

    $stmt2 = db()->prepare('INSERT INTO quiz_sessions (quiz_id, user_id, challenge_id) VALUES (?, ?, ?)');
    $stmt2->execute([$quizId, $userId, $challengeId]);
    $sessionId = db()->lastInsertId();

    // Charger les questions
    $stmt3 = db()->prepare('SELECT id, question_text, options_json, position FROM questions WHERE quiz_id = ? ORDER BY position ASC');
    $stmt3->execute([$quizId]);
    $questions = $stmt3->fetchAll();

    foreach ($questions as &$q) {
        $q['options'] = json_decode($q['options_json'], true);
        unset($q['options_json']);
    }

    json_response(true, ['session_id' => $sessionId, 'questions' => $questions]);
}

// POST soumettre les réponses
if ($method === 'POST' && $action === 'submit') {
    $input     = json_decode(file_get_contents('php://input'), true);
    $sessionId = (int)($input['session_id'] ?? 0);
    $answers   = $input['answers'] ?? [];  // {question_id: chosen_index}

    // Vérifier propriété de la session
    $stmt = db()->prepare('SELECT qs.*, q.id AS quiz_id FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id WHERE qs.id = ? AND qs.user_id = ? AND qs.finished_at IS NULL');
    $stmt->execute([$sessionId, $userId]);
    $session = $stmt->fetch();
    if (!$session) json_response(false, null, 'Session invalide.', 404);

    // Charger les bonnes réponses
    $stmt2 = db()->prepare('SELECT id, correct_index, explanation FROM questions WHERE quiz_id = ?');
    $stmt2->execute([$session['quiz_id']]);
    $correctAnswers = [];
    foreach ($stmt2->fetchAll() as $q) {
        $correctAnswers[$q['id']] = ['correct' => $q['correct_index'], 'explanation' => $q['explanation']];
    }

    $score  = 0;
    $total  = count($correctAnswers);
    $detail = [];

    foreach ($correctAnswers as $qId => $correct) {
        $chosen  = isset($answers[$qId]) ? (int)$answers[$qId] : -1;
        $isRight = $chosen === (int)$correct['correct'];
        if ($isRight) $score++;
        $detail[$qId] = [
            'chosen'      => $chosen,
            'correct'     => $correct['correct'],
            'is_correct'  => $isRight,
            'explanation' => $correct['explanation'],
        ];
    }

    $pct = $total > 0 ? (int)round($score / $total * 100) : 0;

    // Mettre à jour la session
    $stmt3 = db()->prepare('UPDATE quiz_sessions SET finished_at = NOW(), score = ?, answers_json = ? WHERE id = ?');
    $stmt3->execute([$pct, json_encode($answers), $sessionId]);

    // Gérer le challenge si applicable
    if ($session['challenge_id']) {
        require_once __DIR__ . '/../includes/challenge_helper.php';
        handle_challenge_completion($session['challenge_id'], $userId, $pct);
    }

    json_response(true, [
        'score'        => $pct,
        'correct'      => $score,
        'total'        => $total,
        'answers_detail' => $detail,
    ]);
}

// GET liste des quizzes de l'utilisateur
if ($method === 'GET' && $action === 'list') {
    $stmt = db()->prepare('
        SELECT q.id, q.title, q.created_at, q.num_questions,
               c.title AS course_title,
               t.name AS theme_name, t.color_hex,
               (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.quiz_id = q.id AND qs.user_id = ?) AS attempt_count,
               (SELECT MAX(qs.score) FROM quiz_sessions qs WHERE qs.quiz_id = q.id AND qs.user_id = ?) AS best_score
        FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        LEFT JOIN themes t ON c.theme_id = t.id
        WHERE q.created_by = ? OR c.is_public = 1
        ORDER BY q.created_at DESC
        LIMIT 50
    ');
    $stmt->execute([$userId, $userId, $userId]);
    json_response(true, $stmt->fetchAll());
}

json_response(false, null, 'Action inconnue.', 400);
