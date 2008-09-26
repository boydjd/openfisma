<style>
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
<p><?php
    $behaviorRules = readSysConfig('behavior_rule');
    // Replace double newline with <p> tags
    $behaviorRules = preg_replace("/\n\s+\n/", '</p><p>', $behaviorRules);
    print($behaviorRules);
?></p>
</div>

