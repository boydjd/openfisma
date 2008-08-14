<div class="barleft">
<div class="barright">
<p><b>General Policies</b>
</div>
</div>
<form name="config" method="post" action="/config/save">
<table align="center" cellpadding="5" cellspacing="1" class="tipframe">
<?php foreach($this->general_configs as $c) { ?>
    <tr >
        <td class="conf_key" ><?php echo $c['description']; ?></td>
        <td class="conf_value" >
            <input type="text" name="keys[<?php echo $c['key'];?>]" value="<?php echo $c['value'];?>" size="30">
        </td>
    </tr>
<?php  }?>

    <tr>
        <td>
        <input name="button" type="reset" id="button" value="Reset" style="cursor:pointer;">
        <input name="button" type="submit" id="button" value="Submit"  style="cursor:pointer;">
        </td>
    </tr>
</table>
</form>
<div class="barleft">
<div class="barright">
<p><b>Ldap Config</b>&nbsp;<input type="button" id="addLdapServer" name="addLdapServer" value="Toggle">
</div>
</div>
<div id="addLdapServer_dialog" style="display:none">
<form name="addConfig" method="post" action="/config/addldap">
    <table align="center" cellpadding="5" cellspacing="1" class="tipframe">
        <tr>
            <td>Server Name</td>
            <td><input type="text" name="ldap[name]" size="30"></td>
        </tr>

        <tr>
            <td>Server Host</td>
            <td><input type="text" name="ldap[host]" size="30"></td>
        </tr>
        <tr>
            <td>Server Port</td>
            <td><input type="text" name="ldap[port]" size="30"></td>
        </tr>
        <tr>
            <td>Server Username</td>
            <td><input type="text" name="ldap[username]" size="30"></td>
        </tr>
        <tr>
            <td>Server Password</td>
            <td><input type="text" name="ldap[password]" size="30"></td>
        </tr>
        <tr>
            <td>Server BaseDn</td>
            <td><input type="text" name="ldap[baseDn]" size="30"></td>
        </tr>
        <tr>
            <td>Server AccountFilterFormat</td>
            <td><input type="text" name="ldap[accountFilterFormat]" size="30"></td>
        </tr>
        <tr>
            <td>Server AccountDomainName</td>
            <td><input type="text" name="ldap[accountDomainName]" size="30"></td>
        </tr>
        <tr>
            <td>Server AccountDomainNameShort</td>
            <td><input type="text" name="ldap[accountDomainNameShort]" size="30"></td>
        </tr>
        <tr>
            <td>Server AccountCanonicalForm</td>
            <td>
                <select name="ldap[accountCanonicalForm]">
                    <option value="1">1</option>
                    <option vlaue="2">2</option>
                    <option value="3">3</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>Server bindRequiresDn</td>
            <td>
                <select name="ldap[bindRequiresDn]">
                    <option value="1">True</option>
                    <option value="">False</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <input name="button" type="reset" id="button" value="Reset" style="cursor:pointer;">
                <input name="button" type="submit" id="button" value="Submit"  style="cursor:pointer;">
            </td>
        </tr>
    </table>
</form>
</div>

<form name="config" method="post" action="/config/saveldap">
<table align="center" cellpadding="5" cellspacing="1" class="tipframe">
<?php 
    if(!empty($this->ldap_configs)){
        foreach ($this->ldap_configs as $name=>$row) {
            foreach ($row as $c) {
?>
    <tr >
        <td class="conf_key" ><?php echo $c['description']; ?></td>
        <td class="conf_value" >
            <input type="text" name="keys[<?php echo $name;?>][<?php echo $c['key'];?>]" value="<?php echo $c['value'];?>" size="50">
        <?php if('name' == $c['key']){ ?>
        <a class="button" href="/config/deleteldap/group/<?php echo $c['group'];?>">Delete this Server</a>
        <?php } ?>
        </td>
    </tr>
<?php    }
    echo'<tr><td height="20"></td></tr>';
}?>
    <tr>
        <td>
        <input name="button" type="reset" id="button" value="Reset" style="cursor:pointer;">
        <input name="button" type="submit" id="button" value="Submit"  style="cursor:pointer;">
        </td>
    </tr>
<?php } ?>
</table>
</form>
