
jQuery(function($){
  // Add titles for truncated cells
  $('.ufsc-table td, .ufsc-table th').each(function(){
    var $c = $(this);
    if (!$c.attr('title')) { $c.attr('title', $.trim($c.text())); }
  });
  var $tn = $('.wrap .tablenav.top');
  if ($tn.length) {
    $tn.css({ position: 'sticky', top: '32px', zIndex: 10, background: '#fff' });
  }
});
