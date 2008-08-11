<script language="javascript" src="/javascripts/jquery/jquery.validate.js"></script>
<script language="javascript" src="/javascripts/system.validate.js"></script>
<div class="barleft">
<div class="barright">
<p><b>System Information</b>
</div>
</div>
<table border="0" width="95%" align="center">
<tr>
    <td align="left"><font color="blue">*</font> = Required Field</td>
</tr>
</table>
<form id="systemform" name="edit" method="post" action="/panel/system/sub/create/s/save">
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
    <tr>
        <td align="right" class="thc" width="200">System Name:</td>
        <td class="tdc">&nbsp;<input type="text" name="system[name]" size="50">
        <font color="blue"> *</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Acronym:</td>
        <td class="tdc">&nbsp;<input type="text" name="system[nickname]" size="10">
        <font color="blue"> *</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Confidentiality:</td>
        <td class="tdc">&nbsp;<select name="system[confidentiality]">
            <option value="HIGH">High</option>
            <option value="MODERATE">Moderate</option>
            <option value="LOW">Low</option></select><font color="blue">*</font>
        </td>            
    </tr>
    <tr>
        <td align="right" class="thc">Integrity:</td>
        <td class="tdc">&nbsp;<select name="system[integrity]">
            <option value="HIGH">High</option>
            <option value="MODERATE">Moderate</option>
            <option value="LOW">Low</option></select><font color="blue">*</font>
        </td>
    </tr>
    <tr>
        <td align="right" class="thc">Availability:</td>
        <td class="tdc">&nbsp;<select name="system[availability]">
            <option value="HIGH">High</option>
            <option value="MODERATE">Moderate</option>
            <option value="LOW">Low</option></select><font color="blue">*</font>
        </td>
    </tr>
    <tr>
        <td align="right" class="thc">Criticality:</td>
        <td class="tdc">&nbsp;<select name="system[criticality]">
            <option value="NONE">NONE</option>
            <option value="SUPPORTIVE">SUPPORTIVE</option>
            <option value="IMPORTANT">IMPORTANT</option>
            <option value="CRITICAL">CRITICAL</option></select><font color="blue">*</font>
        </td>
    </tr>
    <tr>
        <td align="right" class="thc">Type:</td>
        <td class="tdc">&nbsp;<select name="system[type]">
            <option value="GENERAL SUPPORT SYSTEM">GENERAL SUPPORT SYSTEM</option>
            <option value="MINOR APPLICATION">MINOR APPLICATION</option>
            <option value="MAJOR APPLICATION">MAJOR APPLICATION</option></select>
        </td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Description:</td>
        <td class="tdc">&nbsp;<textarea name="system[desc]" size="30" cols="80" rows="5"></textarea></td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Criticality Justification:</td>
        <td class="tdc">&nbsp;<textarea name="system[criticality_justification]" cols="80" rows="5"></textarea></td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Sensitivity Justification:</td>
        <td class="tdc">&nbsp;<textarea name="system[sensitivity_justification]" cols="80" rows="5"></textarea></td>
    </tr>
</table>
<br>
<?php if(!empty($this->sg_list)){
    $i = 0;
    $num = 5;
?>
<fieldset><legend><b>System Groups</b></legend>
<input name="checkhead" value="sysgroup_" type="hidden">
<input name="checktip" value="System Group" type="hidden">
<label for="sysgroup[]" class="error">Please select at least one system group.</label>
<table border="0" width="100%">
<tr>
<?php foreach($this->sg_list as $row){
    $i++;
    $flag = $i%$num == 0?'</tr><tr>':'';
?>
    <td align="right"><input name="sysgroup[]" value="<?php echo $row['id'];?>" type="checkbox"></td>
    <td><span title="<?php echo $row['nickname'];?>" style="cursor: pointer;"><?php echo $row['name'];?></span></td>
<?php echo $flag; } ?>

</table></fieldset>
<?php } ?>
<br>
<table border="0" width="300">
<tr align="center">
    <td><input type="submit" value="Create" title="submit your request"></td>
    <td><span style="cursor: pointer"><input type="reset" value="Reset"></span></td>
</tr>
</table>
</form>
<br>
