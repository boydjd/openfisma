<script language="javascript" src="<?php echo burl(); ?>/javascripts/jquery/jquery.validate.js"></script>
<script language="javascript" src="<?php echo burl(); ?>/javascripts/sysgroup.validate.js"></script>
<div class="barleft">
<div class="barright">
<p><b>System Group Information</b>
</div>
</div>
<table border="0" width="95%" align="center">
<tr>
    <td align="left"><font color="blue">*</font> = Required Field</td>
</tr>
</table>
<form id="sysgroupform" name="edit" method="post" action="<?php echo burl()?>/panel/sysgroup/sub/create/s/save">
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
    <tr>
        <td align="right" class="thc" width="200">System Group Name:</td>
        <td class="tdc">&nbsp;<input type="text" name="sysgroup[name]" size="50">
        <font color="blue"> *</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">System Group Nickname:</td>
        <td class="tdc">&nbsp;<input type="text" name="sysgroup[nickname]" size="50">
        <font color="blue"> *</font></td>
    </tr>
</table>
<br>
<br>
<table border="0" width="300">
<tr align="center">
    <td><input type="submit" value="Create" title="submit your request"></td>
    <td><span style="cursor: pointer"><input type="reset" value="Reset"></span></td>
</tr>
</table>
</form>
<br>
