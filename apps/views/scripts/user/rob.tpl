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
        <p><b>Rules of Behavior</b> <a class="button" href="javascript:history.go(-1);">Back</a></p>
    </div>
</div>
<div class="notice">
<h1>Behavior Rules for <?php echo readSysConfig('system_name');?></h1>
<div><?php echo nl2br(readSysConfig('behavior_rule')); ?></div>
</div>

