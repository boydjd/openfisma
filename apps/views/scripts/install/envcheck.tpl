<table  width="778" align="center" cellspacing="0" cellpadding="0" background="/images/install/bg_table.gif">
        <tr><td colspan=4 align='center'><h4>Setting Check</h4> </td> </tr>
        <tr>
        <td width='5%'>&nbsp;</td>
        <td colspan="3">
                <ul class="nolist">
                    <li class="<?php echo $this->checklist['version'];?>"> 
                    Currently PHP version is <?php echo phpversion(); ?></li>
                </ul>
        </td>
        <td width='5%'>&nbsp;</td>
        </tr>
        <tr>
        <td width='5%'>&nbsp;</td>
        <td width='35%' align='left'>
            <button onclick="javascript:history.back();">Back</button>
        </td>
        <td width='20%' align='center'></td>
        <td width='35%' align='right'><span style='font-size:85%;'>Please click Next to Continue >></span>
            <a class="button" href="<?php echo $this->next ?>" >Next</a>
        </td>
        <td width='5%'>&nbsp;</td>
        </tr>
        <tr>
        <td colspan="5">&nbsp;</td>
        </tr>
</table>
