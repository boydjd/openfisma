<div class="barleft">
<div class="barright">
<p><b>Change Profile</b>
</div>
</div>
<div class="form_box">
    <?php echo $this->form ?>
</div>

<div class="barleft">
<div class="barright">
<p><b>Change Password</b>
</div>
</div>
<form name="edit" method="post" action="/panel/user/sub/pwdchange/s/save">
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
    <tr>
        <td align="" colspan="2" style="font-weight:bold;font-family:tahoma;font-size:11px;">
            Please Create a New Password Below:</td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Old Password:</td>
        <td >&nbsp;<input type="password" name="pwd[old]"></td>
    </tr>
    <tr>
        <td align="right" class="thc">New Password:</td>
        <td >&nbsp;<input type="password" name="pwd[new]"></td>
    </tr>
    <tr>
        <td align="right" class="thc">Confirm Password:</td>
        <td >&nbsp;<input type="password" name="pwd[confirm]"></td>
    </tr>
</table>
<br>
<br>
<table border="0" width="300">
<tr align="center">
    <td><span style="cursor: pointer"><input type="reset" value="Reset"></span></td>
    <td><input type="submit" value="Submit" title="submit your request"></td>
</tr>
</table>
</form>
<br>

<?php echo $this->partial('user/assign_notify.tpl', array('notify_frequency'=>$this->notify_frequency, 'notify_email'=>$this->notify_email, 'availableList'=>$this->availableList, 'enableList'=>$this->enableList));?>

