<table  width="778" align="center" cellspacing="0" cellpadding="0" background="img/bg_table.gif">
  <tr>
    <td width='5%'>&nbsp;</td>
    <td colspan="3"><h4 style="margin-top: 10px; margin-bottom: 5px; padding: 10px;">General settings</h4><div style="padding: 10px;text-align:center;"><input type='hidden' name='is_second' value='true' /><table width='100%' class='outer' cellspacing='5'>
                <tr>
                    <th colspan='2'><h4 style='color:green;'></h4></th>
                </tr>
                <tr valign='top' align='left'>
                    <td class='head'>
                        <b>Database</b><br />

                        <span style='font-size:85%;'>Choose the database to be used</span>
                    </td>
                    <td class='even'>
                        <select  size='1' name='dsn[type]' id='database'><option value='mysql' selected='selected''>mysql</option></select>
                    </td>
                </tr>
                <tr valign='top' align='left'>
                    <td class='head'>

                        <b>Database Hostname</b><br />
                        <span style='font-size:85%;'>Hosting database</span>
                    </td>
                    <td class='even'>
                        <input type='text' name='dsn[host]' id='host' size='30' maxlength='100' value='localhost' />
                    </td>
                </tr>
                <tr valign='top' align='left'>

                    <td class='head'>
                        <b>Database Service Port</b><br />
                        <span style='font-size:85%;'>Hostname of the database server. If you are unsure, 'localhost' works in most cases.</span>
                    </td>
                    <td class='even'>
                        <input type='text' name='dsn[port]' id='port' size='30' maxlength='100' value='3306' />
                    </td>
                </tr>

                <tr valign='top' align='left'>
                    <td class='head'>
                        <b>Database Name</b><br />
                        <span style='font-size:85%;'>Your database user account on the host</span>
                    </td>
                    <td class='even'>
                        <input type='text' name='dsn[dbname]' id='dbname' size='30' maxlength='100' value='legacy_fisma_cut' />
                    </td>

                </tr>
                <tr valign='top' align='left'>
                    <td class='head'>
                        <b>Database Username</b><br />
                        <span style='font-size:85%;'>Password for your database user account</span>
                    </td>
                    <td class='even'>
                        <input type='text' name='dsn[uname]' id='uname' size='30' maxlength='100' value='fisma' />

                    </td>
                </tr>
                <tr valign='top' align='left'>
                    <td class='head'>
                        <b>Database Password</b><br />
                        <span style='font-size:85%;'>The name of database on the host. The installer will attempt to create the database if not exist</span>
                    </td>
                    <td class='even'>

                        <input type='password' name='dsn[upass]' id='upass' size='30' maxlength='100'/>
                    </td>
                </tr>
                <tr valign='top' align='left'>
                    <td class='head'>
                        <b>Installation physical path</b><br />
                        <span style='font-size:85%;'></span>
                    </td>

                    <td class='even'>
                        <input type='text' name='dsn[rpath]' id='rpath' size='30' maxlength='100' value='/opt/reyo/openfisma/fismaold' />
                    </td>
                </tr>
                </table></div></td>
    <td width='5%'>&nbsp;</td>
  </tr>
  <tr>
    <td width='5%'>&nbsp;</td>

    <td width='35%' align='left'></td>
    <td width='20%' align='center'></td>
    <td width='35%' align='right'><span style='font-size:85%;'>Connection Test Configuration >></span><input type='hidden' name='op' value='dbconfirm2' />
<input type='submit' name='submit' value='Next' />
</td>
    <td width='5%'>&nbsp;</td>
  </tr>
  <tr>
    <td colspan="5">&nbsp;</td>

  </tr>
</table>
