<script language="javascript">
    $(function(){
        $(":button[name=select_all]").click(function(){
            $(":checkbox").attr( 'checked','checked' );
        });
        $(":button[name=select_none]").click(function(){
            $(":checkbox").attr( 'checked','' );
        });
    })
</script>
<script language="javascript" src="/javascripts/jquery/jquery.validate.js"></script>
<script language="javascript" src="/javascripts/account.validate.js"></script>
<div class="barleft">
    <div class="barright">
        <p><b>User Account Information</b>
    </div>
</div>

<p>The new password must meet the following complexity criteria:</p>
<ul>
<?php
foreach ($this->requirements as $requirement) {
    echo "<li>$requirement";
}
?>
</ul>

<div class="form_box">
<?php echo $this->form ?>
</div>

<br>
