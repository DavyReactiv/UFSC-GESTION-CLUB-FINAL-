(function($){'use strict';
var ajaxUrl=(UFSC_PRO&&UFSC_PRO.ajaxUrl)||'/wp-admin/admin-ajax.php';var nonce=(UFSC_PRO&&UFSC_PRO.nonce)||'';
function toast(m){var t=$('<div class="ufsc-toast"></div>').text(m).appendTo('body');setTimeout(function(){t.fadeOut(150,function(){t.remove();});},1300);} 
$('<style>').text('.ufsc-toast{position:fixed;right:16px;top:16px;background:#111;color:#fff;padding:8px 12px;border-radius:10px;z-index:99999;opacity:.95}').appendTo(document.head);
function post(a,d){d=d||{};d.action=a;if(nonce)d._ajax_nonce=nonce;return $.post(ajaxUrl,d);}
$(document).on('click','.ufsc-pay-licence,.ufsc-add-to-cart',function(e){e.preventDefault();var $b=$(this),id=$b.data('licenceId')||$b.data('licence-id');if(!id)return;$b.prop('disabled',true);post('ufsc_add_to_cart',{licence_id:id}).done(function(r){if(r&&r.success&&r.data&&r.data.redirect){window.location.href=r.data.redirect;}else{toast((r&&r.data)||'Erreur');}}).fail(function(){toast('Erreur réseau');}).always(function(){$b.prop('disabled',false);});});
$(document).on('click','.ufsc-delete-draft',function(e){e.preventDefault();var $b=$(this),id=$b.data('licenceId')||$b.data('licence-id');if(!id)return;if(!confirm('Supprimer ce brouillon ?'))return;$b.prop('disabled',true);post('ufsc_delete_licence_draft',{licence_id:id}).done(function(r){if(r&&r.success){location.reload();}else{toast((r&&r.data)||'Erreur');}}).fail(function(){toast('Erreur réseau');}).always(function(){$b.prop('disabled',false);});});

$(document).on('click','.ufsc-include-quota',function(e){
  e.preventDefault();
  var $b=$(this), id=$b.data('licence-id')||$b.data('id'); if(!id) return;
  $b.prop('disabled',true);
  post('ufsc_include_quota',{licence_id:id}).done(function(r){
    if(r&&r.success){ toast('Inclus au quota'); location.reload(); }
    else { alert((r&&r.data)?r.data:'Inclusion impossible'); }
  }).fail(function(){ alert('Erreur réseau'); }).always(function(){ $b.prop('disabled',false); });
});

})(jQuery);