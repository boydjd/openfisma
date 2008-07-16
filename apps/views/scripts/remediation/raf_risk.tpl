<?php

$index = $this->table_lookup[$this->threat_likelihood][$this->impact];
$cell_colors_rl = cell_background_colors(9, $index);
$overall_impact = calcImpact($this->threat_likelihood, $this->impact);

?>
<tr> <td colspan="4">
<table class="rafImpact">
 <tr>
  <td colspan='4' align='center'><b>RISK LEVEL TABLE</b></td>
 </tr>
 <tr>
  <td align='center'>&nbsp;</td>
  <td colspan='3' align='center'><b>IMPACT</b></td>
 </tr>
 <tr>
  <td><b>LIKELIHOOD</b></td>
  <td><b>LOW</b></td>
  <td><b>MODERATE</b></td>
  <td><b>HIGH</b></td>
 </tr>
 <tr>
  <td><b>HIGH</b></td>
  <td bgcolor='<?php echo $cell_colors_rl[0];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors_rl[1];?>'>moderate</td>
  <td bgcolor='<?php echo $cell_colors_rl[2];?>'>high</td>
 </tr>
 <tr>
  <td><b>MODERATE</b></td>
  <td bgcolor='<?php echo $cell_colors_rl[3];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors_rl[4];?>'>moderate</td>
  <td bgcolor='<?php echo $cell_colors_rl[5];?>'>moderate</td>
 </tr>
 <tr>
  <td><b>LOW</b></td>
  <td bgcolor='<?php echo $cell_colors_rl[6];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors_rl[7];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors_rl[8];?>'>low</td>
 </tr>
</table>
</td></tr>
   <tr><td><b>High:</b></td><td colspan=3>   Strong need for corrective action</td></tr>
   <tr><td><b>Moderate:</b></td><td colspan=3>   Need for corrective action within a reasonable time period</td></tr>
   <tr><td><b>Low:</b></td><td colspan=3>    Authorizing Official may correct or accept the risk</td></tr>
   <tr><td><b>Overall Risk Level:</b></td>
   <td colspan=3><?php echo $overall_impact;?></td></tr>
