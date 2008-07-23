<div class="barleft">
<div class="barright">
<p><b>Role Information</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
    <tr>
        <td align="right" class="thc" width="200">Role Name:</td>
        <td class="tdc">&nbsp;<?php echo $this->role['name'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Nickname:</td>
        <td class="tdc">&nbsp;<?php echo $this->role['nickname'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Description:</td>
        <td class="tdc"><?php echo nl2br($this->role['desc']);?></td>
    </tr>
   </table>
<br>
