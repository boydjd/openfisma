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
<script language="javascript" src="<?php echo burl(); ?>/javascripts/form.js"></script>
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
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<form name="create" method="post" action="<?php echo burl()?>/panel/account/sub/save" onsubmit="return go(document.create);">
    <tr>
        <td align="right" class="thc" width="200">First Name:</td>
        <td class="tdc">&nbsp;<input type="text" name="user_name_first" size="90" isnull="no" title="first name" 
            datatype="char"><font color="blue">*</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Last Name:</td>
        <td class="tdc">&nbsp;<input type="text" name="user_name_last" size="90" isnull="no" title="last name"
            datatype="char"><font color="blue"> 
*</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Office Phone:</td>
        <td class="tdc">&nbsp;<input type="text" name="user_phone_office" size="20" isnull="no"
             title="office phone" datatype="char"><font color="blue"> *</font> </td>
    </tr>
    <tr>
        <td align="right" class="thc">Mobile Phone:</td>
        <td class="tdc">&nbsp;<input type="text" name="user_phone_mobile" size="20" title="mobile phone"
            datatype="char"></td>
    </tr>
    <tr>
        <td align="right" class="thc">Email:</td>
        <td class="tdc">&nbsp;<input type="text" name="user_email" size="64" isnull="no" title="email"
            datatype="email" isemail="yes"><font color="blue"> *</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Role:</td>
        <td class="tdc">&nbsp;<?php echo $this->formSelect('user_role_id',null,null,$this->roles);?><font color="blue">*</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Title:</td>
        <td class="tdc">&nbsp;<input type="text" name="user_title" size="90" title="title" datatype="char"></td>
    </tr>
    <tr>
        <td align="right" class="thc">Status:</td>
        <td class="tdc">&nbsp;<select name="user_is_active">
            <option value="1" selected>Active</option>
            <option value="0">Suspend</option>
        </select></td>
    </tr>
    <tr>
        <td align="right" class="thc">Username:</td>
        <td class="tdc">&nbsp;<input type="text" name="user_account" size="90" isnull="no" title="username"
            datatype="char"><font color="blue"> *</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Password:</td>
        <td class="tdc">&nbsp;<input type="password" name="user_password" value="" size="30" isnull="no"
            title="Password" datatype="password"></td>
    </tr>
    <tr>
        <td align="right" class="thc">Confirm Password:</td>
        <td class="tdc">&nbsp;<input type="password" id="user_password_confirm" name="user_password_confirm" value="" size="30" isnull="no"
            title="Password" datatype="password">
        <font color="blue">*</font></td>
    </tr>
</table>
<br><br>
<fieldset style="border:1px solid #BEBEBE; padding:3"><legend><b>Systems</b></legend>
<div style="text-align:right"><span style="margin-right:80px;"><input type="button" name="select_all" value="All" />&nbsp;<input type="button" name="select_none" value="None" /></span></div>
<input name="p_checkhead" value="system_" type="hidden">
<input name="p_checktip" value="System" type="hidden">
<table border="0" width="100%">
<?php
    /* Convert the associative array of systems into a linear array */
    $system_array = array();
    foreach ($this->systems as $id => $system) {
        $system_array[] = array('id'=>$id, 'name'=>$system['name']);
    }
    
    /* Now display the system list in 4 columns. This is tricky since tables are
     * laid out left to right but we want to list systems top to bottom.
     * Look at the "create user" page to see this in effect.
     */
    $column_count = 4;
    $system_count = count($this->systems);
    $row_count = ceil($system_count / $column_count);

    for ($current_row = 0; $current_row < $row_count; $current_row++) {
        print "<tr>";
        for ($current_column = 0; $current_column < $column_count; $current_column++) {
            print "<td width=\"25%\">";
            $current_system_index = $current_column * $row_count + $current_row;
            if ($current_system_index < $system_count) {
                $system = $system_array[$current_system_index];
                print "<input type='checkbox' id='sys_{$system['id']}' name='system_{$system['id']}'
                       value='{$system['id']}'>{$system['name']}\n";
            }
            print "&nbsp;</td>";
        }
        print "</tr>\n";
    }
?>
</table>
</fieldset>
<table border="0" width="300">
<tr align="center">
    <td><input type="submit" value="Create" title="submit your request"></td>
    <td><span style="cursor: pointer"><input type="reset" value="Reset"></span></td>
</tr>
</table>
</form>
<br>
