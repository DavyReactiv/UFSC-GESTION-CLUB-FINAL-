<?php
if (!defined('ABSPATH')) exit;
$stats = isset($stats)?$stats:array();
$gender = $stats['by_gender'] ?? array('M'=>0,'F'=>0);
$type   = $stats['by_type'] ?? array('competition'=>0,'leisure'=>0);
$age    = $stats['by_age_group'] ?? array();
function ufsc_svg_pie($values, $colors, $size=140){
    $sum = max(1, array_sum($values)); $r=($size/2)-8; $cx=$size/2; $cy=$size/2; $circ=2*pi()*$r; $off=0;
    $svg = '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'">';
    $svg .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="transparent" stroke="#f3f4f6" stroke-width="'.($r*2).'" />';
    foreach ($values as $i=>$v){ $len = ($v/$sum)*$circ; $svg.='<circle transform="rotate(-90 '.$cx.' '.$cy.')" cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="transparent" stroke="'.$colors[$i%count($colors)].'" stroke-width="'.($r*2).'" stroke-dasharray="'.$len.' '.$circ.'" stroke-dashoffset="-'.$off.'" />'; $off+=$len; }
    $svg .= '</svg>'; return $svg;
}
?>
<div class="ufsc-stats-charts" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;">
  <div class="ufsc-card"><h4>Licences par sexe</h4>
    <div style="display:flex;gap:14px;align-items:center;">
      <div><?php echo ufsc_svg_pie(array((int)$gender['M'],(int)$gender['F']), array('#1f2667','#e11d48'), 140); ?></div>
      <ul style="list-style:none;margin:0;padding:0;font-size:14px;">
        <li><span style="display:inline-block;width:10px;height:10px;background:#1f2667;margin-right:6px;border-radius:2px;"></span>Masculin: <?php echo (int)$gender['M']; ?></li>
        <li><span style="display:inline-block;width:10px;height:10px;background:#e11d48;margin-right:6px;border-radius:2px;"></span>Féminin: <?php echo (int)$gender['F']; ?></li>
      </ul>
    </div>
  </div>
  <div class="ufsc-card"><h4>Compétition vs Loisir</h4>
    <div style="display:flex;gap:14px;align-items:center;">
      <div><?php echo ufsc_svg_pie(array((int)$type['competition'],(int)$type['leisure']), array('#10b981','#f97316'), 140); ?></div>
      <ul style="list-style:none;margin:0;padding:0;font-size:14px;">
        <li><span style="display:inline-block;width:10px;height:10px;background:#10b981;margin-right:6px;border-radius:2px;"></span>Compétition: <?php echo (int)$type['competition']; ?></li>
        <li><span style="display:inline-block;width:10px;height:10px;background:#f97316;margin-right:6px;border-radius:2px;"></span>Loisir: <?php echo (int)$type['leisure']; ?></li>
      </ul>
    </div>
  </div>
  <div class="ufsc-card"><h4>Licences par âge</h4>
    <div style="display:flex;align-items:flex-end;height:160px;gap:8px;">
      <?php $pal=array('#8b5cf6','#06b6d4','#e11d48','#f97316','#10b981','#64748b'); $i=0; $max=1; foreach($age as $v){ $max=max($max,(int)$v);} foreach($age as $label=>$val){ $h=$max?((int)$val/$max*140):0; echo '<div title="'.esc_attr($label.': '.$val).'" style="width:26px;height:'.(int)$h.'px;background:'.$pal[$i%count($pal)].';border-radius:6px 6px 0 0;"></div>'; $i++; } ?>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
      <?php $i=0; foreach($age as $label=>$val){ $pal2=array('#8b5cf6','#06b6d4','#e11d48','#f97316','#10b981','#64748b'); echo '<span style="font-size:12px;"><span style="display:inline-block;width:10px;height:10px;background:'.$pal2[$i%6].';margin-right:4px;border-radius:2px;"></span>'.esc_html($label).'</span>'; $i++; } ?>
    </div>
  </div>
</div>
