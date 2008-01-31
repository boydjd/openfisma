
{include file="header.tpl" title="OVMS" name="Create New Finding"}

{literal}
<script language="javascript" src="javascripts/ajax.js"></script>
<script language="javascript" src="javascripts/func.js"></script>
<script language="javascript" src="javascripts/form.js"></script>
{/literal}

<!-- Heading Block -->
<table class="tbline">              
<tr><td id="tbheading">Finding Creation</td><td id="tbtime">{$now}</td></tr>        
</table>
<!-- End Heading Block -->

<br>

{if $act eq 'new'}
{if $msg ne ""}
<p><b><u>{$msg}</u></b></p>
{/if}

<form name="finding" method="post" action="findingdetail.php" onsubmit="return qok();">
<input type="hidden" name="act"           value="{$act}">
<input type="hidden" name="do"            value="create">
<input type="hidden" name="vuln_offset"   value="0">
<input type="hidden" name="NUM_VULN_ROWS" value="50">

<table align="center" width="810" border="0" cellpadding="3" cellspacing="1">
<tr>
	<td colspan="2">
	<table border="0" width="100%" cellpadding="3" cellspacing="1">
	<tr>
		<td>
		<fieldset style="border:1px solid #44637A; padding:5">
		<legend><b>General Information</b></legend>
		<table border="0" width="400" cellpadding="1" cellspacing="1">
		<tr>
			<td>
			<table border="0" cellpadding="1" cellspacing="1">
			<tr>
				<td align="right"><b>Discovered Date:</b></td>
				<td>
				<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td><input type="text" name="discovereddate" size="12" maxlength="10" value="{$discovered_date}">&nbsp;</td>
					<td><span onclick="javascript:show_calendar('finding.discovereddate');"><img src="images/picker.gif" width=24 height=22 border=0></span></td>
				</tr>
				</table>
				</td>
			</tr>
{*
** Finding must begin life as OPEN - can't let user set this.
			<tr>
				<td align="right"><b>Status:</b></td>
				<td><select name="status">
					<option value="OPEN" selected>Open</option>
					<option value="REMEDIATION">Remediation</option>
					<option value="CLOSED">Closed</option>
					<option value="DELETED">Deleted</option>
					</select></td>
			</tr>
*}
			<tr>
				<td align="right"><b>Finding Source:</b></td>
				<td><select name="source">
					{foreach from=$source_list key=sid item=sname}
					<option value="{$sid}">{$sname}</option>
					{/foreach}
					</select></td>
			</tr>
			</table>
			</td>
		</tr>
		</table>

		</fieldset>
		</td>

		<td>
		<fieldset style="border:1px solid #44637A; padding:2">
		<legend><b>Instance Specific Information</b></legend>
		<table border="0" align="center" cellpadding="1" cellspacing="1">
		<tr>
			<td>Description:<br>
			<textarea name="finding_data" cols="60" rows="2" style="border:1px solid #333; height:41px;"></textarea></td>
		</tr>
		</table>
		</fieldset>
		</td>
	</tr>

	<tr style="display:none">
		<td colspan="2">
		
		<fieldset style="border:1px solid #44637A; padding:5">
		<legend><b>Vulnerability</b></legend>
		<table border="0" width="800" cellpadding="1" cellspacing="1">
		<tr>
			<td colspan="2">
			<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td>&nbsp;Vulnerability search:&nbsp;</td>
				<td><input type="text" name="vulner_needle" value="" maxlength="20" size="20"> &nbsp;</td>
				<td><span style="cursor: pointer" onclick="return loadVulnerList('ajaxsearch.php');"><img src="images/button_search.png" border="0"></span></td>
			</tr>
			</table>
			</td>
		</tr>
		<tr>
			<td colspan="3" valign="top"><hr size="1" color="#cccccc"></td>
		</tr>
		<tr>
			<td>Results of vulnerability search</td>
			<td>&nbsp;</td>
			<td>Current vulnerabilities associated with</td>
		</tr>
		<tr>
			<td width="500" valign="top">
			<table width="100%" border="0" cellpadding="1" cellspacing="1"">
			<tr>
				<td id="vlist" width="500">
				<table border='0' align='center' width='100%' cellpadding='1' cellspacing='0' class='tbframe'>
				<tr>
					<th></td>
					<th align="left">Vulnerability</td>
					<th align="left">Description</td>
					<th align="left">Type</td>
				</tr>
				</table>
				</td>
			</tr>
			</table>
			</td>
			<td align="center" valign="top"><span style="cursor: pointer" onclick="addVulner('finding', 'vuln__', 'vselect');"><img src="images/button_right.png" border="0"></span><br><br>
			<span style="cursor: pointer" onclick="removeVulner('finding', 'vuln_-', 'vselect');"><img src="images/button_left.png" border="0"></span></td>
			<td width="250" valign="top">
			<table width="100%" border="0" cellpadding="1" cellspacing="1">
			<tr>
				<td id="vselect" width="250">
				<table border='0' align='center' width='250' cellpadding='1' cellspacing='1' class='tbframe'>
				<tr>
					<th width="20"></td>
					<th width="130" align="left">Vulnerability</td>
					<th width="100" align="left">Type</td>
				</tr>
				</table>
				</td>
			</tr>
			</table>
			</td>
		</tr>
		</table>
		</fieldset>
		</td>
	</tr>
	<tr>
		<td colspan="2">
		
		<fieldset style="border:1px solid #44637A; padding:5">
		<legend><b>Asset</b></legend>
		<table border="0" width="800" cellpadding="1" cellspacing="1">
		<tr>
			<td colspan="2">
			<table border="0" cellpadding="1" cellspacing="1">
			<tr>
				<!--<td>&nbsp;Asset search:&nbsp;</td>
				
				<td><select name="asset_list" onchange="loadAsset('ajaxsearch.php');">
				{foreach from=$asset_list key=sid item=sname}
				<option value="{$sid}">{$sname}</option>
				{/foreach}
				</select> &nbsp;</td>
				<td><input type="text" name="asset_needle" value="" maxlength="20" size="20"> &nbsp;</td>-->
				
				<td>System:</td>
				<td><select name="system" onchange="return loadAssetList('ajaxsearch.php');">
					<option value="">--Any--</option>
					{foreach from=$system_list key=sid item=sname}
					{if $sid eq $system }
					<option value="{$sid}" selected>{$sname}</option>
					{else}
					<option value="{$sid}">{$sname}</option>
					{/if}
					{/foreach}
					</select>&nbsp;</td>
				<td>Asset Name:</td>
				<td><input type="text" name="asset_needle" value="" maxlength="10" size="10">&nbsp;</td>
				<!--
				<td>Network:</td>
				<td><select name="network">
					<option value="">--Any--</option>
					{foreach from=$network_list key=sid item=sname}
					{if $sid eq $network }
					<option value="{$sid}" selected>{$sname}</option>
					{else}
					<option value="{$sid}">{$sname}</option>
					{/if}
					{/foreach}
					</select>&nbsp;</td>
				<td>IP Address:</td>
				<td><input type="text" name="ip" value="" maxlength="20" size="20"> &nbsp;</td>
				<td>Port:</td>
				<td><input type="text" name="port" value="" maxlength="6" size="6"> &nbsp;</td>
				-->
				<td><span style="cursor: pointer" onclick="return loadAssetList('ajaxsearch.php');"><img src="images/button_search.png" border="0"></span></td>
			</tr>
			</table>
			</th>
		</tr>
		<tr>
			<td colspan="2" valign="top"><hr size="1" color="#cccccc"></td>
		</tr>
		<tr>
			<td width="200" align="center"><select name="asset_list" size="10" style="width: 190px;" onchange="loadAsset('ajaxsearch.php');">
				<option value="">--None--</option>
				{foreach from=$asset_list key=sid item=sname}
				<option value="{$sid}">{$sname}</option>
				{/foreach}
				</select></td>
			<td valign="top">
			<fieldset style="height:115;border:1px solid #44637A; padding:5">
			<legend><b>Asset Information</b></legend>
			<div id="assetarea"></div>
			</fieldset>
			</td>
		</tr>
		</table>
		</fieldset>
		</td>
	</tr>
	</table>
	</td>

</tr>
<tr>
	<td align="right"><input type="image" src="images/button_create.png" border="0">&nbsp;&nbsp;&nbsp;&nbsp;</td>
	<td align="left">&nbsp;&nbsp;&nbsp;&nbsp;<a href="findingdetail.php"><img src="images/button_reset.png" border="0"></a></td>
</tr>
</table>
</form>

{else}
<p>{$noright}</p>
{/if}

<p>&nbsp;</p>


{include file="footer.tpl"}

<script>
var theFloaters = new floaters();
//alert(document.body.Width);
theFloaters.addItem('tip','document.body.clientWidth','0','',0);
theFloaters.play();

//loadAsset('ajaxsearch.php');
</script>
