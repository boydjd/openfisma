{* BEGIN EXTRACT FROM header.tpl *}
<html>
<head>
	
<title>{$title} - {$name}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
{literal}
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/menu.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/cal.js"></script>
<link rel="stylesheet" type="text/css" href="stylesheets/main.css">
<link rel="stylesheet" type="text/css" href="stylesheets/mainmenu.css">
{/literal}
</head>

<!--
-- The rpdata data set consists of three data blocks:
-- [0] - a single POAM statistics row
-- [1] - a list of vulnerability description rows
-- [2] - a list of affected server/asset rows
-->

<br/>
<!-- Header Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="13"><img src="images/left_circle.gif" border="0"></td>
	<td bgcolor="#DFE5ED"><b>{$raf_lang[0][0]}</b>{$poam_id}</td>
	<td bgcolor="#DFE5ED" align="right">{$now}</td>
	<td width="13"><img src="images/right_circle.gif" border="0"></td>
</tr>
</table>
<!-- End Header Block -->

<br>
<table width="95%" align="center" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="25%" ><b>{$raf_lang[1][0]}</b></td>
    <td width="25%">{$WVTNO}</td>
    <td width="25%"><b>{$raf_lang[1][1]}</b></td>
    <td width="25%">{$rpdata[0].dt_discv}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][2]}</b>&nbsp;{$rpdata[0].s_po}</td>
    <td><b>{$raf_lang[1][3]}</b>&nbsp;{$rpdata[0].s_nick}</td>
    <td><b>{$raf_lang[1][4]}</b></td>
    <td>{$rpdata[0].dt_created}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][7]}</b></td>
    <td>{$rpdata[0].fs_nick}</td>
    <td><b>{$raf_lang[1][6]}</b></td>
    <td>{$rpdata[0].dt_mod}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][9]}</b></td>
    <td>{$rpdata[0].is_repeat}</td>
    <td><b>{$raf_lang[1][8]}</b></td>
    <td>{$rpdata[0].dt_closed}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][10]}</b></td>
    <td>{$rpdata[0].prev}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][5]}</b></td>
    {section name=row loop=$rpdata[1]}
    <td>{$rpdata[1][row].vuln}</td>
    {/section}
  </tr>
</table>

<!-- Header Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="13"><img src="images/left_circle.gif" border="0"></td>
	<td bgcolor="#DFE5ED">&nbsp;</td>
	<td bgcolor="#DFE5ED">&nbsp; </td>
	<td width="13"><img src="images/right_circle.gif" border="0"></td>
</tr>
</table>
<!-- End Header Block -->

<br>
{*impact table*}
<table width="95%" align="center" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="50%">
    
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="50%" ><b>{$raf_lang[2][0]}</b></td>
    <td width="50%">{$rpdata[0].s_a}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[2][1]}</b></td>
    <td width="50%">{$rpdata[0].s_c_just}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[2][2]}</b></td>
    <td width="50%">{$rpdata[0].data_sensitivity}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[2][3]}</b></td>
    <td width="50%">{$rpdata[0].s_s_just}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[2][4]}</b></td>
    <td width="50%">{$rpdata[0].impact}</td>
  </tr>
  
</table>
    
    
    </td>
    <td width="50%">{include file="raf_impact_table.tpl"}</td>
  </tr>
 
</table><br>

<!-- Header Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="13"><img src="images/left_circle.gif" border="0"></td>
	<td bgcolor="#DFE5ED">&nbsp;</td>
	<td bgcolor="#DFE5ED">&nbsp; </td>
	<td width="13"><img src="images/right_circle.gif" border="0"></td>
