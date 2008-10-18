<?php
    $index = $this->table_lookup[$this->threat_likelihood][$this->impact];
    $cell_colors_rl = cell_background_colors(9, $index);
    $overall_impact = calcImpact($this->threat_likelihood, $this->impact);
?>
<tr>
    <td colspan="2">
        <table class="rafImpact">
            <tr>
                <td colspan='4' align='center'><b>Overall Risk Level Analysis</b></td>
            </tr>
            <tr>
                <td align='center'>&nbsp;</td>
                <td colspan='3' align='center'><b>Security Categorization of Information System</b></td>
            </tr>
            <tr>
                <td width="25%"><b>Threat Likelihood</b></td>
                <td width="25%"><b>LOW</b></td>
                <td width="25%"><b>MODERATE</b></td>
                <td width="25%"><b>HIGH</b></td>
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
    </td>
</tr>
<tr>
    <td colspan="2">
        Based on the threat likelihood and security categorization of the information system, the finding presents a <b><?php echo $overall_impact;?></b> level of risk to agency operations.
    </td>
</tr>

