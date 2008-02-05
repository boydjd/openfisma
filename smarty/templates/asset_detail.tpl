<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 
{literal}{/literal}
<br>
<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Asset Detail</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->
{if $view_right eq 1} <br>

<table width="95%" align="center" border="0" cellpadding="3" cellspacing="1">
	<tr valign='top'>
		<td width='50%'>

			<table width='100%' cellpadding='3' cellspacing='1' class='tipframe'>
				<th align='left'>Asset Information</th>
				<tr><td valign='center' align='left'><b>Name: </b>{$asset_name}</td></tr>
			  	<tr><td valign="center" align="left"><b>System:</b> {$asset_source}</td></tr>
				<tr><td valign="center" align="left"><b>Date Created:</b> {$asset_date_created} ({$asset_source})</td></tr>
			</table>

		</td>
		<td width='50%'>

			<table width="100%"  border="0"  cellpadding="3" cellspacing="1" class='tipframe'>
				<th align='left'>Product Information</th>
				<tr><td><b>Vendor: </b>{$product_vendor}</td></tr>
				<tr><td><b>Product: </b>{$product_name}</td></tr>
		  		<tr><td><b>Version: </b>{$product_version}</td></tr>
			</table>

		</td>
	</tr>
	<tr>

		<td colspan='2'>

			<table width='100%' cellpadding='3' cellspacing='1' class='tipframe'>
				<th align='left'>Address Information</th>
				<tr>
					<td>

						<table width='100%' cellpadding='3' cellspacing='1' class='tipframe'>
							<tr>
								<td width='50%'><b>Network: </b>({$address.network_nickname}) {$address.network_name}</td>
								<td width='50%'><b>IP Address: </b>{$address.address_ip}</td>
							</tr>
							<tr>
								<td width='50%'><b>Date Discovered: </b>{$address.address_date_created}</td>
								<td width='50%'><b>Port: </b>{$address.address_port}</td>
							</tr>
						</table>
					
					</td>
				</tr>
			</table>
			
		</td>
	</tr>
</table>

{else}
<p>No right do your request.</p>
{/if}

<table width="95%" align="center">
<tr>
<td align="left">
<form action='asset.php' method='POST'>
<!--	<input type='Submit' value='Return to Summary List'>-->
 <input type='hidden' name='Submit' value='Return to Summary List'/>
 <input type='submit' value="Back" >
</form>
</td>
</tr>
</table>

{include file="footer.tpl"} 