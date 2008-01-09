
{include file="header.tpl" title="OVMS" name="Dashboard"}
{literal}
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/func.js"></script>
{/literal}

{if $view_right == 1 or $del_right == 1 or $edit_right == 1}

<!-- Heading Block -->
<table class="tbline">
<tr><td id="tbheading">Dashboard</td><td id="tbtime">{$now}</td></tr>
</table>
<!-- End Heading Block -->

<br>

<table width="100%" border="0" cellpadding="0" cellspacing="0" >
<tr align="center">
  <td>
<table width="99%"  border="0" class="tipframe">
    <tr>
      <td  align="left"> &nbsp; <b>Alerts </b><br>
<br>
	{$need_type}
	{$need_mit}
	{$need_ev_ot}
	{$need_ev_od}
	{$new_poam}
        {$cap_expected} 
        {$cap_overdue} 
   	{$new_cap}
	{$review_pkg}		  		  <br>
		  </td>
    </tr>
  </table>  
  
  
  </td>
  </tr>

<tr align="center">
  <td>
  <br>
  <table width="99%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
    <tr>
      <td colspan="3"  align="left">&nbsp;<b>Management Overview </b></td>
    </tr>
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
  
  </td>
  </tr>

</table>

{else}
<p>Insufficient privileges to view dashboard.</p>
{/if}
<p>&nbsp;</p>

{include file="footer.tpl"}
