<?php include __DIR__.'/../header.php'; $u=current_user(); if(!in_array($u['role'], ['admin','premium'])){ http_response_code(403); echo 'Недостаточно прав'; include __DIR__.'/../footer.php'; exit; } ?>
<div class="card">
    <h1>API-ключи</h1>
    <div class="input-row">
        <input id="q" placeholder="Поиск по метке">
        <button id="new">+ Создать ключ</button>
        <?php if($u['role']==='admin'): ?><button id="set-default">Сделать дефолтным (по ID)</button><?php endif; ?>
    </div>
    <table id="tbl"><thead><tr>
            <th>ID</th><th>Токен</th><th>Метка</th><th>Владелец</th><th>Дефолтный</th><th>Действия</th>
        </tr></thead><tbody></tbody></table>
</div>
<script>
    function load() {
        Api.get('/api/admin.keys.php',{q: $('#q').val().trim()}).then(res=>{
            const $tb=$('#tbl tbody').empty();
            res.rows.forEach(k=>{
                $tb.append(`<tr data-id="${k.id}">
        <td>${k.id}</td><td>${k.token}</td><td>${k.label??''}</td><td>${k.owner_name??''}</td><td>${k.is_default?'да':'—'}</td>
        <td class="actions">
          <button class="label">Метка</button>
          <button class="del">Удалить</button>
        </td>
      </tr>`);
            });
            $('.label').off().on('click', function(){
                const id=$(this).closest('tr').data('id');
                const val=prompt('Новая метка'); if(val===null) return;
                Api.post('/api/admin.keys.php?action=set_label',{id, label: val}).then(load);
            });
            $('.del').off().on('click', function(){
                const id=$(this).closest('tr').data('id');
                if(!confirm('Удалить ключ? Пользователи, привязанные к нему, потеряют назначение.')) return;
                Api.post('/api/admin.keys.php?action=delete',{id}).then(load);
            });
        });
    }
    $('#new').on('click', function(){ Api.post('/api/admin.keys.php?action=create', {label: prompt('Метка для ключа','')||''}).then(load); });
    $('#set-default').on('click', function(){ const id = prompt('ID ключа для дефолта'); if(!id) return; Api.post('/api/admin.keys.php?action=set_default',{id:parseInt(id)}).then(load); });
    $('#q').on('input', load);
    load();
</script>
<?php include __DIR__.'/../footer.php'; ?>
