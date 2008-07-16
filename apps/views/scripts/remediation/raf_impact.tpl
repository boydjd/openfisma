<?php

$sensitivity = &$this->sensitivity;
$availability = &$this->act_owner['availability'];
$impact_index = $this->table_lookup[$sensitivity][$availability]; 
$cell_colors = cell_background_colors(9, $impact_index);

?>

<tr><td colspan=4>
<table class="rafImpact">
 <tr>
  <td colspan='4' align='center'><b>IMPACT LEVEL TABLE</b></td>
 </tr>
 <tr>
  <td>&nbsp;</td>
  <td colspan='3' align='center'><b>MISSION CRITICALITY</b></td>
 </tr>
 <tr>
  <td><b>DATA SENSITIVITY</b></td>
  <td><b>SUPPORTIVE</b></td>
  <td><b>IMPORTANT</b></td>
  <td><b>CRITICAL</b></td>
 </tr>
 <tr>
  <td><b>HIGH</b></td>
  <td bgcolor='<?php echo $cell_colors[0];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors[1];?>'>moderate</td>
  <td bgcolor='<?php echo $cell_colors[2];?>'>high</td>
 </tr>
 <tr>
  <td><b>MODERATE</b></td>
  <td bgcolor='<?php echo $cell_colors[3];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors[4];?>'>moderate</td>
  <td bgcolor='<?php echo $cell_colors[5];?>'>moderate</td>
 </tr>
 <tr>
  <td><b>LOW</b></td>
  <td bgcolor='<?php echo $cell_colors[6];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors[7];?>'>low</td>
  <td bgcolor='<?php echo $cell_colors[8];?>'>low</td>
 </tr>
</table>
</td>
</tr>

<tr> 
  <td><b>Mission Criticality:</b></td>
  <td colspan=3><?php echo $this->act_owner['criticality'];?></td>
</tr>
<tr> 
  <td><b>Criticality Justification:</b></td>
  <td colspan=3><?php echo $this->act_owner['criticality_justification'];?></td>
</tr>
<tr> <td><b>Data Sensitivity:</b></td>
  <td colspan=3><?php echo $sensitivity;?></td>
</tr>
<tr> <td><b>Sensitivity Justification:</b></td>
  <td colspan=3><?php echo $this->act_owner['sensitivity_justification'];?></td>
</tr>
<tr> <td><b>Overall Impact Level:</b></td>
  <td colspan=3><?php echo $this->impact;?></td>
</tr>
