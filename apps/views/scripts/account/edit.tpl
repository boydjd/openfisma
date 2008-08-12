<script language="javascript">
    $(function(){
        $(":button[name=select_all]").click(function(){
            $(":checkbox").attr( 'checked','checked' );
        });
        $(":button[name=select_none]").click(function(){
            $(":checkbox").attr( 'checked','' );
        });
    })
</script>
<script language="javascript" src="/javascripts/jquery/jquery.validate.js"></script>
<script language="javascript" src="/javascripts/account.validate.js"></script>
<?php   $this->role_list[0] = '';?>
<div class="barleft">
    <div class="barright">
        <p><b>User Account Information</b> 
    </div>
</div>
<table border="0" width="95%" align="center">
    <tr>
        <td align="left"><font color="blue">*</font> = Required Field</td>
    </tr>
</table>
<form id="accountform" name="edit" method="post" action="/panel/account/sub/update/id/<?php echo $this->id;?>">
    <table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
        <tr>
            <td align="right" class="thc" width="200">First Name:</td>
            <td class="tdc">&nbsp;
                <input type="text" name="user[name_first]" 
            value="<?php echo $this->user['firstname'];?>" size="50">
                <font color="blue"> *</font></td>
        </tr>
        <tr>
            <td align="right" class="thc">Last Name:</td>
            <td class="tdc">&nbsp;
                <input type="text" name="user[name_last]" 
            value="<?php echo $this->user['lastname'];?>" size="50">
                <font color="blue"> *</font></td>
        </tr>
        <tr>
            <td align="right" class="thc">Office Phone:</td>
            <td class="tdc">&nbsp;
                <input type="text" name="user[phone_office]"
            value="<?php echo $this->user['officephone'];?>" size="50">
                <font color="blue"> *</font> </td>
        </tr>
        <tr>
            <td align="right" class="thc">Mobile Phone:</td>
            <td class="tdc">&nbsp;
                <input type="text" name="user[phone_mobile]"
            value="<?php echo $this->user['mobilephone'];?>" size="50"></td>
        </tr>
        <tr>
            <td align="right" class="thc">Email:</td>
            <td class="tdc">&nbsp;
                <input type="text" name="user[email]" 
            value="<?php echo $this->user['email'];?>" size="50">
                <font color="blue"> *</font></td>
        </tr>
        <tr>
            <td align="right" class="thc">Role:</td>
            <td class="tdc">&nbsp;
                <?php
             if($this->role_count > 1){
                 echo $this->roles;
             }else{
                 echo $this->formSelect('user_role',nullGet($this->roles,0),null,$this->role_list);
             }
        ?>
                &nbsp;<a href="/panel/account/sub/assignrole/id/<?php echo $this->id;?>">Advanced</a></td>
        </tr>
        <tr>
            <td align="right" class="thc">Title:</td>
            <td class="tdc">&nbsp;
                <input type="text" name="user[title]" 
            value="<?php echo $this->user['title'];?>" size="50"></td>
        </tr>
        <tr>
            <td align="right" class="thc">Status:</td>
            <td class="tdc">&nbsp;
                <select name="user[is_active]">
                    <option value="1" <?php echo 1 == $this->user['status']?'selected':'';?>>Active</option>
                    <option value="0" <?php echo 0 == $this->user['status']?'selected':'';?>>Suspend</option>
                </select></td>
        </tr>
        <?php if ( 'ldap' != readSysConfig('auth_type') ) { ?>
        <tr>
            <td align="right" class="thc">Account:</td>
            <td class="tdc">&nbsp;
                <input type="text" name="user[account]"
            value="<?php echo $this->user['username'];?>" size="50">
                <font color="blue"> *</font></td>
        </tr>
        <tr>
            <td align="right" class="thc">Password:</td>
            <td class="tdc">&nbsp;
                <input type="password" id="user_password" name="user[password]" value="" size="50">
                <font color="blue">*</font></td>
        </tr>
        <tr>
            <td align="right" class="thc">Confirm Password:</td>
            <td class="tdc">&nbsp;
                <input type="password" id="password_confirm" name="password_confirm" value="" size="50">
                <font color="blue">*</font></td>
        </tr>
        <?php
            }
            if('ldap' == readSysConfig('auth_type')){
        ?>
        <tr>
            <td align="right" class="thc">Account Dn:</td>
            <td class="tdc">&nbsp;
                <input type="text" id="user[ldap_dn]" name="user[ldap_dn]" value="<?php echo $this->user['ldap_dn'];?>" size="50" isnull="no"
                    title="AccoutnDn" datatype="char"><font color="blue"> *</font>
                <input type="button" id="checkdn" value="Check Dn">
                <div id="checkResult"></div></td>
        </tr>
        <?php } ?>                    
    </table>
    <br>
    <br>
    <fieldset style="border:1px solid #BEBEBE; padding:3">
    <legend><b>Systems</b></legend>
    <div style="text-align:right"><span style="margin-right:80px;">
        <label for="system[]" class="error">Please select at least one system for
        your account.</label>
        <input type="button" name="select_all" value="All" />
        &nbsp;
        <input type="button" name="select_none" value="None" />
        </span></div>
    <table border="0" width="100%">
        <tr>
            <?php /*
    $row = 4;
    $num = 0;
    foreach($this->all_sys as $sid=>$system ){
        $num++;
        if($num % $row == 0){
            $flag = "</tr><tr>";
        } else {
            $flag = "";
        }
        if(in_array($sid, $this->my_systems)){
            $checked = " checked";
        } else {
            $checked ="";
        }
?>
    <td>
       <input type="checkbox" name="system[]" value="<?php echo $sid;?>" <?php echo $checked;?>>&nbsp;<?php echo $system['name']; ?>
    </td>
<?php echo $flag;
    } */
?>
    </table>
    <table border="0" width="100%">
        <?php
    /* Convert the associative array of systems into a linear array */
    $system_array = array();
    foreach ($this->all_sys as $id => $system) {
        $system_array[] = array('id'=>$id, 'name'=>$system['name']);
    }
//print('<pre>'.print_r($system_array,true).'</pre>');
    /* Now display the system list in 4 columns. This is tricky since tables are
     * laid out left to right but we want to list systems top to bottom.
     * Look at the "create user" page to see this in effect.
     */
    $column_count = 4;
    $system_count = count($this->all_sys);
    $row_count = ceil($system_count / $column_count);

    for ($current_row = 0; $current_row < $row_count; $current_row++) {
        print "<tr>";
        for ($current_column = 0; $current_column < $column_count; $current_column++) {
            print "<td width=\"25%\">";
            $current_system_index = $current_column * $row_count + $current_row;
            if ($current_system_index < $system_count) {
                $system = $system_array[$current_system_index];
                if(in_array($system['id'], $this->my_systems)){
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }
                print "<input type='checkbox' name='system[]' $checked
                       value='{$system['id']}'>{$system['name']}\n";
            }
            print "&nbsp;</td>";
        }
        print "</tr>";
    }
?>
    </table>
    </fieldset>
    <table border="0" width="300">
        <tr align="center">
            <td><input type="submit" value="Update" title="submit your request"></td>
            <td><span style="cursor: pointer">
                <input type="reset" value="Reset" onclick="document.edit.reset();">
                </span></td>
        </tr>
    </table>
</form>
<br>
