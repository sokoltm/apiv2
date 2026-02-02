<?php
require_once __DIR__ . '/../config.php';
require_login();
$pdo = pdo();
$action = $_GET['action'] ?? null;

function parse_dt($s) {
    if (!$s) return null;
    $t = strtotime($s);
    return $t ? date('Y-m-d H:i:s', $t) : null;
}

/**
 * Блок 1. Агрегаты по сайтам
 * Исправление: условия по дате в ON у LEFT JOIN, по названию — в WHERE (ровно один WHERE).
 */
if (!$action && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 10)));
    $q = trim($_GET['q'] ?? '');
    $sort = $_GET['sort'] ?? 'clicks';
    $order = $_GET['order'] ?? 'desc';
    $from = parse_dt($_GET['from'] ?? '');
    $to   = parse_dt($_GET['to'] ?? '');

    $params = [];

    // JOIN с условиями по дате
    $join = "LEFT JOIN stats_clicks sc ON sc.site_id = s.id";
    $joinConds = [];
    if ($from) { $joinConds[] = "sc.occurred_at >= :from"; $params[':from'] = $from; }
    if ($to)   { $joinConds[] = "sc.occurred_at <= :to";   $params[':to']   = $to; }
    if ($joinConds) $join .= " AND " . implode(" AND ", $joinConds);

    // WHERE только по названию
    $whereConds = [];
    if ($q !== '') { $whereConds[] = "s.name LIKE :q"; $params[':q'] = "%$q%"; }
    $whereSql = $whereConds ? ('WHERE ' . implode(' AND ', $whereConds)) : '';

    // сортировка/лимиты
    $whitelist = ['default'=>'clicks','name'=>'s.name','ir'=>'s.ir','clicks'=>'clicks'];
    $orderSql = order_by_clause($sort, $order, $whitelist);
    $limitSql = " LIMIT ".(($page-1)*$pageSize).", ".$pageSize;

    // total (через подзапрос без второй WHERE)
    $sqlTotal = "
        SELECT COUNT(*) FROM (
          SELECT s.id
          FROM sites s
          $join
          $whereSql
          GROUP BY s.id
        ) t
    ";
    $stTotal = $pdo->prepare($sqlTotal);
    $stTotal->execute($params);
    $total = (int)$stTotal->fetchColumn();

    // данные
    $sqlRows = "
      SELECT s.id AS site_id, s.name, s.ir, COALESCE(SUM(sc.clicks),0) AS clicks
      FROM sites s
      $join
      $whereSql
      GROUP BY s.id
      $orderSql
      $limitSql
    ";
    $st = $pdo->prepare($sqlRows);
    $st->execute($params);
    $rows = $st->fetchAll();

    json_response(['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'page_size'=>$pageSize]);
}

/**
 * Блок 2. Агрегаты по фразам конкретного сайта
 * Исправление: фильтры по дате и site_id — в ON у LEFT JOIN; по тексту — в WHERE (один WHERE).
 * Это сохраняет строки фраз даже при отсутствии кликов в периоде.
 */
if ($action === 'phrases' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $siteId = (int)($_GET['site_id'] ?? 0);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 10)));
    $q = trim($_GET['q'] ?? '');
    $sort = $_GET['sort'] ?? 'clicks';
    $order = $_GET['order'] ?? 'desc';
    $from = parse_dt($_GET['from'] ?? '');
    $to   = parse_dt($_GET['to'] ?? '');

    $params = [':sid' => $siteId];

    // JOIN: привязываем клики к фразам и сразу фильтруем по сайту/датам В ON
    $join = "LEFT JOIN stats_clicks sc ON sc.phrase_id = p.id AND sc.site_id = :sid";
    $joinConds = [];
    if ($from) { $joinConds[] = "sc.occurred_at >= :from"; $params[':from'] = $from; }
    if ($to)   { $joinConds[] = "sc.occurred_at <= :to";   $params[':to']   = $to; }
    if ($joinConds) $join .= " AND " . implode(" AND ", $joinConds);

    // WHERE: сам список фраз сайта + опциональный поиск по тексту
    $whereConds = ["p.site_id = :sid"];
    if ($q !== '') { $whereConds[] = "p.text LIKE :q"; $params[':q'] = "%$q%"; }
    $whereSql = 'WHERE ' . implode(' AND ', $whereConds);

    // сортировка/лимиты
    $whitelist = ['default'=>'clicks','text'=>'p.text','clicks'=>'clicks'];
    $orderSql = order_by_clause($sort, $order, $whitelist);
    $limitSql = " LIMIT ".(($page-1)*$pageSize).", ".$pageSize;

    // total
    $sqlTotal = "
      SELECT COUNT(*) FROM (
        SELECT p.id
        FROM phrases p
        $join
        $whereSql
        GROUP BY p.id
      ) t
    ";
    $stTotal = $pdo->prepare($sqlTotal);
    $stTotal->execute($params);
    $total = (int)$stTotal->fetchColumn();

    // данные
    $sqlRows = "
      SELECT p.text, COALESCE(SUM(sc.clicks),0) AS clicks
      FROM phrases p
      $join
      $whereSql
      GROUP BY p.id
      $orderSql
      $limitSql
    ";
    $st = $pdo->prepare($sqlRows);
    $st->execute($params);
    $rows = $st->fetchAll();

    json_response(['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'page_size'=>$pageSize]);
}

json_response(['error'=>'Unsupported method/action'], 400);
