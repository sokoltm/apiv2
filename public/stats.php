<?php include 'header.php'; ?>
<div class="card">
    <h1>Статистика</h1>
    <div class="input-row">
        <label>Дата/время с: <input type="datetime-local" id="d-from"></label>
        <label>по: <input type="datetime-local" id="d-to"></label>
        <input type="text" id="s-q" placeholder="Фильтр по названию сайта...">
        <label class="small">Page size: <select id="page-size"><option>10</option><option>20</option><option>50</option></select></label>
    </div>
    <table id="tbl-stats"><thead><tr>
            <th data-sort="name">Сайт</th><th data-sort="ir">Ir</th><th data-sort="clicks">Кол-во кликов</th>
        </tr></thead><tbody></tbody></table>
    <div id="pager"></div>
</div>

<script>
    const st = {page:1, pageSize:10, sort:'clicks', order:'desc'};
    function defaultDates() {
        const now = new Date(); const pad=n=>n.toString().padStart(2,'0');
        const start = new Date(now); start.setHours(0,0,0,0);
        const end = new Date(now); end.setHours(23,59,59,0);
        const iso = d => d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
        $('#d-from').val(iso(start)); $('#d-to').val(iso(end));
    }
    function loadStats() {
        const params = { page: st.page, page_size: st.pageSize, sort: st.sort, order: st.order, q: $('#s-q').val().trim(), from: $('#d-from').val(), to: $('#d-to').val() };
        Api.get('/api/stats.php', params).then(res => {
            const $tb = $('#tbl-stats tbody').empty();
            res.rows.forEach(row => {
                const tr = $(`<tr class="site-stat" data-id="${row.site_id}"><td>${row.name}</td><td>${row.ir}</td><td>${row.clicks}</td></tr>`);
                $tb.append(tr);
                $tb.append(`<tr class="row-details" data-for="${row.site_id}" style="display:none;"><td colspan="3"><div class="details">Загрузка...</div></td></tr>`);
            });
            $('.site-stat').off('click').on('click', function(){
                const id = $(this).data('id'); const $d = $(`tr.row-details[data-for="${id}"]`);
                if ($d.is(':visible')) { $d.hide(); return; } $d.show(); loadPhraseStatsInto($d.find('.details'), id);
            });
            renderPager($('#pager'), res.total, res.page, res.page_size, (p)=>{ st.page=p; loadStats(); });
        });
    }
    function loadPhraseStatsInto($wrap, siteId) {
        $wrap.html(`<div class="input-row"><strong>Фразы сайта #${siteId}</strong>
      <input type="text" class="ph-q" placeholder="Фильтр по фразе...">
      <label class="small">Page size: <select class="ph-ps"><option>10</option><option>20</option><option>50</option></select></label></div>
    <table class="tbl-ph-stats"><thead><tr><th data-sort="text">Фраза</th><th data-sort="clicks">Кол-во кликов</th></tr></thead><tbody></tbody></table><div class="ph-pager"></div>`);
        const st2 = {page:1, pageSize:10, sort:'clicks', order:'desc', q:''};
        function reload(){
            Api.get('/api/stats.php', {action: 'phrases', site_id: siteId, from: $('#d-from').val(), to: $('#d-to').val(), page: st2.page, page_size: st2.pageSize, sort: st2.sort, order: st2.order, q: st2.q}).then(res=>{
                const $tb=$wrap.find('.tbl-ph-stats tbody').empty();
                res.rows.forEach(r => $tb.append(`<tr><td>${r.text}</td><td>${r.clicks}</td></tr>`));
                bindSort($wrap.find('.tbl-ph-stats'), st2, (s,o)=>{ st2.sort=s; st2.order=o; reload(); });
                renderPager($wrap.find('.ph-pager'), res.total, res.page, res.page_size, (p)=>{ st2.page=p; reload(); });
            });
        }
        $wrap.on('input', '.ph-q', function(){ st2.q=$(this).val().trim(); st2.page=1; reload(); });
        $wrap.on('change', '.ph-ps', function(){ st2.pageSize=parseInt($(this).val()); st2.page=1; reload(); });
        reload();
    }
    bindSort($('#tbl-stats'), st, (s,o)=>{ st.sort=s; st.order=o; loadStats(); });
    $('#page-size').on('change', function(){ st.pageSize=parseInt(this.value); st.page=1; loadStats(); });
    $('#s-q, #d-from, #d-to').on('input change', function(){ st.page=1; loadStats(); });
    defaultDates(); loadStats();
</script>
<?php include 'footer.php'; ?>
