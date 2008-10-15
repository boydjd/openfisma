<div class="barleft">
<div class="barright">
<p><b>Administration: Roles List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>Role Name</th>
    <th>Nickname</th>
    <?php if(Config_Fisma::isAllow('admin_roles','update')){
              echo'<th>Edit</th>';
          } 
          if(Config_Fisma::isAllow('admin_roles','read')){
              echo'<th>View</th>';
          }
          if(Config_Fisma::isAllow('admin_roles','read')){
              echo'<th>Rights</th>';
          }
          if(Config_Fisma::isAllow('admin_roles','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->role_list as $role){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $role['name'];?></td>
    <td class="tdc">&nbsp;<?php echo $role['nickname'];?></td>
    <?php if(Config_Fisma::isAllow('admin_roles','update')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/role/sub/view/v/edit/id/<?php echo $role['id'];?>" title="edit the Roles">
        <img src="/images/edit.png" border="0"></a>
    </td>
    <?php } if(Config_Fisma::isAllow('admin_roles','read')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/role/sub/view/id/<?php echo $role['id'];?>" title="display the Roles">
        <img src="/images/view.gif" border="0"></a>
    </td>
    <?php } if(Config_Fisma::isAllow('admin_roles','definition')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/role/sub/right/id/<?php echo $role['id'];?>" title="set Right for this role">
        <img src="/images/signtick.gif" border="0"></a>
    </td>
    <?php } if(Config_Fisma::isAllow('admin_roles','delete')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/role/sub/delete/id/<?php echo $role['id'];?>" title="delete the Roles, then no restore after deleted" onclick="return delok('Role');">
        <img src="/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
