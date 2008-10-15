<table width="70%" class="tbframe" cellpadding="5" style="margin-top:80px;margin-bottom:150px;">
    <th align="left"><b>Error Message</b></th>
    <tr>
        <td>
            <?php echo $this->content; ?>
        </td>
    </tr>
    <tr>
        <td>
            <a href="javascript:history.back()">Please click here to return your last page!</a>
        </td>
    </tr>
    <tr>
        <td>Technical Support: <?php echo Config_Fisma::readSysConfig('contact_name');?>, Phone: <?php echo Config_Fisma::readSysConfig('contact_phone');?>, Email: <a href="mailto:<?php echo Config_Fisma::readSysConfig('contact_email');?>"><?php echo Config_Fisma::readSysConfig('contact_email');?></a>
        </td>
    </tr>
</table>
