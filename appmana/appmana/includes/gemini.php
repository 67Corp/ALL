<?php
require_once __DIR__ . '/../config/config.php';

class GeminiClient {

    private string $apiKey;
    private string $apiUrl;

    public function __construct() {
        $this->apiKey = GEMINI_API_KEY;
        $this->apiUrl = GEMINI_API_URL;
    }

    // Appel brut à l'API Gemini
    private function call(string $prompt): string {
        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 4096,
            ],
        ]);

        $ch = curl_init($this->apiUrl . '?key=' . $this->apiKey);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => (APP_ENV === 'production'),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new RuntimeException('Erreur API Gemini (HTTP ' . $httpCode . ')');
        }

        $data = json_decode($response, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    // Récupérer le contenu d'une URL
    private function fetchUrl(string $url): string {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (AppMana/1.0)',
            CURLOPT_SSL_VERIFYPEER => (APP_ENV === 'production'),
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
            throw new RuntimeException('Impossible de récupérer l\'URL.');
        }

        // Extraire le texte
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        // Limiter à 12 000 caractères pour l'API
        return mb_substr(trim($text), 0, 12000);
    }

    // Résumer une URL
    public function summarizeUrl(string $url): string {
        $content = $this->fetchUrl($url);

        $prompt = <<<PROMPT
Tu es un assistant académique spécialisé en droit, gestion, management et informatique.
Résume le texte suivant de manière structurée en français.

Format de réponse requis (utilise ces titres exactement) :
**TITRE :** (titre du contenu)
**RÉSUMÉ COURT :** (2-3 phrases)
**POINTS CLÉS :**
- point 1
- point 2
- ...
**CONTENU DÉTAILLÉ :** (développement complet et pédagogique)
**MOTS-CLÉS :** terme1, terme2, terme3, ...

Texte à résumer :
$content
PROMPT;

        return $this->call($prompt);
    }

    // Générer un quiz depuis un contenu de cours
    public function generateQuiz(string $courseContent, string $themeContext = '', int $numQuestions = 10): array {
        // Limiter le contenu
        $content = mb_substr($courseContent, 0, 10000);

        $prompt = <<<PROMPT
Tu es un expert pédagogique en $themeContext.
À partir du contenu de cours suivant, génère exactement $numQuestions questions QCM en français.
Chaque question doit avoir 4 options de réponse.

Réponds UNIQUEMENT avec un tableau JSON valide, sans texte avant ou après, dans ce format exact :
[
  {
    "question": "...",
    "options": ["A. ...", "B. ...", "C. ...", "D. ..."],
    "correct_index": 0,
    "explanation": "Explication courte de la bonne réponse.",
    "difficulty": "facile"
  }
]

Les valeurs de "difficulty" peuvent être : "facile", "moyen", "difficile".
"correct_index" est l'index 0-basé dans le tableau "options".

Contenu du cours :
$content
PROMPT;

        $raw = $this->call($prompt);

        // Extraire le JSON (parfois Gemini ajoute du texte autour)
        preg_match('/\[.*\]/s', $raw, $matches);
        $json = $matches[0] ?? $raw;

        $questions = json_decode($json, true);

        if (!is_array($questions)) {
            throw new RuntimeException('Réponse Gemini invalide : JSON malformé.');
        }

        // Valider chaque question
        foreach ($questions as $i => $q) {
            if (!isset($q['question'], $q['options'], $q['correct_index'])) {
                throw new RuntimeException("Question $i invalide.");
            }
            if (!is_array($q['options']) || count($q['options']) < 2) {
                throw new RuntimeException("Options de la question $i invalides.");
            }
            if ($q['correct_index'] >= count($q['options'])) {
                $questions[$i]['correct_index'] = 0;
            }
        }

        return $questions;
    }

    // Répondre à une question de recherche
    public function answerSearch(string $query): string {
        $prompt = <<<PROMPT
Tu es un assistant spécialisé en droit, gestion, management et informatique.
Réponds à la question suivante de manière claire, concise et pédagogique (max 300 mots).
Inclus si applicable : définition, exemple pratique, référence légale ou académique.

Question : $query
PROMPT;

        return $this->call($prompt);
    }

    // Vérifier la limite d'utilisation
    public static function checkLimit(int $userId): bool {
        require_once __DIR__ . '/db.php';
        $stmt = db()->prepare('
            SELECT COUNT(*) FROM search_history
            WHERE user_id = ? AND result_src = "gemini"
            AND searched_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn() < GEMINI_HOURLY_LIMIT;
    }
}