</tr>
</table>
<!-- End Header Block -->
<br>
<table width="95%" align="center" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="50%">
        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="50%" ><b>{$raf_lang[3][0]}</b></td>
    <td width="50%">{$rpdata[0].cm}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[3][1]}</b></td>
    <td width="50%">{$rpdata[0].cm_eff}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[3][2]}</b></td>
    <td width="50%">{$rpdata[0].cm_just}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[3][3]}</b></td>
    <td width="50%">{$rpdata[0].t_level}</td></tr><tr>
    <td width="50%" ><b>{$raf_lang[3][4]}</b></td>
    <td width="50%">{$rpdata[0].t_source}</td>
  </tr><tr>
    <td width="50%" ><b>{$raf_lang[3][5]}</b></td>
    <td width="50%">{$rpdata[0].t_just}</td>
  </tr><tr>
    <td width="50%" ><b>{$raf_lang[3][6]}</b></td>
    <td width="50%">{$rpdata[0].threat_likelihood}</td>
  </tr><tr>
    <td width="50%" ><b>{$raf_lang[3][7]}</b></td>
    <td width="50%">&nbsp;</td>
  </tr>
  {section name=row loop=$rpdata[2]}
    <tr>
      <td>{$rpdata[2][row].pname}</td>
    </tr>
    {/section}
  </tr>
  
</table>
    </td>
    <td width="50%">{include file="raf_TL_table.tpl"}</td>
  </tr>
 
</table><br>

<!-- Header Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="13"><img src="images/left_circle.gif" border="0"></td>
	<td bgcolor="#DFE5ED">&nbsp;</td>
	<td bgcolor="#DFE5ED">&nbsp; </td>
	<td width="13"><img src="images/right_circle.gif" border="0"></td>
</tr>
</table>
<!-- End Header Block -->
<br>
<table width="95%" align="center" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="50%"  valign="top">    
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
<td width="{$table_width}" colspan="2"><b>{$raf_lang[4][0]}</b></td>
    </tr><tr>
    <td width="1%" ><b>{$raf_lang[4][1]}</b></td>
    <td width="80%">{$raf_lang[4][2]}</td></tr><tr>
    <td width="1%" ><b>{$raf_lang[4][3]}</b></td>
    <td width="80%">{$raf_lang[4][4]}</td></tr><tr>
    <td width="1%" ><b>{$raf_lang[4][5]}</b></td>
    <td width="80%">{$raf_lang[4][6]}</td></tr>
</table></td>
    <td width="50%">{include file="raf_risklevel_table.tpl"}</td>
  </tr>
 
</table><br>
<!-- Header Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="13"><img src="images/left_circle.gif" border="0"></td>
	<td bgcolor="#DFE5ED">&nbsp;</td>
	<td bgcolor="#DFE5ED">&nbsp; </td>
	<td width="13"><img src="images/right_circle.gif" border="0"></td>
</tr>
</table>
<!-- End Header Block -->
<br>
<table width="95%" align="center" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top">   <table width="100%"  border="0" cellspacing="0" cellpadding="0">
    <td width="20%" nowrap><b>{$raf_lang[5][0]}</b></td>
    <td width="80%">{$rpdata[0].act_sug}</td></tr><tr>
    <td width="20%" nowrap><b>{$raf_lang[5][1]}</b></td>
    <td width="80%">{$rpdata[0].act_plan}</td></tr>
</table></td>
    <td>&nbsp;</td>
  </tr>
 
</table>
<br>

<!-- Footer with Buttons at bottom -->
<table width="98%" align="center" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="right">
    <!--<input type="button" name="Button" value="Return to POA&amp;M Detail"  onclick="javascript:history.back();">-->
  <input type="button" name="Button" value="Print" onclick="javascript:window.print();">
  <input type="button" name="Button" value="Export to PDF" onclick="javascript:window.location='craf.php?poam_id={$poam_id}';">
  <!--input type="button" name="Button" value="Export to PDF" onclick="javascript:window.location='craf.php';"-->
    </td>
  </tr>
  <tr>
    <td align="center">{$warn_footer}</td>
  </tr>
</table>
<!-- End Footer with Buttons at bottom -->

{include file="footer.tpl"}
