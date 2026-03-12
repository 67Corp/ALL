<?php
// ============================================================
// Fonctions utilitaires AppMana
// ============================================================

function s(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function get_flash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function json_response(bool $success, $data = null, string $error = '', int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($success
        ? ['success' => true,  'data'  => $data]
        : ['success' => false, 'error' => $error]
    );
    exit;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function format_date(string $datetime): string {
    $dt = new DateTime($datetime);
    $months = ['jan','fév','mar','avr','mai','jun','jul','aoû','sep','oct','nov','déc'];
    return $dt->format('d') . ' ' . $months[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'à l\'instant';
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'j';
    return format_date($datetime);
}

function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => ($currentPage - 1) * $perPage,
        'has_prev'    => $currentPage > 1,
        'has_next'    => $currentPage < $totalPages,
    ];
}

function excerpt(string $text, int $length = 200): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

function theme_badge(array $theme): string {
    return sprintf(
        '<span class="badge" style="background:%s">%s</span>',
        s($theme['color_hex']),
        s($theme['name'])
    );
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, username, display_name, email, role, status, avatar_path FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function notif_count(): int {
    if (empty($_SESSION['user_id'])) return 0;
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

function add_notification(int $userId, string $type, string $message, array $payload = []): void {
    $stmt = db()->prepare('INSERT INTO notifications (user_id, type, message, payload) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $type, $message, $payload ? json_encode($payload) : null]);
}

function avatar_url(?string $path): string {
    if ($path && file_exists(UPLOAD_DIR . basename($path))) {
        return APP_URL . '/uploads/avatars/' . basename($path);
    }
    return APP_URL . '/assets/images/avatar.png';
}
