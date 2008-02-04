{include file="header.tpl" title="$pageTitle" name="Report"}
<br>

<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Reports : Generate System RAFs</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>

<br>

<table width="95%" align="center" border="0">
	<tr>
    	<td align="left">

			<table border="0" cellpadding="5" cellspacing="0" class="tipframe">
				<tr>
                	<td><b>System:</b></td>
                	<td>
						<form action="" method="post">
							<select name="system_id">
							{html_options options=$system_list selected=$system_id}
							</select>
							<input type="hidden" value="4" name="t">
							<input type="submit">
						</form>
					</td>
 				</tr>
				<tr>
                	<td colspan="2">
						{if $num_poam_ids > 0}
						There are {$num_poam_ids} risk analysis forms for the current system.<br /> 
						Click <a href="#" onclick="frmHide.submit()">here</a> to download all risk analysis forms.<br /> 
						<form action="craf.php" method="POST" name="frmHide">
						<input type="hidden" name="poam_id" value="{foreach from=$poam_ids item=poam_id}{$poam_id.poam_id},{/foreach}">
						</form>
						This may take some time depending on the number of risk analysis forms, please wait.
						{elseif $system_id}
    					Sorry, no findings have enough information to generate risk analysis forms.
						{else}
    					Please select a system to generate risk analysis forms.
						{/if}
					</td>
         		</tr>
			</table>

		</td>
 	</tr>
</table>

{include file="footer.tpl"}