<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}
<script language="javascript">

// Validates all fields are filled out correctly
function validate_input() {

	// Check to ensure plugin, system, source, and network are all selected
	if( 	
		finding_upload.system.selectedIndex 	== 0 	&& 
		finding_upload.source.selectedIndex 	== 0 	&& 
		finding_upload.network.selectedIndex 	== 0 	&& 
		finding_upload.plugin.selectedIndex 	== 0	
		)
	{
		alert("Please ensure Plugin, System, Source and Network are all selected.");
		return false;
    }

	// Check to ensure a file has been selected for upload
	if(finding_upload.upload_file.value == "") 
	{
		alert("Please select a file to upload.");
		return false;
    }
  
  // Set the value of the function to true
  finding_upload.submitted.value = true;

  // All validation passed, continue with submit
  finding_upload.submit();
  }

</script>
{/literal}

<!-- ---------------------------------------------------------------------- -->
<!-- MAIN PAGE DISPLAY                                                      -->
<!-- ---------------------------------------------------------------------- -->

<br>

{if $upload_right eq 1}

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Upload Scan Results</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- build our form -->

<form name="finding_upload" action="finding_upload.php" enctype="multipart/form-data" method="POST">
<table width="95%" align="center">
	<tr>
    	<td>
			<!-- End Finding Upload Scan Results Table -->
			<table align="left" border="0" cellpadding="5" class="tipframe">
				<th align="left" colspan="2">Finding Upload</th>
				<!-- display the plugins row -->
				<tr>
					<td align="right"><b>Plugin:<b></td>
					<td align="left">
						<select name="plugin">
						<option value="0">--- Select Plugin ---</option>
						{section name=row loop=$plugins}
						<option value="{$plugins[row].plugin_nickname}">
                        ({$plugins[row].plugin_nickname}){$plugins[row].plugin_name}
                        </option>
						{/section}
						</select>
					</td>
				</tr>
				<!-- display the finding sources row -->
				<tr>
					<td align="right"><b>Finding Source:<b></td>
					<td align="left">
						<select name="source">
						<option value="0">--- Select Finding Source ---</option>
						{section name=row loop=$finding_sources}
						<option value="{$finding_sources[row].source_id}">
            		    ({$finding_sources[row].source_nickname}) {$finding_sources[row].source_name}
            		    </option>
						{/section}
						</select>
					</td>
				</tr>
				<!-- display the systems row -->
				<tr>
					<td align="right"><b>System:<b></td>
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
					<td align="right"><b>Network:<b></td>
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
					<td align="right"><b>Results File:<b></td>
					<td><input type="file" name="upload_file"></td>
				</tr>
				<tr align="right">
	    			<input type="hidden" name="submitted"/>
    				<td colspan="2"><input type="button" name="submit_button" value="Submit" onClick="javascript:validate_input();"></td> 
				<tr>
			</table>
			<!-- End Finding Upload Scan Results Table -->
		</td>
	</tr>
</table>
</form>
<br>

{else}

<div class="noright">{$noright}</div>

{/if}

<!-- ---------------------------------------------------------------------- -->
<!-- FOOTER TEMPLATE                                                        -->
<!-- ---------------------------------------------------------------------- -->

{include file="footer.tpl"}
