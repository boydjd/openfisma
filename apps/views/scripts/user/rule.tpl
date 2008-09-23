<style>
.notice {
    text-align:left;
}
</style>

<div class="notice">

<h1>Behavior Rules for <?php echo readSysConfig('system_name');?></h1>

    
<div><?php echo nl2br(readSysConfig('behavior_rule')); ?></div>


<p>
    <a class="button" href="/user/logout">Cancel</a>
    <a class="button" href="/user/acceptrob">Continue</a>
</p>

<p style="clear:both"></p>
</div>


