<!-- NIST Baseline Security Controls -->

<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td  width="5%"></td>	
	<td width="25%" valign="top">
		<!--Management-->
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
			<tr align="center">
				<td colspan="2"><b>{$report_lang[3][1][1][0]}</b></td>
			</tr>
			<tr align="center">
				<th>{$report_lang[3][1][1][3]}</th>
				<th>{$report_lang[3][1][1][4]}</th>
			</tr>
			{foreach item=item from=$rpdata[0]}
			<tr align="center">
				<td class="tdc">{$item.t}</td>
				<td class="tdc">{$item.n}</td>
			</tr>
			{assign var=sum0 value=$sum0+$item.n}
			{/foreach}
			<tr align="center">
				<td class="tdc">{$report_lang[3][1][1][5]}</td>
				<td class="tdc">{$sum0}</td>
			</tr>
		</table>
	</td>
	<td  width="5%"></td>	
	<td width="25%"  valign="top">
		<!--Operational-->	
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
			<tr align="center">
				<td colspan="2"><b>{$report_lang[3][1][1][1]}</b></td>
			</tr>
			<tr align="center">
				<th>{$report_lang[3][1][1][3]}</th>
				<th>{$report_lang[3][1][1][4]}</th>
			</tr>
				{foreach item=item from=$rpdata[1]}
			<tr align="center">
				<td class="tdc">{$item.t}</td>
				<td class="tdc">{$item.n}</td>
			</tr>
				{assign var=sum1 value=$sum1+$item.n}
				{/foreach}
			<tr align="center">
				<td class="tdc">{$report_lang[3][1][1][5]}</td>
				<td class="tdc">{$sum1}</td>
			</tr>
		</table>
	</td>
	<td  width="5%"></td>	
	<td width="25%"  valign="top">
		<!--Technical-->	
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
			<tr align="center">
				<td colspan="2"><b>{$report_lang[3][1][1][2]}</b></td>
			</tr>
			<tr align="center">
				<th>{$report_lang[3][1][1][3]}</th>
				<th>{$report_lang[3][1][1][4]}</th>
			</tr>
				{foreach item=item from=$rpdata[2]}
			<tr align="center">
				<td class="tdc">{$item.t}</td>
				<td class="tdc">{$item.n}</td>
			</tr>
				{assign var=sum2 value=$sum2+$item.n}
				{/foreach}
			<tr align="center">
				<td class="tdc">{$report_lang[3][1][1][5]}</td>
				<td class="tdc">{$sum2}</td>
			</tr>
		</table>
   	</td>
	<td  width="5%"></td>	
	</tr>
</table>
