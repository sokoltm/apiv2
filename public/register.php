<?php require_once __DIR__.'/../config.php'; ?>
<!doctype html><html lang="ru"><head>
    <meta charset="utf-8"><title>Регистрация</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/app.js"></script>
</head><body><div class="container">
    <div class="card"><h1>Регистрация</h1>
        <div class="input-row"><input id="name" placeholder="Имя"><input id="email" placeholder="Email"><input id="password" type="password" placeholder="Пароль">
            <button id="register">Создать аккаунт</button>  <a href="/login.php">Войти</a></div>
        <div id="msg"></div>
    </div></div>
<script>
    $('#register').on('click', function(){
        Api.post('/api/auth.php?action=register', {name: $('#name').val().trim(), email: $('#email').val().trim(), password: $('#password').val()}).then(r => {
            if (r.ok) location.href='/sites.php'; else $('#msg').text(r.error||'Ошибка');
        });
    });
</script></body></html>
