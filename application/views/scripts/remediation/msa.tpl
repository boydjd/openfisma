<script language="javascript">
function delok()
{
    var str = "The revised mitigation strategy must be fully approved before evidence can be re-submitted. Are you sure that you want to continue?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
</script>
<!-- BEGIN MITIGATION STRATEGY APPROVAL TABLE -->
<?php
    $postAction = "/remediation/msa/id/".$this->poam['id'];
    $array = array('recommendation'=>$this->poam['action_suggested'],
                   'desciption'    =>$this->poam['action_planned'],
                   'resources'     =>$this->poam['action_resources'],
                   'blscr'         =>$this->poam['blscr_id'],
                   'threat_level'  =>$this->poam['threat_level'],
                   'threat_source' =>$this->poam['threat_source'],
                   'threat_justification'=>$this->poam['threat_justification'],
                   'cmeasure_effectiveness'=>$this->poam['cmeasure_effectiveness'],
                   'cmeasure'=>$this->poam['cmeasure'],
                   'cmeasure_justification'=>$this->poam['cmeasure_justification']);
    $complete = 0;
    foreach($array as $row){
        if($row == '' || $row == 'NONE'){
            $complete++;
        }
    }
    if (0 == $complete) {
?>
<div class="barleft">
    <div class="barright">
        <p><b>Mitigation Strategy Approval</b></p>
    </div>
</div>
<div>
<?php if ('OPEN' == $this->poam['status'] && Config_Fisma::isAllow('remediation', 'mitigation_strategy_submit')) { ?>
    <a class="button" href="<?php echo $postAction;?>/is_msa/1">Submit Mitigation Strategy</a>
<?php } if ('EN' == $this->poam['status'] || 'EO' == $this->poam['status'] && Config_Fisma::isAllow('remediation', 'mitigation_strategy_revise')) { ?>
    <a class="button" href="<?php echo $postAction;?>/is_msa/0" onclick="return delok();">Revise Mitigation Strategy</a>
<?php }  }
?>
</div>
<?php
    if ($this->poam['status']!= 'NEW' && $this->poam['status']!= 'OPEN') {
        $i = 0;
        foreach ($this->ms_evals as $v) {
            $k = $i++;
?>

<form action="<?php echo $postAction;?>" method="post" name="ms_approval_<?php echo $k;?>">
<table border="0" cellpadding="5" cellspacing="1" class="tipframe">
    <th align='left' colspan="2">Approval</th>
    <tr>
        <td colspan="2">
            <i>(All fields above must be set and saved to make SSO approval field editable.)</i>
        </td>
    </tr>
<?php
    $editable = true;
    $comment = new Comments();
    foreach ($v as $precedenceId=>$row) {
        $value = 'NONE';
        if (isset($row['decision'])) {
            $value = $row['decision'];
        }
        echo "<tr><td><b>".$row['name']."&nbsp;</b>";
        if ('NONE' == $value && $editable) {
            if(Config_Fisma::isAllow('remediation', $row['function'])){
                echo '<input type="hidden" name="eval_id" value="'.$row['id'].'"/>';
                echo '<input type="hidden" name="topic" value="" />';
                echo '<input type="hidden" name="reject" value="" />';
                echo '<input type="hidden" name="decision" value="APPROVED" />';
                echo '<input type="submit" value="APPROVE" />';
                echo '<input type="button" value="DENY" onclick="ms_comment(document.ms_approval_'.$k.');" />';
                $editable = false;
            }
        } else {
            echo $value."</td>";
            if ($value == 'APPROVED') {
                echo "<td> -- <i>by {$row['username']} ON {$row['date']}</i></td>";
            } else if ($value == 'DENIED') {
                $ret = $comment->fetchRow('poam_evaluation_id = '.$row['pev_id']);
                echo "<td>";
                if (!empty($ret)) {
                    echo "<b>{$ret->topic}</b>:{$ret->content}";
                }
                echo " -- <i> by {$row['username']} ON {$row['date']}</i></td>";
            }
        }
    }

?>
</table>
</form>
<?php } }?>

<div id='ms_dialog' style="display:none">
Topic:
<input type="text" name="topic" size=80 value="" />
Justification:
<textarea name="reason" rows="3" cols="76" ></textarea>
</div>
<!-- END MITIGATION STRATEGY APPROVAL TABLE -->

