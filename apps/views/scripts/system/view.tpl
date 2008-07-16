<div class="barleft">
<div class="barright">
<p><b>System Information</b>
</div>
</div>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
    <tr>
        <td align="right" class="thc" width="200">System Name:</td>
        <td class="tdc"><?php echo $this->system['name'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Acronym:</td>
        <td class="tdc"><?php echo $this->system['nickname'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Organization:</td>
        <td class="tdc"><?php echo $this->system['primary_office'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Confidentiality:</td>
        <td class="tdc"><?php echo $this->system['confidentiality'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Integrity:</td>
        <td class="tdc"><?php echo $this->system['integrity'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Availability:</td>
        <td class="tdc"><?php echo $this->system['availability'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc">Type:</td>
        <td class="tdc"><?php echo $this->system['type'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Description:</td>
        <td class="tdc"><?php echo $this->system['desc'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Criticality Justification:</td>
        <td class="tdc"><?php echo $this->system['criticality_justification'];?></td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Sensitivity Justification:</td>
        <td class="tdc"><?php echo $this->system['sensitivity_justification'];?></td>
    </tr>
</table>
<br>
<fieldset><legend><b>System Groups</b></legend>
<?php if(!empty($this->user_sysgroup_list)){
    $i = 0;
    $num = 5;
?>
<table border="0" width="100%">
<tr>
<?php foreach($this->user_sysgroup_list as $row){
    $i++;
    $flag = $i%$num == 0?'</tr><tr>':'';
?>
    <td align="right"><input name="sysgroup_<?php echo $row['id'];?>" value="<?php echo $row['id'];?>" type="checkbox" checked></td>
    <td><span title="<?php echo $row['nickname'];?>" style="cursor: pointer;"><?php echo $row['name'];?></span></label></td>
<?php echo $flag; } ?>
</table>
<?php } ?>
</fieldset><br>
<br>
