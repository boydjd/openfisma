<?php
/**
 * This file is used for update old remediation comments to the new style in database.
 */
require_once("../../../ovms.ini.php");
require_once("dblink.php");
// required for all pages, sets smarty directory locations for cache, templates, etc.
require_once("smarty.inc.php");

$page_size = 10;
$step = (isset($_GET['s']) && (int)$_GET['s']>0)?(int)$_GET['s']:1;

$keywords = array();
$keywords['NONE'] = array("UPDATE: evidence upload",
                        "FSA to OVMS Conversion Comments",
                        "UPDATE: recommended course of action",
                        "SYSTEM: NEW REMEDIATION CREATED",
                        "UPDATE: course of action",
                        "UPDATE: threat source",
                        "UPDATE: countermeasure",
                        "UPDATE: threat justification",
                        "UPDATE: countermeasure justification",
                        "UPDATE: remediation type",
                        "UPDATE: threat level",
                        "UPDATE: countermeasure effectiveness",
                        "UPDATE: course of action resources",
                        "Password for evidence - FMSCM2007!");
$keywords['SSO'] = array("UPDATE: course of action evaluation");
$keywords['EST'] = array("UPDATE: course of action estimated completion date");
$keywords['EV_SSO'] = array("UPDATE: SSO evidence evaluation");
$keywords['EV_FSA'] = array("UPDATE: FSA evidence evaluation");
$keywords['EV_IVV'] = array("UPDATE: IV&V evidence evaluation");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Remediation Comments Update Tools</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="js/jquery.js" type="text/javascript" ></script>
    <script src="js/main.js" type="text/javascript" ></script>
    <link href="css/main.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div>
<h1>Remediation Comments Update Tools</h1>
<span><a href="index.php?s=1">By Group</a></span>
<span><a href="index.php?s=2">One By One</a></span>
</div>
<?php

if ($step == 1){
// step 1
$sql = "SELECT COUNT(*) AS c, `comment_topic` AS topic FROM ".TN_POAM_COMMENTS." 
        WHERE  `comment_type` IS NULL OR `comment_type` = ''
        GROUP BY `comment_topic` ORDER BY c DESC";
$result = $db->sql_query($sql) or die("Query failed:".$sql."<br>".$db->sql_error());
$groups = $db->sql_fetchrowset($result);


?>
<table>
<thead><h2>By Group</h2></thead>
<tr>
    <th>We Guess</th>
    <th>Count</th>
    <th>Topic</th>
</tr>
<?
$flag = 1;
foreach ((array)$groups as $group) {
    $group['guess'] = "";
    $flag *= -1;
    $class = ($flag>0)?" class='alt'":"";
    foreach ($keywords as $k=>$ka) {
    	if(in_array($group['topic'], $ka)) $group['guess']=$k;
    }
?>
<tr>
    <td<?=$class?>><?=getSelectOptions($group['guess'])?></td>
    <td<?=$class?>><?=$group['c']?></td>
    <td<?=$class?>><?=$group['topic']?></td>
</tr>
<?
}
?>
</table>

<?
// end of step 1
}

if ($step == 2) {
	$sql = " FROM ".TN_POAM_COMMENTS." 
        WHERE `comment_type` = '' OR `comment_type` IS NULL";

$result = $db->sql_query("SELECT COUNT(*) ".$sql) or die("Query failed:".$sql."<br>".$db->sql_error());
$total = array_pop(array_pop($db->sql_fetchrowset($result)));
$page_no = (isset($_GET['p']) && (int)$_GET['p']>0)?(int)$_GET['p']:1;

$total_pages = floor($total/$page_size);

$page_sql = " LIMIT ".($page_no-1)*$page_size.",".$page_size;
$result = $db->sql_query("SELECT * ".$sql.$page_sql) or die("Query failed:".$sql."<br>".$db->sql_error());

$comments = $db->sql_fetchrowset($result);
?>
<table>
<thead><h2>One By One</h2> <i>-- We suggest you use "By Group" first.</i>
<a href="#" class="change_all" id="NONE">Hide All</a>
<!--<a href="#" class="change_all" id="SSO">SSO All</a>-->
</thead>
<tr>
    <th>We Guess</th>
    <th>ID</th>
    <th>POA&M ID</th>
<!--    <th>User ID</th>-->
<!--    <th>Evidence ID</th>-->
    <th>Date</th>
    <th>Topic</th>
    <th>Body</th>
<!--    <th>Type</th>-->
</tr>
<?
$flag = 1;
foreach ((array)$comments as $c) {
    $flag *= -1;
    $class = ($flag>0)?" class='alt'":"";
?>
<tr>
    <td<?=$class?>><?=getSelectOptions('')?></td>
    <td<?=$class?>><?=$c['comment_id']?></td>
    <td<?=$class?>><?=$c['poam_id']?></td>
<!--    <td><?=$c['user_id']?></td>-->
<!--    <td><?=$c['ev_id']?></td>-->
    <td<?=$class?>><?=$c['comment_date']?></td>
    <td<?=$class?>><?=$c['comment_topic']?></td>
    <td<?=$class?>><?=$c['comment_body']?></td>
<!--    <td><?=$c['comment_type']?></td>-->
</tr>
<?
}
?>
</table>
<?
$links = "";
for ($i=1;$i<$total_pages;$i++){
    if ($i == $page_no){
        $links .= " $i ";
    }
    else {
        $links .= " <a href='index.php?p=$i&s=2'>$i</a> \n";
    }
}
echo $links;
// end of step 2
}

?>
<div>
    <input type="button" id="update" value="Yes, update for me now!">
</div>
</body>
</html>
<?php

function getSelectOptions($guess){
    $g_none = ($guess=='NONE')?'selected="selected"':'';
    $g_sso = ($guess=='SSO')?'selected="selected"':'';
    $g_est = ($guess=='EST')?'selected="selected"':'';
    $g_ev_sso = ($guess=='EV_SSO')?'selected="selected"':'';
    $g_ev_fsa = ($guess=='EV_FSA')?'selected="selected"':'';
    $g_ev_ivv = ($guess=='EV_IVV')?'selected="selected"':'';
    $g_ = ($guess=='')?'selected="selected"':'';
    $action_select = '
        <select name="guess">
            <option value="NONE" '.$g_none.'>Hide it</option>
            <option value="SSO" '.$g_sso.'>SSO evaluation</option>
            <option value="EST" '.$g_est.'>EST changed</option>
            <option value="EV_SSO" '.$g_ev_sso.'>Evidence SSO evaluation</option>
            <option value="EV_FSA" '.$g_ev_fsa.'>Evidence FSA evaluation</option>
            <option value="EV_IVV" '.$g_ev_ivv.'>Evidence IVV evaluation</option>
            <option value="" '.$g_.'>Do nothing</option>
        </select>';
    return $action_select;
}
?>