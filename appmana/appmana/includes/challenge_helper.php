<?php
// Appelé après la soumission d'un quiz dans un contexte de défi
function handle_challenge_completion(int $challengeId, int $userId, int $score): void {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM challenges WHERE id = ? AND status = "accepted"');
    $stmt->execute([$challengeId]);
    $challenge = $stmt->fetch();
    if (!$challenge) return;

    // Vérifier si l'autre joueur a aussi terminé
    $otherUserId = $userId === (int)$challenge['challenger_id']
        ? $challenge['challenged_id']
        : $challenge['challenger_id'];

    $other = $pdo->prepare('SELECT score FROM quiz_sessions WHERE challenge_id = ? AND user_id = ? AND finished_at IS NOT NULL');
    $other->execute([$challengeId, $otherUserId]);
    $otherSession = $other->fetch();

    if (!$otherSession) return; // L'autre n'a pas encore terminé

    // Les deux ont terminé : déterminer le gagnant
    $otherScore = (int)$otherSession['score'];
    $winnerId   = $score > $otherScore ? $userId : ($otherScore > $score ? $otherUserId : null);

    $pdo->prepare('UPDATE challenges SET status = "completed", winner_id = ?, completed_at = NOW() WHERE id = ?')
        ->execute([$winnerId, $challengeId]);

    // Notifier les deux joueurs
    $winnerMsg = $winnerId
        ? ($winnerId === $userId ? 'Vous avez gagné le défi ! 🏆' : 'Vous avez perdu le défi.')
        : 'Le défi est terminé : égalité !';

    add_notification($userId, 'challenge_result', $winnerMsg, ['challenge_id' => $challengeId]);

    $loserMsg = $winnerId
        ? ($winnerId !== $otherUserId ? 'Vous avez gagné le défi ! 🏆' : 'Vous avez perdu le défi.')
        : 'Le défi est terminé : égalité !';
    add_notification($otherUserId, 'challenge_result', $loserMsg, ['challenge_id' => $challengeId]);
}
