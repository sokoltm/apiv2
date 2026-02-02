<?php
require_once __DIR__.'/../config.php';
$pdo = pdo();

$exists = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($exists === 0) { echo '<h3>Сначала выполните db/init.sql</h3>'; exit; }

$admin = $pdo->query("SELECT * FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1")->fetch();
if (!$admin) { echo 'Нет пользователя с ролью admin'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pass = $_POST['password'] ?? '';
  if (strlen($pass) < 6) { echo 'Пароль должен быть не короче 6 символов'; exit; }
  $hash = password_hash($pass, PASSWORD_BCRYPT);
  $stmt = $pdo->prepare("UPDATE users SET password_hash=:h WHERE id=:id");
  $stmt->execute([':h'=>$hash, ':id'=>$admin['id']]);

  $hasDefault = (int)$pdo->query("SELECT COUNT(*) FROM api_keys WHERE is_default=1")->fetchColumn();
  if ($hasDefault == 0) {
      $token = bin2hex(random_bytes(16));
      $stmt = $pdo->prepare("INSERT INTO api_keys (token,label,owner_user_id,is_default) VALUES (:t,:l,:o,1)");
      $stmt->execute([':t'=>$token, ':l'=>'Default', ':o'=>$admin['id']]);
  }
  echo 'ОК. Пароль админа установлен, дефолтный API-ключ создан. Удалите install.php.'; exit;
}
?>
<form method="post" style="max-width:480px;margin:40px auto;font-family:system-ui;">
  <h3>Установка: задайте пароль админа</h3>
  <p>Email админа: <?=htmlspecialchars($admin['email'])?></p>
  <input type="password" name="password" placeholder="Новый пароль (>=6)" required style="width:100%;padding:8px">
  <button type="submit" style="margin-top:8px;padding:8px 12px;">Установить</button>
</form>
