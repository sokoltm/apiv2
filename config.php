<?php
// config.php — конфигурация и базовые хелперы (чистый PHP + PDO)
declare(strict_types=1);
date_default_timezone_set('Europe/Moscow');
session_start();

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'bots_api');
define('DB_USER', getenv('DB_USER') ?: 'bots_api_usr');
define('DB_PASS', getenv('DB_PASS') ?: 'Oh}?Jq3Pdu$>Ds9E');
define('DB_CHARSET', 'utf8mb4');

function pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET time_zone = '+03:00'");
    }
    return $pdo;
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}
// безопасная сортировка
function order_by_clause(string $sort, string $order, array $whitelist): string {
    $sort = $whitelist[$sort] ?? $whitelist['default'] ?? null;
    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
    if (!$sort) return '';
    return " ORDER BY $sort $order ";
}

// ---- Аутентификация/авторизация ----
function current_user(): ?array {
    static $cached = null;
    if ($cached !== null) return $cached;
    if (!empty($_SESSION['uid'])) {
        $stmt = pdo()->prepare("SELECT id,name,email,role,balance,assigned_api_key_id FROM users WHERE id=?");
        $stmt->execute([$_SESSION['uid']]);
        $cached = $stmt->fetch() ?: null;
        return $cached;
    }
    return null;
}
function require_login(): void {
    if (!current_user()) { header('Location: /login.php'); exit; }
}
function require_role($roles): void {
    $u = current_user();
    if (!$u) { header('Location: /login.php'); exit; }
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>Недостаточно прав.</p>';
        exit;
    }
}
