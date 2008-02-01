<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/func.js"></script>
{/literal}

{if $view_right == 1}

<!-- Heading Block -->
<table class="tbline">
<tr><td id="tbheading">Dashboard</td><td id="tbtime">{$now}</td></tr>
</table>
<!-- End Heading Block -->

<br>

<table width="98%" align="center"  border="0" cellpadding="10" class="tipframe">
    <tr>
      <td  align="left"><b>Alerts </b><br>
        <br>
	{$need_type}
	{$need_mit}
	{$need_ev_ot}
	{$need_ev_od}
	{$new_poam}
        {$cap_expected} 
        {$cap_overdue} 
   	{$new_cap}
	{$review_pkg}		  		  
        <br>
      </td>
    </tr>
  </table>  

<br>

<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tipframe">
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
<p>Insufficient privileges to view dashboard.</p>
{/if}

<br>

{include file="footer.tpl"}
