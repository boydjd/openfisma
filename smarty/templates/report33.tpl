<!-- Products with Open Vulnerabilities Report -->
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
	<tr align="center">
		<td class="tdc" colspan="4"><b>{$report_lang[3][0][3]}</b></td>
	</tr>
	<tr align="center">
		<th width="13%">{$report_lang[3][1][3][0]}</th>
		<th width="13%">{$report_lang[3][1][3][1]}</th>
  		<th width="13%">{$report_lang[3][1][3][2]}</th>
  		<th width="13%">{$report_lang[3][1][3][3]}</th>
	</tr>
		{foreach item=item from=$rpdata}
	<tr align="center">
		<td class="tdc">{$item.Vendor}</td>
		<td class="tdc">{$item.Product}</td>
    	<td class="tdc">{$item.Version}</td>
    	<td class="tdc">{$item.NumoOV}</td>
	</tr>
		{/foreach}
</table>

