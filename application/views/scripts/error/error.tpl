<table width="70%" class="tbframe" cellpadding="5" style="margin-top:80px;margin-bottom:150px;">
    <th align="left"><b>An Error Has Occurred&hellip;</b></th>
<?php
    if (Config_Fisma::debug()) {
?>
    <tr>
        <td>
            <i>Application is currently in debugging mode:</i><br>
            <?php echo $this->content; ?>
        </td>
    </tr>
<?php    
    } else {
?>
<tr>
    <td>
        <p>An unexpected error has occurred. This error has been logged for administrator review.</p>
        <p>You may want to try again in a few minutes. If the problem persists, please contact your administrator.</p>
    </td>
</tr>
<?php    
    }
?>
    <tr>
        <td>Technical Support Contact: <?php echo Config_Fisma::readSysConfig('contact_name');?><br>
            Contact Phone: <?php echo Config_Fisma::readSysConfig('contact_phone');?><br>
            Contact E-mail: <a href="mailto:<?php echo Config_Fisma::readSysConfig('contact_email');?>">
                <?php echo Config_Fisma::readSysConfig('contact_email');?></a>
        </td>
    </tr>
    <tr>
        <td>
            <a href="javascript:history.back()">Click here to return your last page</a>
        </td>
    </tr>
</table>
