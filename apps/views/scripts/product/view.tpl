<div class="barleft">
<div class="barright">
<p><b>Product Information</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
    <tr>
        <td align="right" class="thc" width="200">Product Name:</td>
        <td class="tdc">&nbsp;<?php echo $this->product['name'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Vendor:</td>
        <td class="tdc">&nbsp;<?php echo $this->product['vendor'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Version:</td>
        <td class="tdc">&nbsp;<?php echo $this->product['version'];?></td>
    </tr>
 
    <tr>
        <td align="right" class="thc" width="200">Description:</td>
        <td class="tdc">&nbsp;<textarea name="prod_desc" size="30" cols="110" rows="5">
            <?php echo $this->product['desc'];?></textarea></td>
    </tr>
   </table>
<br>
