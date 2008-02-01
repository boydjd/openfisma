<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<!-- Heading Block -->

<!--
<table width="80%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><b>Finding:</b> Detail</td>
	<td align="right" valign="bottom">{$now}</td>
</tr>
</table>
-->
<br>

{if $act eq 'view' or $act eq 'edit'}


{if $msg ne ""}
<p><b><u>{$msg}</u></b></p>
{/if}


<table align="center" border="0" cellpadding="3" cellspacing="1">
<tr>
	<td>
	<table border="0" cellpadding="3" cellspacing="1">
	<tr>
		<td valign="top">
		<table border="0" width="300" cellpadding="3" cellspacing="1" class="tipframe">
		<tr>
			<th align="left">General Information</td>
		</tr>
		<tr>
			<td>&nbsp;<b>Finding ID:</b>&nbsp;{$finding->finding_id}</td>
		</tr>
		<tr>
			<td>&nbsp;<b>Date Discovered:</b>&nbsp;{$finding->finding_date_discovered}</td>
		</tr>
		<tr>
			<td>&nbsp;<b>Date Opened:</b>&nbsp;{$finding->finding_date_created}</td>
		</tr>
		<tr>
			<td>&nbsp;<b>Date Closed:</b>&nbsp;{$finding->finding_date_closed}</td>
		</tr>
		<tr>
			<td>&nbsp;<b>Date Modified:</b>&nbsp;{$finding->finding_date_created}</td>
		</tr>
		<tr>
			{if $act eq 'edit' && $finding->finding_status eq 'OPEN'}
			<form name="fdetail" method="post" action="findingdetail.php">
			<input type="hidden" name="act" value="edit">
			<input type="hidden" name="fid" value="{$finding->finding_id}">
			<input type="hidden" name="do" value="Update">
			<td>
			<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td>&nbsp;<b>Status:</b>&nbsp;</td>
				<td colspan="5"><select name="status">
					<option value="OPEN" selected>Open</option>
					<option value="DELETED">Deleted</option>
					</select></td>
				<td>&nbsp;<input type="image" name="sbt" src="images/button_update.png" border="0"></td>
			</tr>
			</table>
			</td>
			</form>
			{else}
			<td>&nbsp;<b>Status:</b>&nbsp;{$finding->finding_status}</td>
			{/if}
		<tr>
			<td>&nbsp;<b>Finding Source:</b>&nbsp;{$finding->source_name}</td>
		</tr>
		</table>
		</td>
		<td>&nbsp;</td>
		<td valign="top">
		<table border="0" width="500" cellpadding="0" cellspacing="1" class="tipframe">
		<tr>
			<th align="left" colspan="2">&nbsp;Asset: {$finding->asset_obj->asset_name}	</th>
		</tr>
		<tr>
			
			<td align="right" nowrap width="80"><b>System:</b></td>
			<td>&nbsp;
			{foreach from=$finding->asset_obj->system_arr item=sname}
			{$sname} {*|*}&nbsp;
			{/foreach}
			</td>
		</tr>
		<tr>
			<td align="right" nowrap width="80"><b>IP Address:</b></td>
			<td>&nbsp;
			{foreach from=$finding->asset_obj->ipaddr_arr item=sname}
			{$sname} {*|*}&nbsp;
			{/foreach}
			</td>
		</tr>

		<tr>
			<td align="right" nowrap width="80"><b>Network:</b></td>
			<td>&nbsp;
			{foreach from=$finding->asset_obj->network_arr item=sname}
			{$sname} {*|*}&nbsp;
			{/foreach}
			</td>
		</tr>
		
		<tr>
			<td align="right" width="80"><b>Vendor:</b></td>
			<td>&nbsp;{$finding->asset_obj->prod_vendor}</td>
		</tr>
		<tr>
			<td align="right" width="80"><b>Product:</b></td>
			<td>&nbsp;{$finding->asset_obj->prod_name}</td>
		</tr>
		<tr>
			<td align="right" width="80"><b>Version:</b></td>
			<td>&nbsp;{$finding->asset_obj->prod_version}</td>
		</tr>
		<!-- insert a blank line to even up with right table -->
		<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
		</td>
	</tr>

	<tr>
		<td colspan="3">
		<table border="0" width="100%" cellpadding="3" cellspacing="1" class="tipframe">
		<tr>
			<th align="left" colspan="3">Instance Specific Information:</td>
		</tr>
		<tr>
			<td width="10"></td>
			<td width="100%"><b>Description:</b> <br>
			<table border="0" width="770" cellpadding="3" cellspacing="1" class="tbframe" bgcolor="#eeeeee">
			<tr>
				<td>{$finding->finding_data}&nbsp;</td>
			</tr>
			</table>
			</td>
			<td width="10"></td>
		</tr>
		</table>
		</td>
	</tr>


	<tr>
		<td colspan="3">
		<table border="0" width="100%" cellpadding="3" cellspacing="1" class="tipframe">
		<tr>
			<th align="left" colspan="3">Vulnerability:</td>
		</tr>

		{foreach from=$finding->vulnerability_arr key=vseq item=vobj}
		<tr>
			<td width="10"></td>
			<td width="100%">
			<table border="0" width="100%" cellpadding="3" cellspacing="1" class="tbframe">
			<tr>
				<td>
				<table border="0" width="100%">
				<tr>
					<td align="right" width="120"><b>Vulnerability ID:<b></td>
					<td>{$vobj->vuln_seq}</td>
				</tr>
				<tr>
					<td align="right" width="120"><b>Description:<b></td>
					<td>
					<table border="0" width="637" cellpadding="3" cellspacing="1" class="tbframe" bgcolor="#eeeeee">
					<tr>
						<td>{$vobj->vuln_desc_primary}
{if $vobj->vuln_desc_secondary ne "0"}
 | {$vobj->vuln_desc_secondary}
{/if}
</td>
					</tr>
					</table>
					</td>
				</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td>
				<table border="0" width="100%">
				<tr>
					<td>
					<fieldset style="border:1px solid #44637A; padding:5">
					<legend><b>The vulnerability will cost the loss of</b></legend>
					<table border="0" width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_loss_confidentiality eq 1}checked{else}disabled{/if}>{if $vobj->vuln_loss_confidentiality eq 0}<font color="#888888">{/if}Confidentiality</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_loss_security_admin eq 1}checked{else}disabled{/if}>{if $vobj->vuln_loss_security_admin eq 0}<font color="#888888">{/if}Security Admin</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_loss_availability eq 1}checked{else}disabled{/if}>{if $vobj->vuln_loss_availability eq 0}<font color="#888888">{/if}Availability</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_loss_security_user eq 1}checked{else}disabled{/if}>{if $vobj->vuln_loss_security_user eq 0}<font color="#888888">{/if}Security User</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_loss_integrity eq 1}checked{else}disabled{/if}>{if $vobj->vuln_loss_integrity eq 0}<font color="#888888">{/if}Integrity</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_loss_security_other eq 1}checked{else}disabled{/if}>{if $vobj->vuln_loss_security_other eq 0}<font color="#888888">{/if}Security Other</td>
					</tr>
					</table>
					</fieldset>
					</td>
					<td>&nbsp;</td>
					<td>
					<fieldset style="border:1px solid #44637A; padding:5">
					<legend><b>Type of Vulnerability</b></legend>
					<table border="0" width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_access eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_access eq 0}<font color="#888888">{/if}Access</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_input_buffer eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_input_buffer eq 0}<font color="#888888">{/if}Input Buffer</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_exception eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_exception eq 0}<font color="#888888">{/if}Exception</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_other eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_other eq 0}<font color="#888888">{/if}Other</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_input eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_input eq 0}<font color="#888888">{/if}Input</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_race eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_race eq 0}<font color="#888888">{/if}Race</td>
						<td colspan="2"><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_environment eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_environment eq 0}<font color="#888888">{/if}Environment</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_input_bound eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_input_bound eq 0}<font color="#888888">{/if}Input Bound</td>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_design eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_design eq 0}<font color="#888888">{/if}Design</td>
						<td colspan="2"><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_type_config eq 1}checked{else}disabled{/if}>{if $vobj->vuln_type_config eq 0}<font color="#888888">{/if}Config</td>
					</tr>
					</table>
					</fieldset>
					</td>
					<td>&nbsp;</td>
					<td>
					<fieldset style="border:1px solid #44637A; padding:5">
					<legend><b>Vulnerability Range</b></legend>
					<table border="0" width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_range_local eq 1}checked{else}disabled{/if}>{if $vobj->vuln_range_local eq 0}<font color="#888888">{/if}Local</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_range_remote eq 1}checked{else}disabled{/if}>{if $vobj->vuln_range_remote eq 0}<font color="#888888">{/if}Remote</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="vuln" value="1" onclick="return false;" {if $vobj->vuln_range_user eq 1}checked{else}disabled{/if}>{if $vobj->vuln_range_user eq 0}<font color="#888888">{/if}User</td>
					</tr>
					</table>
					</fieldset>
					</td>
				</tr>
				</table>
				</td>
			</tr>

			</table>
			</td>
			<td width="10"></td>
		</tr>
		{/foreach}
		
		</table>
		</td>
	</tr>
	<tr>
		<td colspan="3" align="right"><br>
		<table border="0">
		<tr>
			{if $finding->finding_status != 'REMEDIATION'}
        	        <form name="poam" method="post" action="remediation_detail.php">
	                <input type="hidden" name="target" value="remediation">
                	<input type="hidden" name="action" value="new">
        	        <input type="hidden" name="finding_id" value="{$finding->finding_id}">
	                <td><input type="image" src="images/button_convert_to_poam.png" border="0"></td>
                	</form>
			{/if}
			
			<td>&nbsp;&nbsp;</td>
			<form name="finding" method="post" action="finding.php">
			<input type="hidden" name="sbt" value="{$submit}">
			<input type="hidden" name="fn" value="{$fn}">
			<input type="hidden" name="asc" value="{$asc}">
			<input type="hidden" name="pageno" value="{$pageno}">
			<input type="hidden" name="startdate" value="{$startdate}">
			<input type="hidden" name="enddate" value="{$enddate}">

			<input type="hidden" name="status" value="{$status}">
			<input type="hidden" name="source" value="{$source}">
			<input type="hidden" name="system" value="{$system}">
			<input type="hidden" name="vulner" value="{$vulner}">
			<input type="hidden" name="product" value="{$product}">
			<input type="hidden" name="network" value="{$network}">
			<input type="hidden" name="ip" value="{$ip}">
			<input type="hidden" name="port" value="{$port}">
			<td><input type="image" src="images/button_back.png" border="0" name="search"></td>
			</form>
		</tr>
		</table>
		</td>
	</tr>
	</table>
	</td>
</tr>
</table>

{else}
<p>{$noright}</p>
{/if}

<p>&nbsp;</p>

{include file="footer.tpl"}
