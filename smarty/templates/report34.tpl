<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
<tr align="center">
		<td class="tdc" colspan="3"><b>{$report_lang[3][0][4]}</b></td>
</tr>

<tr align="center">
	<th width="13%">{$report_lang[3][1][4][0]}</th>
	<th width="13%">{$report_lang[3][1][4][1]}</th>
  <th width="13%">{$report_lang[3][1][4][2]}</th>
</tr>
{foreach item=item from=$rpdata}
<tr align="center">
		<td class="tdc">{$item.Vendor}</td>
		<td class="tdc">{$item.Product}</td>
    <td class="tdc">{$item.Version}</td>
</tr>
{/foreach}
</table>

