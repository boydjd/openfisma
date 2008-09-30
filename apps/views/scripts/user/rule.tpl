<div class="notice">
<p>
    <i>System policy states that you must review and accept the Rules of Behavior
    for <?php echo readSysConfig('system_name');?> every
    <?php echo readSysConfig('rob_duration');?> days. If you do not accept these
    rules then you will be logged off.</i>
</p>
<h1>Rules of Behavior for <?php echo readSysConfig('system_name');?></h1>

    
<p><?php
    $behaviorRules = readSysConfig('behavior_rule');
    // Replace double newline with <p> tag
    $behaviorRules = preg_replace("/\n\s+\n/", '</p><p>', $behaviorRules);
    // Replace single newline with <br> tag
    $behaviorRules = str_replace("\n", '<br>', $behaviorRules);
    print($behaviorRules);
?></p>


<p>
    <a class="button" href="/user/logout">Cancel</a>
    <a class="button" href="/user/acceptrob">Continue</a>
</p>

<p style="clear:both"></p>
</div>


