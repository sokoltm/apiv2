<?php include 'header.php'; ?>
<div class="card">
    <h1>Сайты</h1>
    <div class="subtitle">Таблица сайтов с фильтрами, пагинацией, сортировкой и раскрытием фраз.</div>
    <div class="input-row">
        <input type="text" id="f-q" placeholder="Поиск по названию...">
        <input type="text" id="f-ir" placeholder="Поиск по Ir...">
        <input type="text" id="f-tag" placeholder="Фильтр по тегу...">
        <label class="small">Статус:
            <select id="f-active">
                <option value="">Любой</option>
                <option value="1">Активный</option>
                <option value="0">Неактивный</option>
            </select>
        </label>
        <label class="small">Page size:
            <select id="page-size"><option>10</option><option>20</option><option>50</option></select>
        </label>
        <button id="btn-new">+ Добавить сайт</button>
    </div>
    <table id="tbl-sites">
        <thead>
        <tr>
            <th data-sort="name">Название</th>
            <th data-sort="ir">Ir</th>
            <th data-sort="cph">Кликов в час</th>
            <th data-sort="is_active">Статус</th>
            <th data-sort="tag">Тег</th>
            <th data-sort="phrases_count">Кол-во фраз</th>
            <th data-sort="updated_at">Дата изменения</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div id="pager"></div>
</div>

