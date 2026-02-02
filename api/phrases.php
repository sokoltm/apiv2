<?php
require_once __DIR__ . '/../config.php';
require_login();
$pdo = pdo();
$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 10)));
    $siteId = (int)($_GET['site_id'] ?? 0);
    if (!$siteId) json_response(['rows'=>[], 'total'=>0, 'page'=>$page, 'page_size'=>$pageSize]);
    $q = trim($_GET['q'] ?? ''); $tag = trim($_GET['tag'] ?? '');
    $sort = $_GET['sort'] ?? 'updated_at'; $order = $_GET['order'] ?? 'desc';

    $where = ['p.site_id = :sid']; $params = [':sid'=>$siteId];
    if ($q!=='') { $where[] = 'p.text LIKE :q'; $params[':q'] = "%$q%"; }
    if ($tag!=='') { $where[] = 'p.tag LIKE :tag'; $params[':tag'] = "%$tag%"; }
    $whereSql = 'WHERE '.implode(' AND ', $where);

    $total = $pdo->prepare("SELECT COUNT(*) FROM phrases p $whereSql"); $total->execute($params); $total = (int)$total->fetchColumn();

    $whitelist = ['default'=>'p.updated_at','text'=>'p.text','freq'=>'p.freq','tag'=>'p.tag','updated_at'=>'p.updated_at'];
    $orderSql = order_by_clause($sort, $order, $whitelist);
    $limit = " LIMIT ".(($page-1)*$pageSize).", ".$pageSize;

    $stmt = $pdo->prepare("SELECT p.* FROM phrases p $whereSql $orderSql $limit");
    $stmt->execute($params); $rows = $stmt->fetchAll();
    json_response(['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'page_size'=>$pageSize]);
}

$body = read_json_body();
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO phrases (site_id, text, freq, tag) VALUES (:site_id,:text,:freq,:tag)");
    $stmt->execute([':site_id'=>(int)$body['site_id'], ':text'=>trim($body['text'] ?? ''), ':freq'=>(int)($body['freq'] ?? 0), ':tag'=>trim($body['tag'] ?? '')]);
    json_response(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
}
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE phrases SET text=:text, freq=:freq, tag=:tag WHERE id=:id");
    $stmt->execute([':text'=>trim($body['text'] ?? ''), ':freq'=>(int)($body['freq'] ?? 0), ':tag'=>trim($body['tag'] ?? ''), ':id'=>(int)$body['id']]);
    json_response(['ok'=>true]);
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM phrases WHERE id=:id"); $stmt->execute([':id'=>(int)$body['id']]); json_response(['ok'=>true]);
}
if ($action === 'duplicate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $orig = $pdo->prepare("SELECT * FROM phrases WHERE id=:id"); $orig->execute([':id'=>(int)$body['id']]);
    if (!$row = $orig->fetch()) json_response(['ok'=>false, 'error'=>'not found'], 404);
    $ins = $pdo->prepare("INSERT INTO phrases (site_id,text,freq,tag) VALUES (:site_id,:text,:freq,:tag)");
    $ins->execute([':site_id'=>$row['site_id'], ':text'=>$row['text'].' (копия)', ':freq'=>$row['freq'], ':tag'=>$row['tag']]);
    json_response(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
}
json_response(['error'=>'Unsupported method/action'], 400);
