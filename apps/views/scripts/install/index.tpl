
<form name="form1" method="post" action="<?php echo $this->next; ?>">
<table  cellspacing="0" cellpadding="0" background="img/bg_table.gif">
    <tr>
    <td width='5%'>&nbsp;</td>
    <td align="center" colspan="3">
        <h4 style="margin-top: 10px; margin-bottom: 5px; padding: 10px;">Welcome to the Install Wizard for OpenFISMA</h4>
        <div style="padding: 40px;text-align:center;">
            <p>
            Choose language to be used for the installation process
          </p>
            <select name='lang'>
            <option value='english'>english</option>
            </select>
        </div></td>
    <td width='5%'>&nbsp;</td>
    </tr>
    <tr>
    <td width='5%'>&nbsp;</td>
    <td width='35%' align='left'></td>
    <td width='20%' align='center'></td>
    <td width='35%' align='right'><span style='font-size:85%;'>OpenFISMA Introduction >></span>
        <input type='hidden' name='op' value='start' />
        <input type='submit' name='submit' value='Next'  />
    </td>
    <td width='5%'>&nbsp;</td>
    </tr>
    <tr>
    <td colspan="5">&nbsp;</td>
    </tr>
</table>
</form>
