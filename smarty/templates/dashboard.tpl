<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{if $view_right == 1}

<br>

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Dashboard</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<table width="95%" align="center"  border="0" cellpadding="10" class="tipframe">
	<tr>
		<td  align="left"><b>Alerts </b><br>
			<br>
			<!-- Awaiting Mitigation Strategy -->
			{$need_mit}
			<!-- Awaiting Evidence -->
			{$need_ev_ot}
			<!-- Overdue Awaiting Evidence -->
			{$need_ev_od}
			<br>
		</td>
	</tr>
</table>  

<br>

<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tipframe">
	<tr><td colspan="3"  align="left"><b>&nbsp;&nbsp;&nbsp;Management Overview </b></td></tr>
    <tr>
      <td width="33%"  align="center">{$dashboard1}</td>
      <td width="34%"  align="center">{$dashboard2}</td>
      <td width="33%"  align="center">{$dashboard3}</td>
    </tr>
    <tr>
      <td width="33%"  align="center">Current Distribution of<br>POA&M Status</td>
      <td width="34%"  align="center">Current POA&M Item<br>Totals by Status</td>
      <td width="33%"  align="center">Current Distribution of<br>POA&M Type</td>
    </tr>
</table>

{else}

<div class="noright">{$noright}</div>

{/if}

<br>

{include file="footer.tpl"}
