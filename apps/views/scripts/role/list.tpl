<script language="javascript">
function delok(entryname)
{
    var str = "Are you sure that you want to delete this " + entryname + "?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
</script>
<div class="barleft">
<div class="barright">
<p><b>Administration: Roles List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>Role Name</th>
    <th>Nickname</th>
    <?php if(isAllow('admin_roles','update')){
              echo'<th>Edit</th>';
          } 
          if(isAllow('admin_roles','read')){
              echo'<th>View</th>';
          }
          if(isAllow('admin_role_functions','read')){
              echo'<th>Right</th>';
          }
          if(isAllow('admin_roles','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->role_list as $role){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $role['name'];?></td>
    <td class="tdc">&nbsp;<?php echo $role['nickname'];?></td>
    <?php if(isAllow('admin_roles','update')){ ?>
    <td class="tdc" align="center">
        <a href="/zfentry.php/panel/role/sub/view/v/edit/id/<?php echo $role['id'];?>" title="edit the Roles">
        <img src="/images/edit.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_roles','read')){ ?>
    <td class="tdc" align="center">
        <a href="/zfentry.php/panel/role/sub/view/id/<?php echo $role['id'];?>" title="display the Roles">
        <img src="/images/view.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_roles','definition')){ ?>
    <td class="tdc" align="center">
        <a href="/zfentry.php/panel/role/sub/right/id/<?php echo $role['id'];?>" title="set Right for this role">
        <img src="/images/signtick.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_roles','delete')){ ?>
    <td class="tdc" align="center">
        <a href="/zfentry.php/panel/role/sub/delete/id/<?php echo $role['id'];?>" title="delete the Roles, then no restore after deleted" onclick="return delok('Roles');">
        <img src="/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
