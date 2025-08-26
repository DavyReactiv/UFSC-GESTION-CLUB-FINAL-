/**
 * UFSC Club Logo Upload - Direct upload (no Media Library)
 * For club managers: only allow uploading a new logo from local disk.
 */
(function($) {
  'use strict';

  $(document).ready(function() {
    $(document).on('click', '.ufsc-upload-logo-btn', function(e) {
      e.preventDefault();
      const $button = $(this);

      let $input = $('#ufsc-logo-file-input');
      if (!$input.length) {
        $input = $('<input type="file" id="ufsc-logo-file-input" accept="image/*" hidden>');
        $('body').append($input);
      }

      $input.off('change').on('change', function() {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) return;

        const maxSizeMB = (window.ufscLogoUpload && ufscLogoUpload.maxSizeMB) ? ufscLogoUpload.maxSizeMB : 2;
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (allowed.indexOf(file.type) === -1) {
          showMessage('Format non supporté. Utilisez JPG, PNG ou WEBP.', 'error');
          $input.val('');
          return;
        }
        if (file.size > maxSizeMB * 1024 * 1024) {
          showMessage('Fichier trop volumineux. Taille max: ' + maxSizeMB + ' Mo.', 'error');
          $input.val('');
          return;
        }

        const fd = new FormData();
        fd.append('action', 'ufsc_set_club_logo');
        fd.append('nonce', ufscLogoUpload.setLogoNonce);
        fd.append('logo_file', file);

        $button.prop('disabled', true).text('Téléversement...');
        $('.ufsc-logo-upload-message').remove();

        $.ajax({
          url: ufscLogoUpload.ajaxUrl,
          method: 'POST',
          data: fd,
          contentType: false,
          processData: false,
          success: function(response) {
            if (response && response.success) {
              updateLogoDisplay(response.data);
              showMessage(response.data.message || 'Logo mis à jour avec succès.', 'success');
            } else {
              showMessage((response && response.data && response.data.message) ? response.data.message : 'Erreur lors de la mise à jour.', 'error');
            }
          },
          error: function() {
            showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
          },
          complete: function() {
            $button.prop('disabled', false).text('Changer le logo');
            $input.val('');
          }
        });
      });

      $input.trigger('click');
    });

    function updateLogoDisplay(data) {
      const $logoContainer = $('.ufsc-club-logo-display');
      if ($logoContainer.length && data.logo_thumbnail) {
        const logoHtml = '<img src="' + data.logo_thumbnail + '" alt="Logo du club" class="ufsc-club-logo">';
        $logoContainer.html(logoHtml);
      }
      if (data.logo_thumbnail) {
        $('.ufsc-club-avatar img').attr('src', data.logo_thumbnail);
      }
      if (data.attachment_id) {
        $('#club_logo_attachment_id').val(data.attachment_id);
      }
    }

    function showMessage(message, type) {
      const messageClass = type === 'success' ? 'ufsc-alert-success' : 'ufsc-alert-error';
      const messageHtml = '<div class="ufsc-logo-upload-message ufsc-alert ' + messageClass + '"><p>' + message + '</p></div>';
      $('.ufsc-logo-upload-message').remove();
      $('.ufsc-upload-logo-btn').closest('.ufsc-form-group, .ufsc-card-body, .ufsc-logo-actions').prepend(messageHtml);
      if (type === 'success') {
        setTimeout(function() {
          $('.ufsc-logo-upload-message.ufsc-alert-success').fadeOut(300, function() { $(this).remove(); });
        }, 2500);
      }
    }
  });
})(jQuery);