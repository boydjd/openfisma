<?php $this->roleList[0] = ''; ?>
<div class="barleft">
<div class="barright">
<p><b>User Account Information</b>
</div>
</div>
<table width="98%" align="center" >
    <tr>
        <td align="right" class="thc" width="200">First Name:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['name_first'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Last Name:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['name_last'];?></td></tr>
    <tr>
        <td align="right" class="thc">Office Phone:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['phone_office'];?></td></tr>
    <tr>
        <td align="right" class="thc">Mobile Phone:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['phone_mobile'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Email:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['email'];?></td></tr>
    <tr>
        <td align="right" class="thc">Role:</td>
        <td class="tdc">&nbsp;<?php
             if($this->roleCount > 1){
                echo $this->roles;
             }else{
                 echo $this->roleList[nullGet($this->roles,0)];
             }
        ?>        </td>
    </tr>
    <tr>
        <td align="right" class="thc">Title:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['title'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Status:</td>
        <td class="tdc">&nbsp;<?php echo 1 == $this->user['is_active']?'Active':'Suspend';?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Username:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['account'];?></td>
    </tr>
    <?php
        if('ldap' == readSysConfig('auth_type')){
    ?>
    <tr>
        <td align="right" class="thc">Account Dn:</td>
        <td class="tdc">&nbsp;<?php echo $this->user['ldap_dn'];?></td>
    </tr>
    <?php } ?>                    
</table>
<br><br>
<fieldset style="border:1px solid #BEBEBE; padding:3"><legend><b>Systems</b></legend>
<table border="0" width="100%">
<?php
    /* Convert the associative array of systems into a linear array */
    $system_array = array();
    foreach ($this->mySystems as $id) {
        $system_array[] = $id;
    }

    /* Now display the system list in 4 columns. This is tricky since tables are
     * laid out left to right but we want to list systems top to bottom.
     * Look at the "create user" page to see this in effect.
     */
    $column_count = 4;
    $system_count = count($this->mySystems);
    $row_count = ceil($system_count / $column_count);

    for ($current_row = 0; $current_row < $row_count; $current_row++) {
        print "<tr>";
        for ($current_column = 0; $current_column < $column_count; $current_column++) {
            print "<td width=\"25%\">";
            $current_system_index = $current_column * $row_count + $current_row;
            if ($current_system_index < $system_count) {
                $sid = $system_array[$current_system_index];
                print "{$this->allSystems[$sid]['name']}\n";
            }
            print "&nbsp;</td>";
        }
        print "</tr>";
    }
?>
</table>
</fieldset>
