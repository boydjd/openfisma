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
        <td class="tdc">&nbsp;<textarea name="role_desc" size="30" cols="110" rows="5">
            <?php echo $this->role['desc'];?></textarea></td>
    </tr>
   </table>
<br>
