
<form method="post" action="<?php echo $this->next;?>">
    <table align="center" cellspacing="0" cellpadding="0" background="img/bg_table.gif">
        <tr>
            <td colspan="2"><div class="installer">
                    <h4>General settings</h4>
                    <table cellspacing='5' >
                        <tr valign='top' align='left'>
                            <td class='head'><b>Database Product</b></td>
                            <td class='even'><select  size='1' name='dsn[type]' id='database'>
                                    <option value='mysql' selected='selected'>mysql</option>
                                </select>
                            </td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b> Hostname</b><br />
                                <span style='font-size:85%;'>Hosting database</span> </td>
                            <td class='even'><input type='text' name='dsn[host]' id='host' size='30' maxlength='100' value="<?php echo $this->dsn['host']?>" />
                            <?php if(isset($this->message['host'])) {echo " <span class='notice'>*</span>";}?></td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b> Service Port</b></td>
                            <td class='even'><input type='text' name='dsn[port]' id='port' size='30' maxlength='100' value="<?php echo $this->dsn['port']?>" /> <?php if(isset($this->message['port'])) {echo " <span class='notice'>*</span>";}?></td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b>Database Username</b><br />
                                <span style='font-size:85%;'>Your database user account
                                on the host</span> </td>
                            <td class='even'><input type='text' name='dsn[uname]' id='uname' size='30' maxlength='100' value="<?php echo $this->dsn['uname']?>" />
                            <?php if(isset($this->message['uname'])) {echo " <span class='notice'>*</span>";}?></td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b>Database Password</b><br />
                                <span style='font-size:85%;'>Password for the account
                                above</span> </td>
                            <td class='even'><input type='password' name='dsn[upass]' id='upass' size='30' maxlength='100'  value="<?php echo $this->dsn['upass']?>"/>
                            <?php if(isset($this->message['upass'])) {echo " <span class='notice'>*</span>";}?></td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b>Database Name</b><br />
                                <span style='font-size:85%;'> The installer will
                                attempt to create the database if it does not exist</span> </td>
                            <td class='even'><input type='text' name='dsn[dbname]' id='dbname' size='30' maxlength='100'  value="<?php echo $this->dsn['dbname']?>" />
                                <?php if(isset($this->message['dbname'])) {echo " <span class='notice'>*</span>";}?>                            </td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b>New Username to be created for the
                                    database[optional]</b><br />
                                <span style='font-size:85%;'>A lower right account
                                that used in daily connection.</span> </td>
                            <td class='even'><input type='text' name='dsn[name_c]' id='name_c' size='30' maxlength='100'  value="<?php echo $this->dsn['name_c']?>" />
                                <?php if(isset($this->message['name_c'])) {echo " <span class='notice'>*</span>";}?>                            </td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b>Password</b><br />
                                <span style='font-size:85%;'></span> </td>
                            <td class='even'><input type='password' name='dsn[pass_c]' id='pass_c' size='30' maxlength='100'  value="<?php echo $this->dsn['pass_c']?>"/>
                                <?php if(isset($this->message['host'])) {echo " <span class='notice'>*</span>";}?>                            </td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b>Confirm password</b><br />
                                <span style='font-size:85%;'></span> </td>
                            <td class='even'><input type='password' name='dsn[pass_c_ag]' id='pass_c_ag' size='30' maxlength='100' value="<?php echo $this->dsn['pass_c_ag']?>"/>
                                <?php if(isset($this->message['host'])) {echo " <span class='notice'>*</span>";}?>                            </td>
                        </tr>
                        <tr valign='top' align='left'>
                            <td class='head'><b>Installation physical path</b><br />
                                <span style='font-size:85%;'></span> </td>
                            <td class='even'><input type='text' name='dsn[rpath]' id='rpath' size='30' maxlength='100' value="<?php echo ROOT; ?>" />
                         </td>
                        </tr>
                    </table>
                </div>
        </tr>
        <tr>
            <td width='50%'><div class="back"><a class="button" href="<?php echo $this->back; ?>" >Back</a></div></td>
            <td width="50%"><div class="next">
                    <input class="button" type='submit' name='submit' value='Next' />
                </div></td>
        </tr>
    </table>
</form>
