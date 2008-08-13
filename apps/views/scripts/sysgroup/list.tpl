<script language="javascript">
function delok(entryname)
{
    var str = "Are you sure that you want to delete this system group?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
</script>
<div class="barleft">
<div class="barright">
<p><b>System Group List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>System Group Name</th>
    <th>System Group Nickname</th>
    <?php if(isAllow('admin_sysgroups','update')){
              echo'<th>Edit</th>';
          } 
          if(isAllow('admin_sysgroups','read')){
              echo'<th>View</th>';
          }
          if(isAllow('admin_sysgroups','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->sysgroup_list as $sysgroup){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $sysgroup['name'];?></td>
    <td class="tdc">&nbsp;<?php echo $sysgroup['nickname'];?></td>
    <?php if(isAllow('admin_sysgroups','update')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/sysgroup/sub/edit/id/<?php echo $sysgroup['id'];?>" title="edit the System Groups">
        <img src="/images/edit.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_sysgroups','read')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/sysgroup/sub/view/id/<?php echo $sysgroup['id'];?>" title="display the System Groups">
        <img src="/images/view.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_sysgroups','delete')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/sysgroup/sub/delete/id/<?php echo $sysgroup['id'];?>" title="delete the System Groups, then no restore after deleted" onclick="return delok('System Groups');">
        <img src="/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
