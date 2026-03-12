<?php
/**
 * api/scraper.php
 * Récupère le texte brut d'une URL côté serveur
 * (évite les problèmes CORS et expose pas la clé Gemini)
 */

header('Content-Type: application/json');

session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$url  = trim($body['url'] ?? '');

// ─── Validation URL ───────────────────────────────────────
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'URL invalide']);
    exit;
}

// Blocage des URLs locales / internes
$host = parse_url($url, PHP_URL_HOST);
$blocked = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
if (in_array($host, $blocked) || str_starts_with($host, '192.168.') || str_starts_with($host, '10.')) {
    echo json_encode(['success' => false, 'error' => 'URL locale bloquée']);
    exit;
}

// ─── Fetch de la page ─────────────────────────────────────
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; StudyAI-Bot/1.0)',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER     => ['Accept-Language: fr-FR,fr;q=0.9,en;q=0.8'],
]);

$html     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error || $httpCode < 200 || $httpCode >= 400) {
    echo json_encode(['success' => false, 'error' => "Impossible de lire la page (HTTP $httpCode)"]);
    exit;
}

// ─── Extraction du texte propre ───────────────────────────
// Supprime scripts, styles, nav, footer
$html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
$html = preg_replace('/<style\b[^>]*>.*?<\/style>/is',   '', $html);
$html = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is',       '', $html);
$html = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $html);
$html = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $html);
$html = preg_replace('/<aside\b[^>]*>.*?<\/aside>/is',   '', $html);
$html = preg_replace('/<!--.*?-->/s',                    '', $html);

// Convertit les balises de titres en marqueurs textuels
$html = preg_replace('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', "\n\n## $2\n", $html);
$html = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "$1\n\n", $html);
$html = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "• $1\n", $html);
$html = preg_replace('/<br\s*\/?>/i', "\n", $html);

// Strip remaining tags
$text = strip_tags($html);

// Nettoyage des espaces
$text = preg_replace('/\n{3,}/', "\n\n", $text);
$text = preg_replace('/[ \t]+/', ' ', $text);
$text = html_entity_decode(trim($text), ENT_QUOTES, 'UTF-8');

// Limite à 20 000 caractères
$text = mb_substr($text, 0, 20000);

echo json_encode([
    'success' => true,
    'text'    => $text,
    'length'  => mb_strlen($text),
    'url'     => $url,
]);
