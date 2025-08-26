
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
        <td>${(()=>{
          if(r.statut==='brouillon'){
            return `
              <button class="ufscx-btn ufscx-btn-soft" data-a="edit" data-id="${r.id}">Modifier</button>
              <button class="ufscx-btn ufscx-btn-soft" data-a="delete" data-id="${r.id}">Supprimer</button>
              <button class="ufscx-btn ufscx-btn-primary" data-a="pay" data-id="${r.id}">Envoyer au paiement</button>
              <button class="ufscx-btn" data-a="toggleq" data-id="${r.id}">${r.quota==='Oui'?'Retirer du quota':'Inclure au quota'}</button>
            `;
          }
          if(r.statut==='in_cart'){
            return `<button class="ufscx-btn ufscx-btn-soft" data-a="viewcart" data-id="${r.id}">Voir panier</button>`;
          }
          if(r.statut==='pending_payment'){
            return `<button class="ufscx-btn ufscx-btn-soft" data-a="vieworder" data-id="${r.id}">Voir commande</button>`;
          }
          return `<button class="ufscx-btn ufscx-btn-soft" data-a="view" data-id="${r.id}">Voir</button>`;
        })()}</td>
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
