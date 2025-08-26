
(function(){
  function $(sel,ctx){return (ctx||document).querySelector(sel)}
  function $all(sel,ctx){return Array.from((ctx||document).querySelectorAll(sel))}
  const data = JSON.parse($('#ufscx-data')?.textContent||'[]');
  const tbody = $('#ufscx-table tbody');
  const q = $('#ufscx-q'), st=$('#ufscx-status'), cat=$('#ufscx-cat'), qt=$('#ufscx-quota'), pp=$('#ufscx-pp');
  const exportBtn = $('#ufscx-export');
  let sortKey='id', sortDir='desc';

  function fmtDate(s){ if(!s||s=='0000-00-00') return ''; return s; }

  function render(){
    let rows = data.slice();
    const term = (q.value||'').toLowerCase();
    if(term){
      rows = rows.filter(r=>[r.nom,r.prenom,r.email,r.ville].some(v=>(v||'').toLowerCase().includes(term)));
    }
    if(st.value) rows = rows.filter(r=>(r.statut||'').toLowerCase()==st.value.toLowerCase());
    if(cat.value) rows = rows.filter(r=>(r.categorie||'')==cat.value);
    if(qt.value) rows = rows.filter(r=>(r.quota||'')==qt.value);

    rows.sort((a,b)=>{
      const A=(a[sortKey]??'').toString().toLowerCase();
      const B=(b[sortKey]??'').toString().toLowerCase();
      if(A<B) return sortDir==='asc'? -1: 1;
      if(A>B) return sortDir==='asc'? 1: -1;
      return 0;
    });

    const per = parseInt(pp.value||25,10);
    rows = rows.slice(0, per); // simple paginate (first page)
    tbody.innerHTML = rows.map(r=>`
      <tr class="ufscx-row">
        <td>${r.id}</td>
        <td>${r.nom||''}</td>
        <td>${r.prenom||''}</td>
        <td>${r.email||''}</td>
        <td>${r.sexe||''}</td>
        <td>${fmtDate(r.date_naissance)}</td>
        <td>${r.ville||''}</td>
        <td>${r.categorie||''}</td>
        <td>${r.quota||''}</td>
        <td><span class="ufscx-pill">${r.statut||''}</span></td>
        <td>${fmtDate(r.date_licence)}</td>
        <td>
          <button class="ufscx-btn ufscx-btn-soft" data-a="view" data-id="${r.id}">Voir</button>
          <button class="ufscx-btn" data-a="toggleq" data-id="${r.id}">${r.quota==='Oui'?'Retirer du quota':'Inclure au quota'}</button>
        </td>
      </tr>
    `).join('');
  }

  $all('th.sort').forEach(th=>{
    th.addEventListener('click',()=>{
      const k = th.getAttribute('data-k');
      if(sortKey===k) sortDir = (sortDir==='asc'?'desc':'asc'); else {sortKey=k;sortDir='asc';}
      render();
    });
  });

  [q,st,cat,qt,pp].forEach(el=> el && el.addEventListener('input', render));

  tbody.addEventListener('click', function(e){
    const btn = e.target.closest('button[data-a]');
    if(!btn) return;
    e.preventDefault();
    const id = btn.getAttribute('data-id');
    const act = btn.getAttribute('data-a');
    if(!id) return;

    if(act==='view'){
      window.location.href = '?view_licence='+id;
      return;
    }
    if(act==='edit'){
      window.location.href = '?edit_licence='+id;
      return;
    }

    if(act==='cart'){
      window.location.href = '?ufsc_pay_licence='+id;
      return;
    }

    if(act==='toggleq' || act==='delete'){
      btn.disabled = true;
      const params = new URLSearchParams();
      params.append('action', act==='toggleq'?'ufscx_toggle_quota':'ufscx_delete_draft');
      params.append('nonce', UFSCX_AJAX.nonce);
      params.append('id', id);
      fetch(UFSCX_AJAX.ajax, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: params
      }).then(r=>r.json()).then(res=>{
        if(res && res.success){
          if(act==='toggleq'){
            const row = data.find(r=>String(r.id)===String(id));
            if(row){
              row.quota = res.data && res.data.is_included ? 'Oui':'Non';
            }
          } else if(act==='delete'){
            const idx = data.findIndex(r=>String(r.id)===String(id));
            if(idx>-1){ data.splice(idx,1); }
          }
          render();
        } else {
          alert((res && res.data && res.data.message) || 'Erreur');
        }
      }).catch(()=>{
        alert('Erreur');
      }).finally(()=>{
        btn.disabled = false;
      });
    }
  });

  if(exportBtn){
    exportBtn.addEventListener('click',()=>{
      const headers = ['id','nom','prenom','email','sexe','date_naissance','ville','categorie','quota','statut','date_licence'];
      const csv = [headers.join(';')].concat(data.map(r=>headers.map(h=> (r[h]??'').toString().replace(/;/g,',')).join(';'))).join('\n');
      const blob = new Blob([csv],{type:'text/csv;charset=utf-8;'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob); a.download='licences.csv'; a.click();
    });
  }

  render();
})();
