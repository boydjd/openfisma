<div class="barleft">
<div class="barright">
<p><b>System List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>System Name</th>
    <th>Acronym</th>
    <th>Organization</th>
    <th>Confidentiality</th>
    <th>Integrity</th>
    <th>Availability</th>
    <th>Type</th>
    <?php if(isAllow('admin_systems','update')){
              echo'<th>Edit</th>';
          } 
          if(isAllow('admin_systems','read')){
              echo'<th>View</th>';
          }
          if(isAllow('admin_systems','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->system_list as $system){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $system['name'];?></td>
    <td class="tdc">&nbsp;<?php echo $system['nickname'];?></td>
    <td class="tdc">&nbsp;<?php echo $system['primary_office'];?></td>
    <td class="tdc">&nbsp;<?php echo $system['confidentiality'];?></td>
    <td class="tdc">&nbsp;<?php echo $system['integrity'];?></td>
    <td class="tdc">&nbsp;<?php echo $system['availability'];?></td>
    <td class="tdc">&nbsp;<?php echo $system['type'];?></td>
    <?php if(isAllow('admin_systems','update')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/system/sub/view/v/edit/id/<?php echo $system['id'];?>" title="edit the Systems">
        <img src="/images/edit.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_systems','read')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/system/sub/view/id/<?php echo $system['id'];?>" title="display the Systems">
        <img src="/images/view.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_systems','delete')){ ?>
    <td class="tdc" align="center">
        <a href="/panel/system/sub/delete/id/<?php echo $system['id'];?>" title="delete the Systems, then no restore after deleted" onclick="return delok('System');">
        <img src="/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
