(function(factory){
  if (typeof window.jQuery === 'undefined') { console.error('UFSC front: jQuery not found'); return; }
  factory(window.jQuery);
})(function($){
  'use strict';
  // Ensure legacy global ajaxurl for older scripts
  try {
    if (typeof window.ajaxurl === 'undefined') {
      var loc = (window.UFSC && UFSC.ajaxUrl) || (window.ufsc_ajax && ufsc_ajax.url) || '/wp-admin/admin-ajax.php';
      window.ajaxurl = loc;
    }
  } catch(e){}

  var ajaxUrl = (window.UFSC && UFSC.ajaxUrl) || (window.ufsc_ajax && ufsc_ajax.url) || '/wp-admin/admin-ajax.php';

  function lock($btn, text){
    if(!$btn.length) return;
    $btn.data('orig', $btn.html());
    $btn.prop('disabled', true).html(text || $btn.data('orig'));
  }
  function unlock($btn){
    if(!$btn.length) return;
    $btn.prop('disabled', false).html($btn.data('orig'));
  }

  // Save draft via AJAX (button with id or class)
  $(document).on('click', '#ufsc-save-draft, .ufsc-btn-save-draft', function(e){
    e.preventDefault();
    var $btn = $(this);
    var $form = $btn.closest('form');
    if(!$form.length){ $form = $('#ufsc-licence-form, form.ufsc-form').first(); }
    if(!$form.length) return;

    var payload = $form.serializeArray();
    payload.push({name:'action', value:'ufsc_save_licence_draft'});

    lock($btn, (window.UFSC && UFSC.i18n && UFSC.i18n.saving) || 'Enregistrement…');

    $.post(ajaxUrl, payload).done(function(res){
      if(res && res.success){
        alert((UFSC && UFSC.i18n && UFSC.i18n.saved) || 'Brouillon enregistré.');
        if(res.data && res.data.licence_id){
          if(!$form.find('input[name=\"licence_id\"]').length){
            $('<input>', {type:'hidden', name:'licence_id', value:res.data.licence_id}).appendTo($form);
          } else {
            $form.find('input[name=\"licence_id\"]').val(res.data.licence_id);
          }
        }
      } else {
        alert((res && res.data && res.data.message) || (UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur');
      }
    }).fail(function(){
      alert((UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur');
    }).always(function(){
      unlock($btn);
    });
  });

  // Intercept form submit to add to cart via AJAX
  $(document).on('submit', '#ufsc-licence-form, form#ufsc-licence-form, form.ufsc-licence-form', function(e){
    var submitter = e.originalEvent && e.originalEvent.submitter;
    if(submitter && (submitter.id === 'ufsc-save-draft' || $(submitter).hasClass('ufsc-btn-save-draft') || submitter.name === 'ufsc_save_draft')){
      return;
    }
    if($(this).find('input[name="ufsc_save_draft"]').length){
      return;
    }
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('button[type=\"submit\"], .ufsc-btn-add-to-cart').first();
    lock($btn, 'Ajout…');

    var payload = $form.serializeArray();
    payload.push({name:'action', value:'ufsc_add_licence_to_cart'});
    if (window.UFSC && UFSC.nonces && UFSC.nonces.add_licence_to_cart) {
      payload.push({name:'_ufsc_licence_nonce', value:UFSC.nonces.add_licence_to_cart});
    }

    $.post(ajaxUrl, payload).done(function(res){
      if(res && res.success){
        var url = (res.data && (res.data.cart_url || res.data.redirect)) || (window.wc_cart_url) || window.location.href;
        alert((UFSC && UFSC.i18n && UFSC.i18n.added) || 'Ajouté au panier.');
        if(url){ window.location.href = url; }
      } else {
        alert((res && res.data && res.data.message) || (UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur');
      }
    }).fail(function(){
      alert((UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur');
    }).always(function(){
      unlock($btn);
    });
  });

    // Add an existing licence to cart
    $(document).on('click', '.ufsc-add-to-cart', function(e){
      e.preventDefault();
      var $b = $(this);
      var id = $b.data('licenceId') || $b.data('licence-id');
      if(!id) return;
      lock($b, 'Ajout…');
      $.post(ajaxUrl, {
        action: 'ufsc_add_to_cart',
        licence_id: id,
        nonce: $b.data('nonce') || (UFSC && UFSC.nonces && UFSC.nonces.add_to_cart) || ''
      }).done(function(res){
        if(res && res.success){
          var url = (res.data && res.data.redirect) || (window.wc_cart_url) || window.location.href;
          if(url){ window.location.href = url; }
        } else {
          alert((res && res.data && res.data.message) || (UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur');
        }
      }).fail(function(){
        alert((UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur');
      }).always(function(){
        unlock($b);
      });
    });

    // Delete draft from licences table
    $(document).on('click', '.ufsc-delete-draft', function(e){
      e.preventDefault();
      var id = $(this).data('id') || $(this).data('licence-id');
      if(!id) return;
      if(!confirm('Supprimer ce brouillon ?')) return;
      $.post(ajaxUrl, {
        action:'ufsc_delete_licence_draft',
        licence_id:id,
        nonce:(UFSC && UFSC.nonces && UFSC.nonces.delete_draft) || ''
      }).done(function(res){
        if(res && res.success){ location.reload(); }
        else { alert((res && res.data && res.data.message) || 'Suppression impossible.'); }
      }).fail(function(){ alert('Erreur de suppression.'); });
    });

    // Include licence in quota
    $(document).on('click', '.ufsc-include-quota', function(e){
      e.preventDefault();
      var $b = $(this);
      var id = $b.data('licenceId') || $b.data('licence-id');
      if(!id) return;
      lock($b, 'Inclusion…');
      $.post(ajaxUrl, {
        action:'ufsc_include_quota',
        licence_id:id,
        nonce:(UFSC && UFSC.nonces && UFSC.nonces.include_quota) || ''
      }).done(function(res){
        if(res && res.success){ location.reload(); }
        else { alert((res && res.data && res.data.message) || (UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur'); }
      }).fail(function(){
        alert((UFSC && UFSC.i18n && UFSC.i18n.error) || 'Erreur');
      }).always(function(){
        unlock($b);
      });
    });

  })(jQuery);

