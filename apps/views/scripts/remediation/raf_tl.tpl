<?php
    $thread_index = $this->table_lookup[$this->poam['threat_level']]
                                       [$this->poam['cmeasure_effectiveness']]; 
    $cell_colors_tl = cell_background_colors(9, $thread_index);
    $overall_impact = calcImpact($this->poam['threat_level'], $this->poam['cmeasure_effectiveness']);
?>
<tr>
    <td colspan='2'>
        <table class="rafImpact">
            <tr>
                <td colspan='4' align='center'><b> Overall Threat Likehood Table</b></td>
            </tr>
            <tr>
                <td align='center'></td>
                <td colspan='3' align='center'><b>Countermeasure Effectiveness</b></td>
            </tr>
            <tr>
                <td width="25%"><b>THREAT SOURCE</b></td>
                <td width="25%"><b>LOW</b></td>
                <td width="25%"><b>MODERATE</b></td>
                <td width="25%"><b>HIGH</b></td>
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
    </td>
</tr>
<tr>
    <td colspan="2">
        Based on the threat level and countermeasures currently in place, the finding presents a <b><?php echo $overall_impact;?></b> level of risk to the information system.
    </td>
</tr>
