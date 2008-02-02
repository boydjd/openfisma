{include file="header.tpl" title="OVMS" name="Report"}

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="13"><img src="images/left_circle.gif" border="0"></td>
	<td bgcolor="#DFE5ED"><b>Reports : Generate System RAFs</b></td>
	<td bgcolor="#DFE5ED" align="right">{$now}</td>
	<td width="13"><img src="images/right_circle.gif" border="0"></td>
</tr>
</table>
<br>

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tipframe">
<tr><td align="center">

<table width="80%" border="0" cellpadding="0" cellspacing="0" >
<tr><td align="center">
<form action="" method="post">
<select name="system_id">
{html_options options=$system_list selected=$system_id}
</select>
<input type="hidden" value="4" name="t">
<input type="submit">
</form>
</td></tr>
<tr><td align="center">
{if $num_poam_ids > 0}
	OK. There are {$num_poam_ids} remediation(s) in current system.<br /> 
	Click <a href="#" onclick="frmHide.submit()">here</a> to download the RAF package of them.<br /> 
	<form action="craf.php" method="POST" name="frmHide">
	<input type="hidden" name="poam_id"
	   value="{foreach from=$poam_ids item=poam_id}{$poam_id.poam_id},{/foreach}">
	</form>
	It might cost a long time if the remediations number is large, please wait.
{elseif $system_id}
    Sorry, there is no remediation which can generate RAF in the current system you choosed.
{else}
    Please select a system and submit query.
{/if}
</td></tr>
</table>

</td></tr>
</table>
<!-- craf.php?poam_id=400 -->

{include file="footer.tpl"}