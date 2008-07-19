<form method="post" action="<?php echo $this->next;?>">
    <input type='hidden' name='dsn[type]' value="<?php echo $this->dsn['type'];?>" />
    <input type='hidden' name='dsn[host]' value="<?php echo $this->dsn['host'];?>" />
    <input type='hidden' name='dsn[port]' value="<?php echo $this->dsn['port'];?>" />
    <input type='hidden' name='dsn[uname]' value="<?php echo $this->dsn['uname'];?>" />
    <input type='hidden' name='dsn[upass]' value="<?php echo $this->dsn['upass'];?>" />
    <input type='hidden' name='dsn[dbname]' value="<?php echo $this->dsn['dbname'];?>" />
    <input type='hidden' name='dsn[name_c]' value="<?php echo $this->dsn['name_c'];?>" />
    <input type='hidden' name='dsn[pass_c]' value="<?php echo $this->dsn['pass_c'];?>" />
    <input type='hidden' name='dsn[rpath]' value="<?php echo $this->dsn['rpath'];?>" />
<table  width=778 align="center" cellspacing="0" cellpadding="0" background="img/bg_table.gif">
    <tr>
        <td width='5%'>&nbsp;</td>
        <td colspan="3"><h4 style="margin-top: 10px; margin-bottom: 5px; padding: 10px;">General settings</h4>
                <div style="padding: 10px;text-align:center;">
                    <table border='0' cellpadding='0' cellspacing='0' valign='top' width='90%'>
                        <tr>
                            <td class='bg2'><table width='100%' border='0' cellpadding='4' cellspacing='1' style="TABLE-LAYOUT:fixed;word-break:break-all;word-wrap:break-word;">
                                <tr>
                                        <td class='bg3'><b>Database</b></td>
                                        <td class='bg1'><?php echo $this->dsn['type']; ?></td>
                                </tr>
                                <tr>
                                        <td class='bg3'><b>Database Hostname</b></td>
                                        <td class='bg1' style="word-break:break-all;"> <?php echo $this->dsn['host']; ?>
                                </tr>
                                <tr>
                                        <td class='bg3'><b>Database Service Port</b></td>
                                        <td class='bg1' style="word-break:break-all;"> <?php echo $this->dsn['port']; ?>
                                </tr>
                                <tr>
                                        <td class='bg3'><b>Database Username</b></td>
                                        <td class='bg1' style="word-break:break-all;"> <?php echo $this->dsn['uname']; ?>
                                </tr>
                                <tr>
                                        <td class='bg3'><b>Database Password</b></td>
                                        <td class='bg1' style="word-break:break-all;"> ******
                                </tr>
                                <tr>
                                        <td class='bg3'><b>Database Name</b></td>
                                        <td class='bg1' style="word-break:break-all;"> <?php echo $this->dsn['dbname']; ?>
                                </tr>
                                <tr>
                                        <td class='bg3'><b>Create User for new Database</b></td>
                                        <td class='bg1' style="word-break:break-all;"> <?php echo $this->dsn['name_c']; ?>
                                </tr>
                                <tr>
                                        <td class='bg3'><b>Installation physical path</b></td>
                                        <td class='bg1' style="word-break:break-all;"> <?php echo $this->dsn['rpath']; ?></td>
                                </tr>
                            </table></td>
                        </tr>
                    </table>
                </div></td>
        <td width='5%'>&nbsp;</td>
    </tr>
    <tr>
        <td width='5%'>&nbsp;</td>
        <td width='35%' align='left'><input type='button' value='Back' onclick="javascript:history.back();" />
                <span style='font-size:85%;'><< General settings</span> </td>
        <td width='20%' align='center'></td>
        <td width='35%' align='right'><span style='font-size:85%;'>Save Settings >></span>
                <input type='submit' name='submit' value='Next' />
        </td>
        <td width='5%'>&nbsp;</td>
    </tr>
    <tr>
        <td colspan="5">&nbsp;</td>
    </tr>
</table>
</form>
