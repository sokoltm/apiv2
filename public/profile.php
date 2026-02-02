<?php include 'header.php';
$pdo = pdo();
$u = current_user();
$user = $pdo->prepare("SELECT u.*, ak.token, ak.label FROM users u LEFT JOIN api_keys ak ON ak.id=u.assigned_api_key_id WHERE u.id=:id");
$user->execute([':id'=>$u['id']]);
$user = $user->fetch();
?>
<div class="card">
    <h1>Личный кабинет</h1>
    <p><strong>Имя:</strong> <?=htmlspecialchars($user['name'])?></p>
    <p><strong>Email:</strong> <?=htmlspecialchars($user['email'])?></p>
    <p><strong>Роль:</strong> <?=htmlspecialchars($user['role'])?></p>
    <p><strong>Баланс:</strong> <span id="balance"><?=$user['balance']?></span> ₽</p>

    <?php if ($user['role']==='user'): ?>
        <p><strong>Назначенный API-ключ:</strong>
            <?= $user['token'] ? htmlspecialchars($user['token']) : '— не назначен —' ?>
            <?php if($user['label']): ?> (<?=htmlspecialchars($user['label'])?>) <?php endif; ?>
        </p>
        <p class="small">Обычным пользователям ключ создаёт/назначает администратор.</p>
    <?php else: ?>
        <p><strong>Мои API-ключи:</strong> → <a href="/admin/keys.php">управление ключами</a></p>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
