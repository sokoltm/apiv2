<?php
require_once __DIR__ . '/../config.php';
require_login();
$pdo = pdo();
$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 10)));
    $q = trim($_GET['q'] ?? ''); $ir = trim($_GET['ir'] ?? ''); $tag = trim($_GET['tag'] ?? ''); $active = $_GET['active'] ?? '';
    $sort = $_GET['sort'] ?? 'updated_at'; $order = $_GET['order'] ?? 'desc';

    $where = []; $params = [];
    if ($q !== '') { $where[] = 's.name LIKE :q'; $params[':q'] = "%$q%"; }
    if ($ir !== '') { $where[] = 's.ir = :ir'; $params[':ir'] = (int)$ir; }
    if ($tag !== '') { $where[] = 's.tag LIKE :tag'; $params[':tag'] = "%$tag%"; }
    if ($active !== '' && ($active==='0' || $active==='1')) { $where[] = 's.is_active = :a'; $params[':a'] = (int)$active; }
    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    $total = $pdo->prepare("SELECT COUNT(*) FROM sites s $whereSql"); $total->execute($params); $total = (int)$total->fetchColumn();

    $whitelist = ['default'=>'s.updated_at','name'=>'s.name','ir'=>'s.ir','cph'=>'clicks_per_hour','is_active'=>'s.is_active','tag'=>'s.tag','phrases_count'=>'phrases_count','updated_at'=>'s.updated_at'];
    $orderSql = order_by_clause($sort, $order, $whitelist);
    $limit = " LIMIT ".(($page-1)*$pageSize).", ".$pageSize;

    $sql = "SELECT s.*, (SELECT COUNT(*) FROM phrases p WHERE p.site_id = s.id) AS phrases_count,
            COALESCE((SELECT SUM(sc.clicks) FROM stats_clicks sc WHERE sc.site_id = s.id AND sc.occurred_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)), 0) AS clicks_per_hour
            FROM sites s $whereSql $orderSql $limit";
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
    json_response(['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'page_size'=>$pageSize]);
}

$body = read_json_body();

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO sites (name, ir, is_active, tag) VALUES (:name,:ir,:is_active,:tag)");
    $stmt->execute([':name'=>trim($body['name'] ?? ''), ':ir'=>(int)($body['ir'] ?? 0), ':is_active'=>(int)($body['is_active'] ?? 1), ':tag'=>trim($body['tag'] ?? '')]);
    json_response(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
}
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE sites SET name=:name, ir=:ir, is_active=:is_active, tag=:tag WHERE id=:id");
    $stmt->execute([':name'=>trim($body['name'] ?? ''), ':ir'=>(int)($body['ir'] ?? 0), ':is_active'=>(int)($body['is_active'] ?? 1), ':tag'=>trim($body['tag'] ?? ''), ':id'=>(int)$body['id']]);
    json_response(['ok'=>true]);
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM sites WHERE id=:id"); $stmt->execute([':id'=>(int)$body['id']]); json_response(['ok'=>true]);
}
if ($action === 'duplicate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$body['id']; $withPhrases = !empty($body['with_phrases']); $pdo->beginTransaction();
    try {
        $orig = $pdo->prepare("SELECT * FROM sites WHERE id=:id"); $orig->execute([':id'=>$id]); $s = $orig->fetch();
        if (!$s) throw new Exception('site not found');
        $stmt = $pdo->prepare("INSERT INTO sites (name, ir, is_active, tag) VALUES (:name,:ir,:is_active,:tag)");
        $stmt->execute([':name'=>$s['name'].' (копия)', ':ir'=>$s['ir'], ':is_active'=>$s['is_active'], ':tag'=>$s['tag']]);
        $newId = (int)$pdo->lastInsertId();
        if ($withPhrases) {
            $ph = $pdo->prepare("SELECT * FROM phrases WHERE site_id=:id"); $ph->execute([':id'=>$id]);
            while ($row = $ph->fetch()) {
                $ins = $pdo->prepare("INSERT INTO phrases (site_id,text,freq,tag) VALUES (:site_id,:text,:freq,:tag)");
                $ins->execute([':site_id'=>$newId, ':text'=>$row['text'], ':freq'=>$row['freq'], ':tag'=>$row['tag']]);
            }
        }
        $pdo->commit(); json_response(['ok'=>true, 'id'=>$newId]);
    } catch (Throwable $e) { $pdo->rollBack(); json_response(['ok'=>false, 'error'=>$e->getMessage()], 500); }
}
json_response(['error'=>'Unsupported method/action'], 400);
