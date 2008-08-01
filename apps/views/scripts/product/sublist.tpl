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
<p><b>Administration: Products List</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<tr align="center">
    <th>Product Name</th>
    <th>Vendor</th>
    <th>Version</th>
    <?php if(isAllow('admin_products','update')){
              echo'<th>Edit</th>';
          } 
          if(isAllow('admin_products','read')){
              echo'<th>View</th>';
          }
          if(isAllow('admin_products','delete')){
              echo'<th>Del</th>';
          }
    ?>
</tr>
<?php foreach($this->product_list as $product){ ?>
<tr>
    <td class="tdc">&nbsp;<?php echo $product['name'];?></td>
    <td class="tdc">&nbsp;<?php echo $product['vendor'];?></td>
    <td class="tdc">&nbsp;<?php echo $product['version'];?></td>
    <?php if(isAllow('admin_products','update')){ ?>
    <td class="tdc" align="center">
        <a href="<?php echo burl()?>/panel/product/sub/view/v/edit/id/<?php echo $product['id'];?>" title="edit the Products">
        <img src="<?php echo burl()?>/images/edit.png" border="0"></a>
    </td>
    <?php } if(isAllow('admin_products','read')){ ?>
    <td class="tdc" align="center">
        <a href="<?php echo burl()?>/panel/product/sub/view/id/<?php echo $product['id'];?>" title="display the Products">
        <img src="<?php echo burl()?>/images/view.gif" border="0"></a>
    </td>
    <?php } if(isAllow('admin_products','delete')){ ?>
    <td class="tdc" align="center">
        <a href="<?php echo burl()?>/panel/product/sub/delete/id/<?php echo $product['id'];?>" title="delete the Products, then no restore after deleted" onclick="return delok('Products');">
        <img src="<?php echo burl()?>/images/del.png" border="0"></a>
    </td>
    <?php }?>
</tr>
<?php }?>
</table>
