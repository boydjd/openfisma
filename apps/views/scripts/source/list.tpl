<script language="javascript">
function delok(entryname)
{
    var str = "Are you sure that you want to delete this finding source?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
</script>
<div class="barleft">
<div class="barright">
<p><b>Finding Source List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>Source Name</th>
    <th>Nickname</th>
    <th>Description</th>
    <?php if(isAllow('admin_sources','update')){
              echo'<th>Edit</th>';
          } 
          if(isAllow('admin_sources','read')){
              echo'<th>View</th>';
          }
          if(isAllow('admin_sources','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->source_list as $source){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $source['name'];?></td>
    <td class="tdc">&nbsp;<?php echo $source['nickname'];?></td>
    <td class="tdc">&nbsp;<?php echo $source['desc'];?></td>
    <?php if(isAllow('admin_sources','update')){ ?>
    <td class="tdc" align="center">
        <a href="/zfentry.php/panel/source/sub/view/v/edit/id/<?php echo $source['id'];?>" title="edit the Sources">
        <img src="/images/edit.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_sources','read')){ ?>
    <td class="tdc" align="center">
        <a href="/zfentry.php/panel/source/sub/view/id/<?php echo $source['id'];?>" title="display the Sources">
        <img src="/images/view.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_sources','delete')){ ?>
    <td class="tdc" align="center">
        <a href="/zfentry.php/panel/source/sub/delete/id/<?php echo $source['id'];?>" title="delete the Sources, then no restore after deleted" onclick="return delok('Sources');">
        <img src="/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
