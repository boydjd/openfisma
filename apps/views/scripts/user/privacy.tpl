<style>
.notice {
    text-align:left;
}
a.button {
    display:inline;
    float:none;
}
</style>

<div class="barleft">
<div class="barright">
<p><b>Privacy Policy for <?php echo readSysConfig('system_name');?></b>&nbsp;<a class="button" href="javascript:history.go(-1);">Back</a></p>
</div>
</div>
<div class="notice">
<h1>Privacy Policy for <?php echo readSysConfig('system_name');?></h1>
<p><?php echo nl2br(readSysConfig('privacy_policy')); ?></p>
</div>


