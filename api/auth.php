<?php
require_once __DIR__.'/../config.php';
$pdo = pdo();
$action = $_GET['action'] ?? '';

if ($action === 'login' && $_SERVER['REQUEST_METHOD']==='POST') {
    $b = read_json_body();
    $email = trim($b['email'] ?? '');
    $pass = $b['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email=:e'); $stmt->execute([':e'=>$email]);
    if (!$u = $stmt->fetch()) json_response(['ok'=>false, 'error'=>'Неверные email или пароль'], 401);
    if (!$u['password_hash'] || !password_verify($pass, $u['password_hash'])) json_response(['ok'=>false, 'error'=>'Неверные email или пароль'], 401);
    $_SESSION['uid'] = $u['id'];
    json_response(['ok'=>true]);
}
if ($action === 'register' && $_SERVER['REQUEST_METHOD']==='POST') {
    $b = read_json_body();
    $name = trim($b['name'] ?? ''); $email = trim($b['email'] ?? ''); $pass = $b['password'] ?? '';
    if (!$name || !$email || strlen($pass)<6) json_response(['ok'=>false,'error'=>'Заполните имя, email и пароль (>=6)'], 422);
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $defaultKey = $pdo->query("SELECT id FROM api_keys WHERE is_default=1 ORDER BY id ASC LIMIT 1")->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,assigned_api_key_id) VALUES (:n,:e,:h,\'user\',:kid)');
    try { $stmt->execute([':n'=>$name, ':e'=>$email, ':h'=>$hash, ':kid'=>$defaultKey ?: null]); }
    catch (Throwable $e) { json_response(['ok'=>false,'error'=>'Email уже занят'], 409); }
    $_SESSION['uid'] = (int)$pdo->lastInsertId(); json_response(['ok'=>true]);
}
if ($action === 'me' && $_SERVER['REQUEST_METHOD']==='GET') {
    $u = current_user(); json_response(['ok'=>true, 'user'=>$u]);
}
json_response(['error'=>'Unsupported method/action'], 400);
