<?php
/**
 * api/gemini.php
 * Proxy backend PHP → Gemini Pro API
 * Ne jamais appeler Gemini directement depuis le JS (exposition de la clé API)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// ─── Config ───────────────────────────────────────────────
require_once __DIR__ . '/../config/config.php';
// GEMINI_API_KEY défini dans config.php

// ─── Sécurité : session / auth basique ────────────────────
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// ─── Lecture du body JSON ──────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
$prompt = trim($body['prompt'] ?? '');

if (empty($prompt)) {
    echo json_encode(['success' => false, 'error' => 'Prompt vide']);
    exit;
}

// ─── Limite taille ────────────────────────────────────────
if (strlen($prompt) > 60000) {
    $prompt = substr($prompt, 0, 60000) . "\n[...contenu tronqué...]";
}

// ─── Appel Gemini Pro API ─────────────────────────────────
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=' . GEMINI_API_KEY;

$systemInstruction = "Tu es un assistant pédagogique expert. Tu génères uniquement du HTML structuré et propre, sans balises <html><head><body>. Utilise des commentaires <!-- SECTION: Titre --> pour séparer les grandes sections. Rends le contenu clair, pédagogique et bien formaté.";

$payload = [
    'system_instruction' => [
        'parts' => [['text' => $systemInstruction]]
    ],
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => $prompt]]]
    ],
    'generationConfig' => [
        'temperature'     => 0.4,
        'maxOutputTokens' => 8192,
        'topP'            => 0.8,
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_ONLY_HIGH'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
    ],
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'cURL : ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200) {
    $msg = $data['error']['message'] ?? 'Erreur HTTP ' . $httpCode;
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// ─── Extraction du texte HTML généré ─────────────────────
$html = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($html)) {
    echo json_encode(['success' => false, 'error' => 'Réponse Gemini vide']);
    exit;
}

// Nettoyage : retire les fences markdown si Gemini les ajoute quand même
$html = preg_replace('/^```(?:html)?\s*/i', '', $html);
$html = preg_replace('/\s*```$/', '', $html);

echo json_encode([
    'success'      => true,
    'html'         => $html,
    'tokens_used'  => $data['usageMetadata']['totalTokenCount'] ?? null,
]);
