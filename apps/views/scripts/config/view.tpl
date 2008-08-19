<?php  if (!empty($this->errors)) { ?>
<script language="javascript">
    $(document).ready(function(){
        $("div#addLdapServer_dialog").slideDown("fast");
    });
<?php  if ('openldap' == $this->ldap['serverType']) { ?>
    $(document).ready(function(){
        $("div#addLdapAdvanced_dialog").slideDown("fast");
    });
<?php } ?>
</script>
<?php } ?>
<style type="text/css">
.configform dl {
    padding:10px;
    border:1px #44637A solid;
}
.configform dd {
    line-height:20px;
}
.configform dt {
    float:left;
    width:500px;
    background:#e3e3e3;
    height:20px;
    color:#44637A;
    font-weight:bold;
    line-height:20px;
    vertical-align:middle;
}
.configform span {
    float:left;
    padding-right:10px;
}
.errors {
    color:red;
}
</style>
<div class="barleft">
    <div class="barright">
        <p><b>General Policies</b> 
    </div>
</div>
<div class="block"> <?php echo $this->form['general']->setAttrib('class','configform'); ?> </div>
<div class="barleft">
<div class="barright">
<p><b>Ldap Config</b>&nbsp;<input type="button" id="addLdapServer" name="addLdapServer" value="Toggle">
</div>
</div>
<div id="addLdapServer_dialog" style="display:none" class="block">
<form name="addLdap" method="post" action="/config/addldap" class="configform">
<dl class="zend_form">
    <dt><label for="serverType">Server Type</label></dt>
    <dd><div><input type="radio" name="serverType" value="ad"
        <?php echo 'ad' == $this->ldap['serverType'] || empty($this->ldap['serverType'])?'checked':'';?>> Microsoft AD
        <input type="radio" name="serverType" value="openldap"
        <?php echo 'openldap' == $this->ldap['serverType']?'checked':'';?>> OpenLdap
        </div>
    </dd>
    <dt><label for="host" class="required">Server Host</label></dt>
    <dd><input type="text" name="host" size="30" value="<?php echo $this->ldap['host'];?>">
    <?php  
        if (!empty($this->errors) && array_key_exists('host',$this->errors)) {
            foreach($this->errors['host'] as $error) {
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="accountDomainName" class="required">server AccountDomainName</label></dt>
    <dd><input type="text" name="accountDomainName" size="30"
         value="<?php echo $this->ldap['accountDomainName'];?>">
    <?php
        if (!empty($this->errors) && array_key_exists('accountDomainName',$this->errors)) {
            foreach($this->errors['accountDomainName'] as $error){
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="accountDomainNameShort" class="required">Server AccountDomainNameShort</label></dt>
    <dd><input type="text" name="accountDomainNameShort" size="30" 
        value="<?php echo $this->ldap['accountDomainNameShort'];?>">
    <?php
        if (!empty($this->errors) && array_key_exists('accountDomainNameShort',$this->errors)) {
            foreach($this->errors['accountDomainNameShort'] as $error){
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="useSsl">UseSSL</label></dt>
    <dd><input type="radio" name="useSsl" value="1"
        <?php echo 1 == $this->ldap['useSsl']?'checked':'';?>>True
        <input type="radio" name="useSsl" value=""
        <?php echo empty($this->ldap['useSsl'])?'checked':'';?>>False
        <input type="button" id="addLdapAdvanced" name="addLdapAdvanced" value="Advanced">
    </dd>
    <div id="addLdapAdvanced_dialog" style="display:none">
    <dt><label for="port">Server Port</label></dt>
    <dd><input type="text" name="port" size="30" value="<?php echo $this->ldap['port'];?>">
    <?php
        if (!empty($this->errors) && array_key_exists('port',$this->errors)) {
            foreach($this->errors['port'] as $error){
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="username">Server Username</label></dt>
    <dd><input type="text" name="username" size="30" value="<?php echo $this->ldap['username'];?>">
    <?php
        if (!empty($this->errors) && array_key_exists('username',$this->errors)) {
            foreach($this->errors['username'] as $error){
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="password">Server Password</label></dt>
    <dd><input type="text" name="password" size="30" value="<?php echo $this->ldap['password'];?>">
    <?php
        if (!empty($this->errors) && array_key_exists('password',$this->errors)) {
            foreach($this->errors['password'] as $error){
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="baseDn">Server BaseDn</label></dt>
    <dd><input type="text" name="baseDn" size="30" value="<?php echo $this->ldap['baseDn'];?>">
    <?php
        if (!empty($this->errors) && array_key_exists('baseDn',$this->errors)) {
            foreach($this->errors['baseDn'] as $error){
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="accountFilterFormat">Server AccountFilterFormat</label></dt>
    <dd><input type="text" name="accountFilterFormat" size="30" value="<?php echo $this->ldap['accountFilterFormat'];?>">
    <?php
        if (!empty($this->errors) && array_key_exists('accountFilterFormat',$this->errors)) {
            foreach($this->errors['accountFilterFormat'] as $error){
                echo '<ul class="errors"><li>'.$error.'</li></ul>';
            }
        }
    ?>
    </dd>
    <dt><label for="accountCanonicalForm">Server AccountCanonicalForm</label></dt>
    <dd><select name="accountCanonicalForm">
        <option vlaue="1" <?php echo 1 == $this->ldap['accountCanonicalForm']?'selected':'';?>>1</option>
        <option value="2" <?php echo 2 == $this->ldap['accountCanonicalForm']?'selected':'';?>>2</option>
        <option value="3" <?php echo 3 == $this->ldap['accountCanonicalForm']?'selected':'';?>>3</option>
        <option value="4" <?php echo 4 == $this->ldap['accountCanonicalForm'] || empty($this->ldap['accountCanonicalForm'])?'selected':'';?>>4</option>
        </select>
    </dd>
    </div>
    <span><input name="button" type="reset" id="button" value="Reset" style="cursor:pointer;"></span>
    <dd><input name="button" type="submit" id="button" value="Submit"  style="cursor:pointer;"></dd>
</dl>
</form>
</div>
<div class="block">
<?php 
    if(!empty($this->ldap_configs)){
        echo'<form name="config" method="post" action="/config/saveldap" class="configform">';
        echo'<dl class="zend_form">';
        foreach ($this->ldap_configs as $name=>$row) {
            foreach ($row as $c) {
                echo "<dt>{$c['description']}";
                if ('host' == $c['key']) {
                    echo "&nbsp;&nbsp;&nbsp;<a  href=\"/config/deleteldap/group/{$c['group']}\" class=\"confirm\">Delete</a>";
                }
                echo "</dt><dd>",
                "<input type=\"text\" name=\"keys[{$name}][{$c['key']}]\" value=\"{$c['value']}\" size=\"50\">",
                "</dd>";
            }
            echo'<br><br>';
        }
?>  <span><input name="button" type="reset" id="button" value="Reset" style="cursor:pointer;"></span>
    <dd><input name="button" type="submit" id="button" value="Submit"  style="cursor:pointer;"></dd>
    </dl>
    </form>
<?php } ?>
</div>
