<?php require_once __DIR__.'/../config.php'; ?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Вход</title>
<link rel="stylesheet" href="/assets/css/app.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/assets/js/app.js"></script>
</head><body><div class="container">
<div class="card"><h1>Вход</h1>
<div class="input-row"><input id="email" placeholder="Email"><input id="password" type="password" placeholder="Пароль">
<button id="login">Войти</button> <a href="/register.php">Регистрация</a></div>
<div id="msg"></div>
</div></div>
<script>
$('#login').on('click', function(){
  Api.post('/api/auth.php?action=login', {email: $('#email').val().trim(), password: $('#password').val()}).then(r => {
    if (r.ok) location.href='/sites.php'; else $('#msg').text(r.error||'Ошибка');
  });
});
</script></body></html>