<script>
    const state = {page:1, pageSize:10, sort:'updated_at', order:'desc'};

    function loadSites() {
        const params = {
            page: state.page, page_size: state.pageSize,
            sort: state.sort, order: state.order,
            q: $('#f-q').val().trim(),
            ir: $('#f-ir').val().trim(),
            tag: $('#f-tag').val().trim(),
            active: $('#f-active').val()
        };
        Api.get('/api/sites.php', params).then(res => {
            const $tb = $('#tbl-sites tbody').empty();
            res.rows.forEach(row => {
                const active = row.is_active == 1 ? '<span class="badge">активный</span>' : '<span class="badge">выкл</span>';
                const tr = $(`<tr class="site-row" data-id="${row.id}">
          <td>${row.name}</td><td>${row.ir}</td><td>${row.clicks_per_hour}</td><td>${active}</td>
          <td>${row.tag ?? ''}</td><td>${row.phrases_count}</td><td>${row.updated_at}</td>
          <td class="actions"><button class="edit">Изменить</button><button class="dup">Дублировать</button><button class="del">Удалить</button></td>
        </tr>`);
                $tb.append(tr);
                $tb.append(`<tr class="row-details" data-for="${row.id}" style="display:none;"><td colspan="8"><div class="details-wrap">Загрузка...</div></td></tr>`);
            });
            $('.site-row').off('click').on('click', function(e){
                if ($(e.target).closest('.actions').length) return;
                const id = $(this).data('id');
                const $details = $(`tr.row-details[data-for="${id}"]`);
                if ($details.is(':visible')) { $details.hide(); return; }
                $details.show(); loadPhrasesInto($details.find('.details-wrap'), id);
            });
            $('.actions .del').on('click', function(e){
                e.stopPropagation();
                if (!confirm('Удалить сайт и его фразы?')) return;
                const id = $(this).closest('tr').prev('.site-row').data('id') || $(this).closest('tr').data('id');
                Api.post('/api/sites.php?action=delete', {id}).then(loadSites);
            });
            $('.actions .dup').on('click', function(e){
                e.stopPropagation();
                const id = $(this).closest('tr').prev('.site-row').data('id') || $(this).closest('tr').data('id');
                Api.post('/api/sites.php?action=duplicate', {id, with_phrases:true}).then(loadSites);
            });
            $('.actions .edit').on('click', function(e){
                e.stopPropagation();
                const id = $(this).closest('tr').prev('.site-row').data('id') || $(this).closest('tr').data('id');
                const row = res.rows.find(x=>x.id==id);
                const name = prompt('Название сайта', row.name); if (name===null) return;
                const ir = prompt('Ir', row.ir); const tag = prompt('Тег', row.tag ?? '');
                const is_active = confirm('Сделать активным? OK – да, Отмена – нет') ? 1 : 0;
                Api.post('/api/sites.php?action=update', {id, name, ir: parseInt(ir||0), tag, is_active}).then(loadSites);
            });
            renderPager($('#pager'), res.total, res.page, res.page_size, (p)=>{ state.page=p; loadSites(); });
        });
    }

    function loadPhrasesInto($wrap, siteId) {
        $wrap.html(`<div class="input-row"><strong>Фразы сайта #${siteId}</strong>
      <input type="text" class="ph-q" placeholder="Поиск по фразе...">
      <input type="text" class="ph-tag" placeholder="Тег...">
      <label class="small">Page size: <select class="ph-page-size"><option>10</option><option>20</option><option>50</option></select></label>
      <button class="ph-new">+ Добавить фразу</button></div>
    <table class="tbl-phrases"><thead><tr>
      <th data-sort="text">Фраза</th><th data-sort="freq">Частота</th><th data-sort="tag">Тег</th><th data-sort="updated_at">Дата изменения</th><th>Действия</th>
    </tr></thead><tbody></tbody></table><div class="ph-pager"></div>`);

        const st = {page:1, pageSize:10, sort:'updated_at', order:'desc'};

        function reload() {
            const params = { site_id: siteId, page: st.page, page_size: st.pageSize, sort: st.sort, order: st.order, q: $wrap.find('.ph-q').val().trim(), tag: $wrap.find('.ph-tag').val().trim() };
            Api.get('/api/phrases.php', params).then(res => {
                const $tb = $wrap.find('.tbl-phrases tbody').empty();
                res.rows.forEach(row => {
                    $tb.append(`<tr data-id="${row.id}"><td>${row.text}</td><td>${row.freq}</td><td>${row.tag??''}</td><td>${row.updated_at}</td>
            <td class="actions"><button class="edit">Ред.</button><button class="dup">Дубль</button><button class="del">Удалить</button></td></tr>`);
                });
                bindSort($wrap.find('.tbl-phrases'), st, (s,o)=>{ st.sort=s; st.order=o; reload(); });
                renderPager($wrap.find('.ph-pager'), res.total, res.page, res.page_size, (p)=>{ st.page=p; reload(); });
                $wrap.find('.actions .del').off().on('click', function(){ if(!confirm('Удалить фразу?')) return;
                    const id = $(this).closest('tr').data('id'); Api.post('/api/phrases.php?action=delete', {id}).then(reload); });
                $wrap.find('.actions .dup').off().on('click', function(){ const id = $(this).closest('tr').data('id'); Api.post('/api/phrases.php?action=duplicate', {id}).then(reload); });
                $wrap.find('.actions .edit').off().on('click', function(){ const id = $(this).closest('tr').data('id'); const row=res.rows.find(x=>x.id==id);
                    const text = prompt('Фраза', row.text); if(text===null) return;
                    const freq = parseInt(prompt('Частота', row.freq)||0); const tag = prompt('Тег', row.tag??'');
                    Api.post('/api/phrases.php?action=update', {id, text, freq, tag}).then(reload); });
            });
        }
        $wrap.on('change', '.ph-page-size', function(){ st.pageSize=parseInt($(this).val()); st.page=1; reload(); });
        $wrap.on('input', '.ph-q, .ph-tag', function(){ st.page=1; reload(); });
        $wrap.find('.ph-new').on('click', function(){ const text = prompt('Новая фраза'); if(!text) return;
            const freq = parseInt(prompt('Частота', '0')||0); const tag = prompt('Тег', '')||'';
            Api.post('/api/phrases.php?action=create', {site_id: siteId, text, freq, tag}).then(reload); });
        bindSort($wrap.find('.tbl-phrases'), st, (s,o)=>{ st.sort=s; st.order=o; reload(); });
        reload();
    }

    $('#page-size').on('change', function(){ state.pageSize=parseInt(this.value); state.page=1; loadSites(); });
    $('#f-q, #f-ir, #f-tag, #f-active').on('input change', function(){ state.page=1; loadSites(); });
    $('#btn-new').on('click', function(){ const name = prompt('Название сайта'); if(!name) return;
        const ir = parseInt(prompt('Ir', '213')||0); const tag = prompt('Тег', '')||'';
        const is_active = confirm('Активировать? OK – да, Отмена – нет') ? 1 : 0;
        Api.post('/api/sites.php?action=create', {name, ir, tag, is_active}).then(loadSites); });
    bindSort($('#tbl-sites'), state, (s,o)=>{ state.sort=s; state.order=o; loadSites(); });
    loadSites();
</script>
<?php include 'footer.php'; ?>
