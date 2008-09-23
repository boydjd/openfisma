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
        <p><b>Rules of Behavior for <?php echo readSysConfig('system_name');?></b>&nbsp;<a class="button" href="javascript:history.go(-1);">Back</a></p>
    </div>
</div>
<div class="notice">
<h1>Rules of Behavior for <?php echo readSysConfig('system_name');?></h1>
<div><?php echo nl2br(readSysConfig('behavior_rule')); ?></div>
</div>

