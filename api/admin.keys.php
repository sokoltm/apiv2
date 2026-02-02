<?php
require_once __DIR__.'/../config.php'; require_login();
$pdo = pdo(); $u = current_user(); $action = $_GET['action'] ?? null;

function is_admin(){ return current_user()['role']==='admin'; }
function is_owner($keyRow){ $u=current_user(); return $u && ($u['role']==='admin' || (int)$keyRow['owner_user_id']===(int)$u['id']); }

if (!$action && $_SERVER['REQUEST_METHOD']==='GET') {
    $q = trim($_GET['q'] ?? '');
    if ($u['role']==='admin') {
        $where = $q!=='' ? 'WHERE ak.label LIKE :q' : '';
        $stmt=$pdo->prepare("SELECT ak.*, u.name AS owner_name FROM api_keys ak LEFT JOIN users u ON u.id=ak.owner_user_id $where ORDER BY ak.id DESC");
        $stmt->execute($q!==''?[':q'=>"%$q%"]:[]); json_response(['rows'=>$stmt->fetchAll()]);
    } elseif ($u['role']==='premium') {
        $stmt=$pdo->prepare("SELECT ak.*, :nm AS owner_name FROM api_keys ak WHERE ak.owner_user_id=:o AND (ak.label LIKE :q OR :q='') ORDER BY ak.id DESC");
        $stmt->execute([':o'=>$u['id'], ':nm'=>$u['name'], ':q'=>"%$q%"]); json_response(['rows'=>$stmt->fetchAll()]);
    } else {
        $stmt=$pdo->prepare("SELECT ak.*, u.name AS owner_name FROM api_keys ak LEFT JOIN users u ON u.id=ak.owner_user_id WHERE ak.id=:id");
        $stmt->execute([':id'=>$u['assigned_api_key_id']]); json_response(['rows'=>$stmt->fetchAll()]);
    }
}
if ($action==='list_all' && $_SERVER['REQUEST_METHOD']==='GET') { require_role('admin'); $stmt=$pdo->query("SELECT id, token, label FROM api_keys ORDER BY id DESC"); json_response(['rows'=>$stmt->fetchAll()]); }

$b = read_json_body();
if ($action==='create' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!in_array($u['role'], ['admin','premium'])) json_response(['ok'=>false,'error'=>'forbidden'],403);
    $token = bin2hex(random_bytes(16));
    $stmt=$pdo->prepare("INSERT INTO api_keys (token,label,owner_user_id) VALUES (:t,:l,:o)");
    $stmt->execute([':t'=>$token, ':l'=>trim($b['label'] ?? ''), ':o'=>$u['id']]);
    json_response(['ok'=>true,'id'=>$pdo->lastInsertId(),'token'=>$token]);
}
if ($action==='set_label' && $_SERVER['REQUEST_METHOD']==='POST') {
    $stmt=$pdo->prepare("SELECT * FROM api_keys WHERE id=:id"); $stmt->execute([':id'=>(int)$b['id']]); $row=$stmt->fetch();
    if (!$row || !is_owner($row)) json_response(['ok'=>false,'error'=>'forbidden'],403);
    $upd=$pdo->prepare("UPDATE api_keys SET label=:l WHERE id=:id"); $upd->execute([':l'=>trim($b['label'] ?? ''), ':id'=>$row['id']]); json_response(['ok'=>true]);
}
if ($action==='delete' && $_SERVER['REQUEST_METHOD']==='POST') {
    $stmt=$pdo->prepare("SELECT * FROM api_keys WHERE id=:id"); $stmt->execute([':id'=>(int)$b['id']]); $row=$stmt->fetch();
    if (!$row) json_response(['ok'=>false,'error'=>'not found'],404);
    if (!is_owner($row)) json_response(['ok'=>false,'error'=>'forbidden'],403);
    if ((int)$row['is_default']===1 && !is_admin()) json_response(['ok'=>false,'error'=>'нельзя удалить дефолтный'],403);
    $pdo->prepare("UPDATE users SET assigned_api_key_id=NULL WHERE assigned_api_key_id=:id")->execute([':id'=>$row['id']]);
    $pdo->prepare("DELETE FROM api_keys WHERE id=:id")->execute([':id'=>$row['id']]);
    json_response(['ok'=>true]);
}
if ($action==='set_default' && $_SERVER['REQUEST_METHOD']==='POST') {
    require_role('admin');
    $id = (int)$b['id']; $pdo->exec("UPDATE api_keys SET is_default=0");
    $stmt=$pdo->prepare("UPDATE api_keys SET is_default=1 WHERE id=:id"); $stmt->execute([':id'=>$id]); json_response(['ok'=>true]);
}
json_response(['error'=>'Unsupported'],400);
