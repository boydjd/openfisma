<form method="post" action="<?php echo $this->next;?>">
    <input type='hidden' name='dsn[type]' value="<?php echo $this->dsn['type'];?>" />
    <input type='hidden' name='dsn[host]' value="<?php echo $this->dsn['host'];?>" />
    <input type='hidden' name='dsn[port]' value="<?php echo $this->dsn['port'];?>" />
    <input type='hidden' name='dsn[uname]' value="<?php echo $this->dsn['uname'];?>" />
    <input type='hidden' name='dsn[upass]' value="<?php echo $this->dsn['upass'];?>" />
    <input type='hidden' name='dsn[dbname]' value="<?php echo $this->dsn['dbname'];?>" />
    <input type='hidden' name='dsn[name_c]' value="<?php echo $this->dsn['name_c'];?>" />
    <input type='hidden' name='dsn[pass_c]' value="<?php echo $this->dsn['pass_c'];?>" />
    <table width="100%" align="center" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="2"><div class="installer">
                    <h4>General settings</h4>
                    <table border='0' cellpadding='0' cellspacing='0' valign='top' width='100%'>
                        <tr>
                            <td class='bg2'><table width='100%' border='0' cellpadding='4' cellspacing='1' style="TABLE-LAYOUT:fixed;word-break:break-all;word-wrap:break-word;">
                                    <tr>
                                        <td width="50%" class='bg3'><b>Database</b></td>
                                        <td width="50%" class='bg1'><?php echo $this->dsn['type']; ?></td>
                                    </tr>
                                    <tr>
                                        <td class='bg3'><b>Database Hostname</b></td>
                                    <td class='bg1' style="word-break:break-all;"><?php echo $this->dsn['host']; ?>                                    </tr>
                                    <tr>
                                        <td class='bg3'><b>Database Service Port</b></td>
                                    <td class='bg1' style="word-break:break-all;"><?php echo $this->dsn['port']; ?>                                    </tr>
                                    <tr>
                                        <td class='bg3'><b>Database Username</b></td>
                                    <td class='bg1' style="word-break:break-all;"><?php echo $this->dsn['uname']; ?>                                    </tr>
                                    <tr>
                                        <td class='bg3'><b>Database Password</b></td>
                                    <td class='bg1' style="word-break:break-all;"> ******                                    </tr>
                                    <tr>
                                        <td class='bg3'><b>Database Name</b></td>
                                    <td class='bg1' style="word-break:break-all;"><?php echo $this->dsn['dbname']; ?>                                    </tr>
                                    <tr>
                                        <td class='bg3'><b>Create User for new Database</b></td>
                                    <td class='bg1' style="word-break:break-all;"><?php echo $this->dsn['name_c']; ?>                                    </tr>
                            </table></td>
                        </tr>
                    </table>
                </div></td>
        </tr>
        <tr>
            <td width='50%'><div class="back"><a class="button" href="<?php echo $this->back; ?>" >Back</a></div></td>
            <td width='50%'><div class="next"><input class="button" type='submit' name='submit' value='Next' />
                </div></td>
        </tr>
    </table>
</form>
