<script language="javascript">
function delok(entryname)
{
    var str = "Are you sure that you want to delete this network?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
</script>
<div class="barleft">
<div class="barright">
<p><b>Network List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>Network Name</th>
    <th>Nickname</th>
    <th>Description</th>
    <?php if(isAllow('admin_networks','update')){
              echo'<th>Edit</th>';
          } 
          if(isAllow('admin_networks','read')){
              echo'<th>View</th>';
          }
          if(isAllow('admin_networks','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->network_list as $network){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $network['name'];?></td>
    <td class="tdc">&nbsp;<?php echo $network['nickname'];?></td>
    <td class="tdc">&nbsp;<?php echo $network['desc'];?></td>
    <?php if(isAllow('admin_networks','update')){ ?>
    <td class="tdc" align="center">
        <a href="<?php echo burl()?>/panel/network/sub/view/v/edit/id/<?php echo $network['id'];?>" title="edit the Networks">
        <img src="<?php echo burl()?>/images/edit.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_networks','read')){ ?>
    <td class="tdc" align="center">
        <a href="<?php echo burl()?>/panel/network/sub/view/id/<?php echo $network['id'];?>" title="display the Networks">
        <img src="<?php echo burl()?>/images/view.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_networks','delete')){ ?>
    <td class="tdc" align="center">
        <a href="<?php echo burl()?>/panel/network/sub/delete/id/<?php echo $network['id'];?>" title="delete the Networks, then no restore after deleted" onclick="return delok('Networks');">
        <img src="<?php echo burl()?>/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
