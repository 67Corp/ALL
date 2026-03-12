<?php
/**
 * api/upload_fiche.php
 * Sauvegarde une fiche de cours dans MySQL
 */

header('Content-Type: application/json');

session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php'; // retourne $pdo

$body = json_decode(file_get_contents('php://input'), true);

// ─── Validation ───────────────────────────────────────────
$titre     = trim($body['titre']      ?? '');
$contenu   = trim($body['contenu']    ?? '');
$categorie = trim($body['categorie']  ?? '');
$tags      = trim($body['tags']       ?? '');
$sourceUrl = trim($body['source_url'] ?? '');

if (empty($titre) || empty($contenu)) {
    echo json_encode(['success' => false, 'error' => 'Titre et contenu obligatoires']);
    exit;
}

// ─── Nettoyage HTML (anti-XSS basique) ───────────────────
// On autorise les balises pédagogiques mais on nettoie les attributs dangereux
$allowedTags = '<h1><h2><h3><h4><ul><ol><li><p><strong><em><code><pre><blockquote><section><dl><dt><dd><table><tr><th><td><br><hr><span><div>';
$contenu = strip_tags($contenu, $allowedTags);
// Supprime les attributs style= et onclick= etc.
$contenu = preg_replace('/\s*on\w+="[^"]*"/i', '', $contenu);
$contenu = preg_replace('/\s*javascript:[^\s"\'`>]*/i', '', $contenu);

// ─── Insertion MySQL ──────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        INSERT INTO fiches (titre, contenu, categorie, tags, source_url, auteur_id, created_at)
        VALUES (:titre, :contenu, :categorie, :tags, :source_url, :auteur_id, NOW())
    ");
    $stmt->execute([
        ':titre'      => mb_substr($titre, 0, 200),
        ':contenu'    => $contenu,
        ':categorie'  => mb_substr($categorie, 0, 100),
        ':tags'       => mb_substr($tags, 0, 500),
        ':source_url' => mb_substr($sourceUrl, 0, 500),
        ':auteur_id'  => $_SESSION['user_id'],
    ]);

    $newId = $pdo->lastInsertId();

    // Log dans une table d'activité si elle existe
    try {
        $pdo->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, created_at)
            VALUES (?, 'create_fiche', 'fiche', ?, NOW())
        ")->execute([$_SESSION['user_id'], $newId]);
    } catch (Exception $e) { /* table optionnelle */ }

    echo json_encode([
        'success' => true,
        'id'      => $newId,
        'message' => "Fiche « $titre » sauvegardée avec succès",
    ]);

} catch (PDOException $e) {
    error_log('upload_fiche PDO error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
