<?php include __DIR__.'/../header.php'; require_role('admin'); ?>
<div class="card">
    <h1>Пользователи</h1>
    <div class="input-row">
        <input id="q" placeholder="Поиск по имени/email">
        <label class="small">Роль: <select id="role"><option value="">Любая</option><option>admin</option><option>premium</option><option>user</option></select></label>
        <label class="small">Page size: <select id="ps"><option>10</option><option>20</option><option>50</option></select></label>
    </div>
    <table id="tbl"><thead><tr>
            <th data-sort="name">Имя</th><th data-sort="email">Email</th><th data-sort="role">Роль</th>
            <th data-sort="balance">Баланс</th><th data-sort="api_key">Назначенный API-ключ</th><th>Действия</th>
        </tr></thead><tbody></tbody></table>
    <div id="pager"></div>
</div>
<script>
    const st = {page:1,pageSize:10,sort:'name',order:'asc'};
    function load() {
        Api.get('/api/admin.users.php',{page:st.page,page_size:st.pageSize,sort:st.sort,order:st.order,q:$('#q').val().trim(),role:$('#role').val()}).then(res=>{
            const $tb=$('#tbl tbody').empty();
            res.rows.forEach(r=>{
                $tb.append(`<tr data-id="${r.id}">
        <td>${r.name}</td><td>${r.email}</td><td>${r.role}</td>
        <td>${r.balance}</td><td>${r.api_label??''}${r.token ? ' ('+r.token.slice(0,6)+'…)' : ''}</td>
        <td class="actions">
          <button class="bal">Баланс</button>
          <button class="role">Роль</button>
          <button class="assign-key">Назначить ключ</button>
        </td>
      </tr>`);
            });
            bindSort($('#tbl'), st, (s,o)=>{st.sort=s;st.order=o;load();});
            renderPager($('#pager'), res.total, res.page, res.page_size, (p)=>{st.page=p;load();});

            $('.bal').off().on('click', function(){
                const id=$(this).closest('tr').data('id'); const val=prompt('Новый баланс'); if(val===null) return;
                Api.post('/api/admin.users.php?action=set_balance',{id, balance: parseFloat(val||0)}).then(load);
            });
            $('.role').off().on('click', function(){
                const id=$(this).closest('tr').data('id'); const val=prompt('Роль (admin/premium/user)'); if(!val) return;
                Api.post('/api/admin.users.php?action=set_role',{id, role: val}).then(load);
            });
            $('.assign-key').off().on('click', function(){
                const id=$(this).closest('tr').data('id');
                Api.get('/api/admin.keys.php',{action:'list_all'}).then(r=>{
                    const txt = r.rows.map(k=>`${k.id}: ${k.label||'(без метки)'} [${k.token.slice(0,8)}…]`).join('\n');
                    const keyId = prompt('ID ключа для назначения:\n'+txt);
                    if(!keyId) return;
                    Api.post('/api/admin.users.php?action=assign_key',{user_id:id, api_key_id:parseInt(keyId)}).then(load);
                });
            });
        });
    }
    $('#ps').on('change', function(){st.pageSize=parseInt(this.value); st.page=1; load();});
    $('#q,#role').on('input change', function(){st.page=1; load();});
    bindSort($('#tbl'), st, (s,o)=>{st.sort=s;st.order=o;load();});
    load();
</script>
<?php include __DIR__.'/../footer.php'; ?>
