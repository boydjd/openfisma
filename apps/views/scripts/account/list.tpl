<script language="javascript">
function delok(entryname)
{
    var str = "Are you sure that you want to delete this user?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
</script>
<div class="barleft">
<div class="barright">
<p><b>User Account List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>First Name</th>
    <th>Last Name</th>
    <th>Office Phone</th>
    <th>Mobile Phone</th>
    <th>Email</th>
    <th>Role</th>
    <th>Username</th>
    <?php if(isAllow('admin_users','update')){
              echo'<th>Notification</th>';
          } 
          if(isAllow('admin_users','update')){
              echo'<th>Edit</th>';
          }
          if(isAllow('admin_users','read')){
              echo'<th>View</th>';
          }
          if(isAllow('admin_users','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->userList as $user){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $user['name_first'];?></td>
    <td class="tdc">&nbsp;<?php echo $user['name_last'];?></td>
    <td class="tdc">&nbsp;<?php echo $user['phone_office'];?></td>
    <td class="tdc">&nbsp;<?php echo $user['phone_mobile'];?></td>
    <td class="tdc">&nbsp;<?php echo $user['email'];?></td>
    <td class="tdc">&nbsp;<?php echo $this->roleList[$user['id']];?></td>
    <td class="tdc">&nbsp;<?php echo $user['account'];?></td>
    <?php if(isAllow('admin_users','update')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/account/sub/notificationevent/id/<?php echo $user['id'];?>" title="notification for Users">
        <img src="/images/notification.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_users','update')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/account/sub/view/v/edit/id/<?php echo $user['id'];?>" title="edit the Users">
        <img src="/images/edit.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_users','read')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/account/sub/view/id/<?php echo $user['id'];?>" title="display the Users">
        <img src="/images/view.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_users','delete')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/account/sub/delete/id/<?php echo $user['id'];?>" title="delete the Users, then no restore after deleted" onclick="return delok('Users');">
        <img src="/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
