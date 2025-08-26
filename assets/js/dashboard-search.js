document.addEventListener('DOMContentLoaded', () => {
    const search = document.querySelector('#ufsc-search');
    const results = document.querySelector('#ufsc-results');
    if (search) {
        search.addEventListener('input', async function() {
            const query = this.value;

            if (query.length < 2) {
                if (results) results.innerHTML = '';
                return;
            }

            try {
                const response = await fetch(ufscAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: new URLSearchParams({
                        action: 'ufsc_search_dashboard',
                        query: query,
                        nonce: ufscAjax.nonce
                    })
                });
                const data = await response.json();
                if (data.success) {
                    let html = '<ul>';
                    if (data.data.clubs.length > 0) {
                        html += '<li><strong>Clubs</strong><ul>';
                        data.data.clubs.forEach(club => {
                            html += '<li>🏛️ ' + club.nom + '</li>';
                        });
                        html += '</ul></li>';
                    }
                    if (data.data.licences.length > 0) {
                        html += '<li><strong>Licenciés</strong><ul>';
                        data.data.licences.forEach(licence => {
                            html += '<li>👤 ' + licence.prenom + ' ' + licence.nom + '</li>';
                        });
                        html += '</ul></li>';
                    }
                    html += '</ul>';
                    if (results) results.innerHTML = html;
                } else if (results) {
                    results.innerHTML = '<p>Aucun résultat trouvé.</p>';
                }
            } catch (error) {
                if (results) results.innerHTML = '<p>Erreur AJAX.</p>';
            }
        });
    }
});
