<!-- PURPOSE : provides template for the finding_upload page                -->

<!-- ---------------------------------------------------------------------- -->
<!-- HEADER TEMPLATE INCLUDE                                                -->
<!-- ---------------------------------------------------------------------- -->

{include file="header.tpl" title="OVMS" name="Finding Upload"}

{literal}
<script language="javascript">

function validate_input() {

  //
  // Make sure all necessary form fields have been set by the user 
  // - if necessary (nessus, appdetective, shadowscan)
  //
  if((
      (finding_upload.system.value  != "0" &&
       finding_upload.source.value  != "0" &&
       finding_upload.network.value != "0"
       )
      &&
      (finding_upload.plugin.value == "Nessus" ||
       finding_upload.plugin.value == "AppDetective" ||
       finding_upload.plugin.value == "ShadowScan"
       )
      )
      || 
      (finding_upload.plugin.value == "BLSCR" ||
       finding_upload.plugin.value == "NVD Products" ||
       finding_upload.plugin.value == "NVD List" ||
       finding_upload.plugin.value == "ManualList" ||
       finding_upload.plugin.value == "Inventory"
       )
     ){
    // ok - pass through
    }
  else {
    alert("Please ensure Plugin, System, Source and Network are all selected.");
    return false;
    }

  if(finding_upload.upload_file.value == "") {
    alert("Please select a file to upload.");
    return false;
    }
  
  finding_upload.submitted.value = true;
  finding_upload.submit();
  }

</script>
{/literal}

<!-- ---------------------------------------------------------------------- -->
<!-- MAIN PAGE DISPLAY                                                      -->
<!-- ---------------------------------------------------------------------- -->

<!-- Heading Block -->
<table class="tbline">              
<tr>
 <td id="tbheading">Upload Scan Results</td>
 <td id="tbtime">{$now}</td>
</tr>        
</table>
<!-- End Heading Block -->

<br>

<!-- build our form -->

<form name="finding_upload" action="finding_upload.php" enctype="multipart/form-data" method="POST">
<table border="0" cellpadding="3" cellspacing="0" class="tbframe">

	<!-- display the plugins row -->
	<tr>
		<td align="center">Plugin:</td>
		<td align="left">
			<select name="plugin">
				<option value="0">--- Select Plugin ---</option>
				{section name=row loop=$plugins}
				<option value="{$plugins[row].plugin_nickname}">({$plugins[row].plugin_nickname}) {$plugins[row].plugin_name}</option>
				{/section}
			</select>
		</td>
	</tr>

	<!-- display the finding sources row -->
	<tr>
		<td align="center">Finding Source:</td>
		<td align="left">
			<select name="source">
				<option value="0">--- Select Finding Source ---</option>
				{section name=row loop=$finding_sources}
				<option value="{$finding_sources[row].source_id}">({$finding_sources[row].source_nickname}) {$finding_sources[row].source_name}</option>
				{/section}
			</select>
		</td>
	</tr>

	<!-- display the systems row -->
	<tr>
		<td align="center">System:</td>
		<td align="left">
			<select name="system">
				<option value="0">--- Select System ---</option>
				{section name=row loop=$systems}
				<option value="{$systems[row].system_id}">({$systems[row].system_nickname}) {$systems[row].system_name}</option>
				{/section}
			</select>
		</td>
	</tr>

	<!-- display the networks row -->
	<tr>
		<td align="center">Network:</td>
		<td align="left">
			<select name="network">
				<option value="0">--- Select Network ---</option>
				{section name=row loop=$networks}
				<option value="{$networks[row].network_id}">({$networks[row].network_nickname}) {$networks[row].network_name}</option>
				{/section}
			</select>
		</td>
	</tr>

	<!-- display the scan results upload row -->
	<tr>
		<td align="center">Results File:</td>
		<td><input type="file" name="upload_file"></td>
	</tr>

	<tr align="right">
{*
** input type="submit" does not fail if validation returns false - can't use it
<td colspan="2"><input type="submit" value="Submit" name="submitted" onClick="javascript:validate_input();"></td>
*}
    {* need a hidden 'submitted' field for the target php to check *}
    <input type="hidden" name="submitted"/>
      
		<td colspan="2"><input type="button" name="submit_button" value="Submit" onClick="javascript:validate_input();"></td> 
	<tr>
</table>
</form>
<br>


<!-- ---------------------------------------------------------------------- -->
<!-- FOOTER TEMPLATE                                                        -->
<!-- ---------------------------------------------------------------------- -->

{include file="footer.tpl"}
