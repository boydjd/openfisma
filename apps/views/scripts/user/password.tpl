<div class="barleft">
<div class="barright">
<p><b>Change Password</b>
</div>
</div>

<p>For security reasons, you must enter your old password in order to create a new password.</p>
<p>The new password must meet the following complexity criteria:</p>
<ul>
<?php
foreach ($this->requirements as $requirement) {
    echo "<li>$requirement";
}
?>
</ul>

<?php
    echo $this->form;
?>
