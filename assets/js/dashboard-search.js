jQuery(document).ready(function($) {
    $('#ufsc-search').on('input', function() {
        const query = $(this).val();

        if (query.length < 2) {
            $('#ufsc-results').html('');
            return;
        }

        $.ajax({
            url: ufscAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'ufsc_search_dashboard',
                query: query,
                nonce: ufscAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    let html = '<ul>';
                    if (response.data.clubs.length > 0) {
                        html += '<li><strong>Clubs</strong><ul>';
                        response.data.clubs.forEach(function(club) {
                            html += '<li>🏛️ ' + club.nom + '</li>';
                        });
                        html += '</ul></li>';
                    }
                    if (response.data.licences.length > 0) {
                        html += '<li><strong>Licenciés</strong><ul>';
                        response.data.licences.forEach(function(licence) {
                            html += '<li>👤 ' + licence.prenom + ' ' + licence.nom + '</li>';
                        });
                        html += '</ul></li>';
                    }
                    html += '</ul>';
                    $('#ufsc-results').html(html);
                } else {
                    $('#ufsc-results').html('<p>Aucun résultat trouvé.</p>');
                }
            },
            error: function() {
                $('#ufsc-results').html('<p>Erreur AJAX.</p>');
            }
        });
    });
});
