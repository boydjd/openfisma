<style>
.notice {
    text-align:left;
}
</style>

<div class="notice">
<p>
    <i>System policy states that you must review and accept the Rules of Behavior
    for <?php echo readSysConfig('system_name');?> every
    <?php echo readSysConfig('rob_duration');?> days. If you do not accept these
    rules then you will be logged off.</i>
</p>
<h1>Rules of Behavior for <?php echo readSysConfig('system_name');?></h1>

    
<div><?php echo nl2br(readSysConfig('behavior_rule')); ?></div>


<p>
    <a class="button" href="/user/logout">Cancel</a>
    <a class="button" href="/user/acceptrob">Continue</a>
</p>

<p style="clear:both"></p>
</div>


