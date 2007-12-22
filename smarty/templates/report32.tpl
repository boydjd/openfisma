<!--
  -- FIPS 199 Category report
  -- Input:
  --  rpdata - array containing two rowsets
  --   rpdata[0] - set of system detail data rows
  --   rpdata[1] - row of LOW/MODERATE/HIGH totals
  -- 
  -->
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr align="left" width="60%" valign="middle">
    <td>
<table width="80%" border="0" cellpadding="0" cellspacing="0" class="tbframe" >
<!-- 
  -- Summary table
  -->
<tr align="center">
  <th width="20%"><b>{$report_lang[3][1][2][0]}</b></th>
  <th width="20%"><b>{$report_lang[3][1][2][1]}</b></th>
  <th width="20%"><b>{$report_lang[3][1][2][2]}</b></th>
  <th width="20%"><b>{$report_lang[3][1][2][3]}</b></th>
</tr>
<tr align="center">
  <td class="tdc"><b>{$report_lang[3][1][2][4]}</b></td>
  <td class="tdc">{$rpdata[1].LOW}</td>
  <td class="tdc">{$rpdata[1].MODERATE}</td>
  <td class="tdc">{$rpdata[1].HIGH}</td>
</tr>
</table></td>
    <td width="40%"><img src=piechart.php?data[]={$rpdata[1].LOW}&data[]={$rpdata[1].MODERATE}&data[]={$rpdata[1].HIGH}></td>
</tr>    
</table><br>


<!--
  -- Details table
  -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
<!-- table header row -->
<tr align="center">
    <th width="13%">{$report_lang[3][1][2][5]}</th>
    <th width="13%">{$report_lang[3][1][2][6]}</th>
    <th width="13%">{$report_lang[3][1][2][7]}</th>
    <th width="13%">{$report_lang[3][1][2][8]}</th>
    <th width="13%">{$report_lang[3][1][2][9]}</th>
    <th width="13%">{$report_lang[3][1][2][10]}</th>
    <th width="13%">{$report_lang[3][1][2][11]}</th>
    <th width="13%" nowrap>{$report_lang[3][1][2][12]}</th>
</tr>
<!-- table detail rows -->
{foreach item=item from=$rpdata[0]}
<tr align="center">
    <td class="tdc">{$item.name}</td>
    <td class="tdc">{$item.type}</td>
    <td class="tdc">{$item.crit}</td>
    <td class="tdc">{$item.fips}</td>
    <td class="tdc">{$item.conf}</td>
    <td class="tdc">{$item.integ}</td>
    <td class="tdc">{$item.avail}</td>
    <td class="tdc">{$item.last_upd}</td>
</tr>
{/foreach}
</table>
