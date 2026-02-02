<?php require_once __DIR__.'/../config.php'; 
$u = current_user();
$public = basename($_SERVER['SCRIPT_NAME']);
if (!$u && !in_array($public, ['login.php','register.php','install.php','index.php'])) { header('Location: /login.php'); exit; }
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Traffic Pulse</title>
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="/assets/js/app.js"></script>
</head>
<body>
  <div class="nav">
    <a href="/sites.php">Сайты</a>
    <a href="/stats.php">Статистика</a>
    <a href="/profile.php">Личный кабинет</a>
    <?php if ($u && $u['role']==='admin'): ?><a href="/admin/index.php">Админка</a><?php endif; ?>
    <span style="margin-left:auto"></span>
    <?php if ($u): ?>
      <span>Пользователь: <?=htmlspecialchars($u['name'])?> (<?=htmlspecialchars($u['role'])?>)</span>
      <a href="/logout.php" style="margin-left:12px;">Выйти</a>
    <?php endif; ?>
  </div>
  <div class="container">
