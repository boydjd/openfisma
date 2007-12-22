<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width=10></td>
	<td  align="left"><b>{$report_lang[3][1][5][4]}: </b> {$rpdata[0]}</td>
	<td  align="right"><b>{$report_lang[3][1][5][5]}: </b></td>
	<td width=10   align="left"><div id=sum></div></td>
	<td width=10></td>
</tr>
</table>
{section name=rec loop=$rpdata[1] start=0}
	{if $smarty.section.rec.iteration%$colnum == 1}
	<br>
	<table width="100%" border="0" cellpadding="0" cellspacing="0"  class="tipframe">
	<tr align="center">
	<td width="{$colwidth}%">
		<table border="0" cellpadding="0" cellspacing="0"  width="100%" height="100%">
		<tr><th>{$report_lang[3][1][5][1]}</th></tr>
		<tr><th nowrap>{$report_lang[3][1][5][2]}</th></tr>
		</table>
	</td>
	{assign var=tbflag value=0}
	{/if}
	<td width="{$colwidth}%">
		<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
			<tr><td class="tdc" align="center">{$rpdata[1][rec].nick}</td></tr>
			<tr><td class="tdc" align="center">{$rpdata[1][rec].num}</td></tr>
		</table>
	</td>
	{if $smarty.section.rec.iteration%$colnum == 0}
		</tr>
		</table>
		{assign var=tbflag value=1}
	{/if}
{assign var=sum0 value=$sum0+$rpdata[1][rec].num}
{/section}
	{if $tbflag ne 1}
			{assign var=sumtd value=$colnum-$smarty.section.rec.iteration%$colnum}
			{*assign var=sumtd value=$smarty.section.rec.iteration|mod $colnum*}
			{if $sumtd ne $colnum}
			{assign var=sumtd value=$sumtd+1}
				{section name=addtd max=$sumtd loop=$sumtd}
					<td width="{$colwidth}%">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
								<tr><td class="tdc" align="center">&nbsp;</td></tr>
								<tr><td class="tdc" align="center">&nbsp;</td></tr>
								</table>
					</td>
				{/section}
				{/if}
		</tr>
		</table>
	{/if}
{*/foreach*}
<script>
	sum.innerHTML = {$sum0};
</script>
<!--tr align="center">
	<td class="tdc">{$report_lang[3][1][5][3]}</td>
	<td class="tdc">{$sum0}</td>
</tr-->

