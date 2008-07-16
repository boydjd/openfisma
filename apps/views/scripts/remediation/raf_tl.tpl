<?php

$thread_index = $this->table_lookup[$this->poam['threat_level']]
                                   [$this->poam['cmeasure_effectiveness']]; 
$cell_colors_tl = cell_background_colors(9, $thread_index);

?>
<tr><td colspan=4>
<table class="rafImpact">
 <tr>
  <td colspan='4' align='center'><b>THREAT LIKELIHOOD TABLE</b></td>
 </tr>
 <tr>
  <td align='center'></td>
  <td colspan='3' align='center'><b>COUNTERMEASURE</b></td>
 </tr>
 <tr>
  <td><b>THREAT SOURCE</b></td>
  <td width="70px"><b>LOW</b></td>
  <td width="70px"><b>MODERATE</b></td>
  <td width="70px"><b>HIGH</b></td>
 </tr>
 <tr>
  <td><b>HIGH</b></td>
  <td bgcolor='<?php echo $cell_colors_tl[0];?>' >high</td>
  <td bgcolor='<?php echo $cell_colors_tl[1];?>' >moderate</td>
  <td bgcolor='<?php echo $cell_colors_tl[2];?>' >low</td>
 </tr>
 <tr>
  <td><b>MODERATE</b></td>
  <td bgcolor='<?php echo $cell_colors_tl[3];?>' >moderate</td>
  <td bgcolor='<?php echo $cell_colors_tl[4];?>' >moderate</td>
  <td bgcolor='<?php echo $cell_colors_tl[5];?>' >low</td>
 </tr>
 <tr>
  <td><b>LOW</b></td>
  <td bgcolor='<?php echo $cell_colors_tl[6];?>' >low</td>
  <td bgcolor='<?php echo $cell_colors_tl[7];?>' >low</td>
  <td bgcolor='<?php echo $cell_colors_tl[8];?>' >low</td>
 </tr>
</table>
</td></tr>
   <tr><td><b>Specific Countermeasures:</b></td>
   <td colspan=3><?php echo $this->poam['cmeasure'];?></td></tr>
   <tr><td><b>Countermeasure Effectiveness:</b></td>
   <td colspan=3><?php echo $this->poam['cmeasure_effectiveness'];?></td></tr>
   <tr><td><b>Effectiveness Justification:</b></td>
   <td colspan=3><?php echo $this->poam['cmeasure_justification'];?></td></tr>
   <tr><td><b>Threat Source:</b></td>
   <td colspan=3><?php echo $this->poam['threat_source'];?></td></tr>
   <tr><td><b>Threat Impact:</b></td>
   <td colspan=3><?php echo $this->poam['threat_level'];?></td></tr>
   <tr><td><b>Impact Level Justification:</b></td>
   <td colspan=3><?php echo $this->poam['threat_justification'];?></td></tr>
   <tr><td><b>Overall Threat Likelihood:</b></td>
   <td colspan=3><?php echo $this->threat_likelihood;?></td></tr>


