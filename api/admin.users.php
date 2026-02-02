<?php
require_once __DIR__.'/../config.php'; require_login(); require_role('admin');
$pdo = pdo(); $action = $_GET['action'] ?? null;

if (!$action && $_SERVER['REQUEST_METHOD']==='GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $ps = min(100, max(1, (int)($_GET['page_size'] ?? 10)));
    $q = trim($_GET['q'] ?? ''); $role = trim($_GET['role'] ?? ''); $sort = $_GET['sort'] ?? 'name'; $order = $_GET['order'] ?? 'asc';
    $where = []; $p=[]; if ($q!=='') { $where[] = '(u.name LIKE :q OR u.email LIKE :q)'; $p[':q']="%$q%"; }
    if ($role!=='') { $where[] = 'u.role=:r'; $p[':r']=$role; }
    $ws = $where ? ('WHERE '.implode(' AND ',$where)) : '';
    $total = $pdo->prepare("SELECT COUNT(*) FROM users u $ws"); $total->execute($p); $total=(int)$total->fetchColumn();
    $wl=['default'=>'u.name','name'=>'u.name','email'=>'u.email','role'=>'u.role','balance'=>'u.balance','api_key'=>'ak.label'];
    $ob = order_by_clause($sort,$order,$wl); $lim = " LIMIT ".(($page-1)*$ps).", ".$ps;
    $stmt=$pdo->prepare("SELECT u.id,u.name,u.email,u.role,u.balance,u.assigned_api_key_id, ak.token, ak.label AS api_label
                         FROM users u LEFT JOIN api_keys ak ON ak.id=u.assigned_api_key_id $ws $ob $lim");
    $stmt->execute($p); $rows=$stmt->fetchAll();
    json_response(['rows'=>$rows,'total'=>$total,'page'=>$page,'page_size'=>$ps]);
}
$b = read_json_body();
if ($action==='set_balance' && $_SERVER['REQUEST_METHOD']==='POST') {
    $stmt=$pdo->prepare('UPDATE users SET balance=:b WHERE id=:id'); $stmt->execute([':b'=>(float)$b['balance'], ':id'=>(int)$b['id']]); json_response(['ok'=>true]);
}
if ($action==='set_role' && $_SERVER['REQUEST_METHOD']==='POST') {
    $role = $b['role']; if (!in_array($role, ['admin','premium','user'], true)) json_response(['ok'=>false,'error'=>'bad role'],422);
    $stmt=$pdo->prepare('UPDATE users SET role=:r WHERE id=:id'); $stmt->execute([':r'=>$role, ':id'=>(int)$b['id']]); json_response(['ok'=>true]);
}
if ($action==='assign_key' && $_SERVER['REQUEST_METHOD']==='POST') {
    $stmt=$pdo->prepare('UPDATE users SET assigned_api_key_id=:k WHERE id=:id'); $stmt->execute([':k'=>(int)$b['api_key_id'], ':id'=>(int)$b['user_id']]); json_response(['ok'=>true]);
}
json_response(['error'=>'Unsupported'],400);
