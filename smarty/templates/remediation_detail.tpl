<!-- ----------------------------------------------------------------------- -->
<!-- FILE    : remediation_detail.tpl                                        -->
<!-- AUTHOR  : Brian Gant                                                    -->
<!-- DATE    : 02/01/06                                                      -->
<!-- PURPOSE : establishes template for remediation_detail  page             -->
<!-- ----------------------------------------------------------------------- -->

<!-- ----------------------------------------------------------------------- -->
<!-- HEADER INCLUDE                                                          -->
<!-- ----------------------------------------------------------------------- -->

{include file="header.tpl" title="OVMS" name="Remediation Detail"}

{literal}
<script language="javascript">
<!--
function go(step) {
	document.finding.action.value = step;
	document.finding.submit();
}
-->
</script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/remediation_edit.js"></script>
{/literal}


<!-- ----------------------------------------------------------------------- -->
<!-- MAIN PAGE                                                               -->
<!-- ----------------------------------------------------------------------- -->


<!-- Heading Block -->
<table class="tbline">        
<tr>     
<td id="tbheading"><img src="images/contract.gif" class="expend_btn" />Finding Detail</td>
<td id="tbtime">{$now}</td>
</tr>    
</table>
<!-- End Heading Block -->

<br>

<!-- FINDING DETAIL TABLE -->
<table align="center" border="0" cellpadding="3" cellspacing="1" width="95%" class="tipframe">

	<!-- finding and asset row -->
	<tr>

	    <!-- finding information -->
	    <td width="50%" valign="top">

		<!-- FINDING TABLE -->
		<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
    		<th align="left" colspan="2">Finding Information - #{$finding.finding_id}</th>
		<tr><td><b>Finding Source:</b> ({$finding.source_nickname}) {$finding.source_name}</td></tr>
		<tr><td><b>Finding Status:</b> {$finding.finding_status}</td></tr>
		<tr><td><b>Finding Date:</b> {$finding.finding_date_created}</td></tr>
	        <tr><td><b>Scan Date:</b> {$finding.finding_date_discovered}</td></tr>
	    	</table> 
		<!-- FINDING TABLE -->

	    </td>

	    <!-- asset information -->
    	<td width="50%" valign="top">

		<!-- ASSET TABLE -->
	    	<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

	    	<th align="left" colspan="2">Asset Information</th>
		<tr><td><b>Asset Owner:</b> ({$finding.system_nickname}) {$finding.system_name}</td></tr>
		<tr><td><b>Asset Name:</b> {* RESTRICT VIEW BASED ON ROLE *}
					{if $view_asset_name eq '1'}
					{if $finding.asset_name eq "NULL"}<i>(none given)</i>
					{else}{$finding.asset_name}{/if}
					{else}<i>(restricted)</i>{/if}</td></tr>


		<tr><td><b>Known Address(es):</b>

				{* RESTRICT VIEW BASED ON ROLE *}
				{if $view_asset_addresses eq '1'}

					{section name=row loop=$asset_addresses}
						({$asset_addresses[row].network_nickname}) 
						{if $asset_addresses[row].address_ip   eq ""}<i>(none given)</i> :{else}{$asset_addresses[row].address_ip} :{/if}
						{if $asset_addresses[row].address_port eq ""}<i>(none given)</i>{else}{$asset_addresses[row].address_port}{/if}
					{/section}

				{else}

					<i>(restricted)</i>

				{/if}


				</td>
			</tr>

    		<tr><td><b>Product Information:</b>

				{if $product.prod_id eq ""}<i>(none given)</i>
				{else}{$product.prod_vendor} {$product.prod_name} {$product.prod_version}
				{/if}

				</td>
			</tr>

    	</table> <!-- ASSET TABLE -->

    	</td>

	</tr>

	{* RESTRICT VIEW BY ROLE *}
	{if $view_finding_instance_data eq '1'}
	<tr> <!-- INSTANCE SPECIFIC DATA ROW -->

   		<td colspan="2" width="90%">
		
		<!-- INSTANCE DATA TABLE -->
	    <table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

        	<th align="left">Instance Specific Information</th>
	        <tr><td>{if $finding.finding_data eq ""}<i>(none given)</i>{else}{$finding.finding_data}{/if}</td></tr>

    	</table> <!-- INSTANCE DATA TABLE -->

	    </td>

	</tr>
	{/if}

</table> <!-- FINDING TABLE -->

<br>

<!-- ------------------------------------------------------------------------ -->

<!-- VULNERABILITY DETAIL LINE -->

<!-- Heading Block -->
<table class="tbline">
<tr>
<td id="tbheading"><img src="images/contract.gif" class="expend_btn" />Vulnerability Detail</td>
</tr>
</table>
<!-- End Heading Block -->

<br>


<!-- VULNERABILITY DETAIL TABLE -->
<table border="0" cellpadding="3" cellspacing="1" width="95%" align="center" class="tipframe">

	<th align='left'>Vulnerability Information</th>

	<!-- VULNERABILITY ROW(S) -->
	{section name=row loop=$vulnerabilities step='-1'}
	<tr>

    	<td colspan="2" width="90%">

		<!-- VULERABILITIES TABLE -->
	    <table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

    	    <tr><td><b>Vulnerability ID:</b> {$vulnerabilities[row].vuln_type}-{$vulnerabilities[row].vuln_seq}</td></tr>

    	    <tr><td><b>Primary Description:</b> {$vulnerabilities[row].vuln_desc_primary}</td></tr>

        	<tr>
				<td><b>Secondary Description:</b> 
				{if $vulnerabilities[row].vuln_desc_secondary eq "0"}<i>(none given)</i>
				{else}{$vulnerabilities[row].vuln_desc_secondary}
				{/if}
				</td>
			</tr>

	    </table> <!-- VULERABILITIES TABLE -->

    	</td>

	</tr>
	{/section}


</table> <!-- VULNERABILITY TABLE -->

<br>

{* RETURN TO THE SUMAMRY LIST *}
<form action='remediation.php' method='POST'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

<input type='hidden' name='form_action' value='Return to Summary List'>
<input type='image' src='images/button_back.png' name='form_action' value='Return to Summary List'>
</form>

<!-- ------------------------------------------------------------------------ -->

<!-- REMEDIATION DETAIL LINE -->

<!-- Heading Block -->
<table class="tbline">
<tr>
<td id="tbheading"><img src="images/contract.gif" class="expend_btn" />Remediation Detail</td>
</tr>
</table>
<!-- End Heading Block -->

<br>


<!-- REMEDIATION TABLE -->
<table border="0" cellpadding="3" cellspacing="1" width="95%" align="center" class="tipframe">

   	<th align="left" colspan='2'>Remediation Information - #{$remediation_id}</th>


	<tr> <!-- REMEDIATION INFORMATION ROW -->

		<td width='50%' valign='top'>
		<table border="0" cellpadding="3" cellspacing="1" width="100%" class="tipframe">

			<tr>
				<td colspan='2'>
				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='remediation_owner'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

    			<input type='hidden' name='filter_source'          value='{$filter_source}'>
    			<input type='hidden' name='filter_system'          value='{$filter_system}'>
    			<input type='hidden' name='filter_status'          value='{$filter_status}'>
    			<input type='hidden' name='filter_type'            value='{$filter_type}'>
    
    			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
    			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
    			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
    			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>
    
    			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
    			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>
					<b>Responsible System:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_action_owner eq '1'}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'> 

					{/if}
					{/if}

					<span>({$remediation.system_nickname}) {$remediation.system_name}</span>
				</form>
				</td>

			</tr>

	   	    <tr>

				<td align='left' width='50%'>
				
    				<form action='remediation_modify.php' method='POST'>
    				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
    				<input type='hidden' name='root_comment'   value='{$root_comment}'>
    				<input type='hidden' name='target' 		   value='remediation_type'>
    				<input type='hidden' name='action'         value='update'>
    				<input type='hidden' name='validated'      value='no'>
    				<input type='hidden' name='approved'       value='no'>
    
    			<input type='hidden' name='filter_source'          value='{$filter_source}'>
    			<input type='hidden' name='filter_system'          value='{$filter_system}'>
    			<input type='hidden' name='filter_status'          value='{$filter_status}'>
    			<input type='hidden' name='filter_type'            value='{$filter_type}'>
    
    			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
    			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
    			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
    			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>
    
    			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
    			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>
					<b>Type:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_type eq '1'}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
 						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{/if}

					<span>{$remediation.poam_type}</span>
                    </form>
				</td>

				<td align='left' width='50%'>
					<b>Status:</b> {$remediation.poam_status}
				</td>

			</tr>

			<tr>

				{* RESTRICT BY ROLE *}
				{if $generate_raf eq '1'}

					{* CHECK THAT CMEASURE AND THREAT LEVEL ARE SET *}
					{if $threat_level neq 'NONE' && $cmeasure_effectiveness neq 'NONE'}

						<form action='raf.php' method='POST' target='_blank'>
							<input type='hidden' name='poam_id'     value='{$remediation_id}'>
						<td colspan='2'>
							<input type='hidden' name='form_action' value='Generate RAF'>
							<input type='image' src='images/button_generate_raf.png' name='form_action' value='Generate RAF'>
						</td>
						</form>

					{else}

						<td colspan='2'><i>(Threat Level and Countermeasure Effectiveness must be set to generate a RAF)</i></td>

					{/if}

				{else}

					<td colspan='2'>&nbsp;</td>

				{/if}

			</tr>


		</table>
		</td>
	
		{* RIGHT HAND TABLE *}
		<td width='50%' valign='top'>
		<table border="0" cellpadding="3" cellspacing="1" width="100%%" class="tipframe">

			<tr><td><b>Created By: </b> {$remediation.created_by} ({$remediation.poam_date_created})</td></tr>
			<tr><td><b>Modified By: </b> {$remediation.modified_by} ({$remediation.poam_date_modified})</td></tr>

			<tr>
				<td>
    				<form action='remediation_modify.php' method='POST'>
    				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
    				<input type='hidden' name='root_comment'   value='{$root_comment}'>
    				<input type='hidden' name='target' 		   value='previous_audits'>
    				<input type='hidden' name='action'         value='update'>
    				<input type='hidden' name='validated'      value='no'>
    				<input type='hidden' name='approved'       value='no'>
    
    			<input type='hidden' name='filter_source'          value='{$filter_source}'>
    			<input type='hidden' name='filter_system'          value='{$filter_system}'>
    			<input type='hidden' name='filter_status'          value='{$filter_status}'>
    			<input type='hidden' name='filter_type'            value='{$filter_type}'>
    
    			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
    			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
    			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
    			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>
    
    			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
    			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>
					<b>Previous Audits: </b>

					{* RESTRICT BASED ON STATUS AND ROLE *}
					{if $modify_previous_audits eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_previous_audits}</span>
				</form>
				</td>
			</tr>

		</table>
		</td>

	</tr>

	{* RESTRICT VIEW BY ROLE *}
	{if $view_blscr eq '1'}
	<tr> <!-- BLSCR -->

	    <td colspan="2">

		<!-- BLSCR TABLE -->
   		<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

       		<th align="left" >Baseline Security Requirements</th>

			{if $blscr.blscr_number eq ""}<tr><td><i>(none given)</i></td></tr>
			{else}

			{* UPDATE BUTTON *}
		        <tr>
    				<td>
				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='blscr_number'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

				<input type='hidden' name='filter_source'          value='{$filter_source}'>
				<input type='hidden' name='filter_system'          value='{$filter_system}'>
				<input type='hidden' name='filter_status'          value='{$filter_status}'>
				<input type='hidden' name='filter_type'            value='{$filter_type}'>

				<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
				<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
				<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
				<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

				<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
				<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					
					<b>Number:</b>
					{if $modify_previous_audits eq '1'}
					{if $remediation_status eq 'OPEN'}
						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>
					{/if}
					{/if}
					<span> {$blscr.blscr_number}</span>
			     </form>
				</td>

			</tr>


				<tr><td><b>Class:</b> {$blscr.blscr_class}</td></tr>
				<tr><td><b>Subclass: </b> {$blscr.blscr_subclass}</td></tr>
	        	<tr><td><b>Family: </b> {$blscr.blscr_family}</td></tr>
		        <tr><td><b>Control: </b> {$blscr.blscr_control}</td></tr>
		        <tr><td><b>Guidance: </b> {$blscr.blscr_guidance}</td></tr>
	    	    <tr><td><b>Low: </b> {$blscr.blscr_low}</td></tr>
				<tr><td><b>Moderate: </b> {$blscr.blscr_moderate}</td></tr>
				<tr><td><b>High: </b> {$blscr.blscr_high}</td></tr>

		        <tr>
					<td><b>Enhancements: </b> 	
						{if $blscr.blscr_enhancements eq '.'}<i>(none given)</i>
						{else}{$blscr.blscr_enhancements}
						{/if}
					</td>
				</tr>

	    	    <tr>
					<td><b>Supplement: </b> 	
						{if $blscr.blscr_supplement eq '.'}<i>(none given)</i>
						{else}{$blscr.blscr_supplement}
						{/if}
					</td>
				</tr>

			{/if}


			{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
			{if $modify_blscr eq '1'}
			{*if $remediation_type  neq 'NONE'*}
			{*if $remediation_status eq 'CLOSED'*}


			{/if}
			{*/if*}
			{*/if*}

	    	</table> <!-- BLSCR -->

	    </td>

	</tr>
	{/if}


	{* RESTRICT VIEW BY ROLE *}
	{if $view_threat eq '1'}
	<tr> <!-- THREATS ROW -->

	    <td colspan="2">

		<!-- THREATS TABLE -->
    	<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

        	<th align='left'>Threat Information</th>

			<tr>
				<td>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='threat_level'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Level:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_threat_level eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_threat_level}</span>

				</form>
				</td>

			</tr>

	        <tr>
				<td>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='threat_source'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>
				
			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>


					<b>Source:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_threat_source eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_threat_source}</span>

				</form>
				</td>

			</tr>

			<tr>
				<td>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='threat_justification'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Justification:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_threat_justification eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_threat_justification}</span>

				</form>
				</td>

			</tr>

    	</table> <!-- THREATS TABLE -->

	    </td>

	</tr>
	{/if}

	{* RESTRICT VIEW BY ROLE *}
	{if $view_cmeasure eq '1'}
	<tr> <!-- COUNTERMEASURES ROW -->

	    <td colspan="2">

		<!-- COUNTERMEASURE TABLE -->
    	<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

        	<th align="left" colspan="2">Countermeasure Information</th>

			<tr>
				<td>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='cmeasure_effectiveness'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Effectiveness:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_cmeasure_effectiveness eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_cmeasure_effectiveness}</span>

				</form>
				</td>

			</tr>

	        <tr>
				<td>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='cmeasure'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Countermeasure:</b> 


					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_cmeasure eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_cmeasure}</span>

				</form>
				</td>

			</tr>

			<tr>
				<td>
				
				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='cmeasure_justification'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Justification:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_cmeasure_justification eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_cmeasure_justification}</span>

				</form>
				</td>

			</tr>

    	</table> <!-- COUNTERMEASURE TABLE -->

	    </td>

	</tr>
	{/if}


	{* RESTRICT VIEW BY ROLE *}
	{if $view_mitigation eq '1'}
	<tr> <!-- MITIGATION STRATEGY -->

		<td colspan='2'>
		<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

			<th align='left' colspan='2'>Mitigation Strategy</th>

			<tr>
				<td colspan='2'>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='action_suggested'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

				<input type='hidden' name='filter_source'          value='{$filter_source}'>
				<input type='hidden' name='filter_system'          value='{$filter_system}'>
				<input type='hidden' name='filter_status'          value='{$filter_status}'>
				<input type='hidden' name='filter_type'            value='{$filter_type}'>

				<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
				<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
				<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
				<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

				<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
				<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Recommendation:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_mitigation_recommendation eq '1'}
					{* if $remediation_type   eq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_action_suggested}</span>

				</form>
				</td>

			</tr>


	    	<tr>
				<td colspan='2'>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='action_planned'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Course of Action:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_mitigation_course_of_action eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						{* COURSE OF ACTON CAN ONLY BE CHANGED FOR A CAP *}
						{*if $remediation_type eq 'CAP'*}

							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

						{*/if*}

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_action_planned}</span>

				</form>
				</td>

			</tr>


	    	<tr>
				<td colspan='2'>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='action_resources'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Resources:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_mitigation_resources eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_action_resources}</span>

				</form>
				</td>

			</tr>


			<tr>
				<td width='50%'>

				<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='root_comment'   value='{$root_comment}'>
				<input type='hidden' name='target' 		   value='action_date_est'>
				<input type='hidden' name='action'         value='update'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>Estimated Completion Date:</b> 

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_mitigation_completion_date eq '1'}
					{* if $remediation_type  neq 'NONE' *}
					{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{* /if *}
					{/if}

					<span>{$remediation.poam_action_date_est}</span>

				</form>
				</td>


				<td width='50%'>
					<b>Actual Completion Date:</b> 
					{if $remediation.poam_action_date_actual neq ""}{$remediation.poam_action_date_actual}
					{else}<i>(action not yet completed)</i>
					{/if}
				</td>

			</tr>
			{if $num_comments_est > 0}
			
			<tr><th align="left" colspan="2">Comments For Estimated Completion Date Changed <i>({$num_comments_est} total)</i></th></tr>
			{section name=row loop=$comments_est}	
			<tr>
	    		<td colspan="2" width="90%">
	    		<table border="0" cellpadding="3" cellspacing="1" width="100%">
					<tr>
					<td>
			    	<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
						<th align='left'>{$comments_est[row].comment_topic}</th>
						<tr><td colspan='2'>{$comments_est[row].comment_body}</td></tr>
						<tr><td align='right'><i>{$comments_est[row].comment_date} by {$comments_est[row].user_name}</i></td></tr>
					</table>
					</td>
					</tr>
				</table>
				</td>
			</tr>
			{/section}
			
			{/if}
		</table>
		</td>

	</tr>
	{/if}

 	<tr>

		<td colspan='2'>
		<table border="0" cellpadding="3" cellspacing="1" width="100%%" class="tipframe">

			<th align='left'>Approval</th>
			<tr>

				<td colspan='2'>
			<form action='remediation_modify.php' method='POST'>
			<input type='hidden' name='remediation_id' value='{$remediation_id}'>
			<input type='hidden' name='root_comment'   value='{$root_comment}'>
			<input type='hidden' name='target' 		   value='action_approval'>
			<input type='hidden' name='action'         value='update'>
			<input type='hidden' name='validated'      value='no'>
			<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

					<b>SSO Approval:</b>

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_mitigation_sso_approval eq '1'}
					{if $remediation_type  neq 'NONE' && $is_completed eq 'yes'}
					{if $remediation_status eq 'OPEN' || $remediation_status eq 'EN' || $remediation_status eq 'EO'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

					{/if}
					{/if}
					{/if}

					<span>{$remediation.poam_action_status}</span>
			</form>

				</td>

			</tr>
			{if $num_comments_sso > 0}
			
			<tr><th align="left" colspan="2">Comments From SSO <i>({$num_comments_sso} total)</i></th></tr>
			{section name=row loop=$comments_sso}
			<tr>
	    		<td colspan="2" width="90%">
	    		<table border="0" cellpadding="3" cellspacing="1" width="100%">
					<tr>
					<td>
			    	<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
						<th align='left'>{$comments_sso[row].comment_topic}</th>
						<tr><td colspan='2'>{$comments_sso[row].comment_body}</td></tr>
						<tr><td align='right'><i>{$comments_sso[row].comment_date} by {$comments_sso[row].user_name}</i></td></tr>
					</table>
					</td>
					</tr>
				</table>
				</td>
			</tr>
			{/section}
			
			{/if}
		</table>
		</td>

	</tr>


</table> <!-- REMEDIATION TABLE -->

<br>

<!-- ------------------------------------------------------------------------ -->


{* NO REAL NEED TO SHOW UNTIL EN, EO, EP, ES or CLOSED *}
{if $view_evidence eq '1'}
{if $remediation_status neq 'OPEN'}

<!-- EVIDENCE LINE -->

<!-- Heading Block -->
<table class="tbline">
<tr>
<td id="tbheading"><img src="images/contract.gif" class="expend_btn" />Evidence Detail</td>
</tr>
</table>
<!-- End Heading Block -->

	<br>

	<!-- EVIDENCE TABLE -->
	<table border="0" cellpadding="3" cellspacing="1" width="100%%" class="tipframe">

		<th align='left'>Evidence Submissions <i>({$num_evidence} total)</i></th>
	
		{* loop through the evidence *}
		{if $num_evidence gt 0}

			{section name=row loop=$all_evidence}

			{* DO NOT SHOW BAD EVIDENCE AT STATUS ES *}
			{if $remediation_status eq 'ES' && $all_evidence[row].ev_sso_evaluation neq 'APPROVED' || 
				$remediation_status eq 'ES' && $all_evidence[row].ev_fsa_evaluation neq 'APPROVED'}
			{else}

			<tr>

				{* EVIDENCE TABLE *}
				<td colspan='2' width='100%'>
				<table border='0' cellpadding='3' cellspacing='1' class='tipframe' width='100%'>

					<th align='left'>Submitted: {$all_evidence[row].ev_date_submitted} by {$all_evidence[row].submitted_by}</th>

					<tr><td><b>Evidence:</b> <!--a href='{$all_evidence[row].ev_submission}'>{$all_evidence[row].ev_submission}</a> <br-->
						<a href="javascript:void(0)" onClick="window.open('{$all_evidence[row].ev_submission}', 'evidence_window', config='resizable=yes,menubar=no,scrollbars=yes')">{$all_evidence[row].ev_submission}</a>
					</td></tr>


					{* SSO EVALUATION *}
					<tr>
                        <td>
						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_evidence_sso_approval eq '1'}
						{if $remediation_status eq 'EP'}

							{* ONLY ALLOW APPROVAL IF NONE EXISTS *}
							{if $all_evidence[row].ev_sso_evaluation eq 'NONE'}

								<form action='remediation_modify.php' method='POST'>
								<input type='hidden' name='remediation_id' value='{$remediation_id}'>
								<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
								<input type='hidden' name='root_comment'   value='{$root_comment}'>
								<input type='hidden' name='target'         value='evidence'>
								<input type='hidden' name='action'         value='sso_evaluate'>
								<input type='hidden' name='validated'      value='no'>
								<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

		
								<b>SSO Evaluation:</b> 
									<input type='hidden' name='form_action' value='Evaluate'>
									<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
									<span>{$all_evidence[row].ev_sso_evaluation} </span>

								</form>
                            </td>
							{else}

								<td><b>SSO Evaluation:</b> {$all_evidence[row].ev_sso_evaluation}</td>

							{/if}

						{else}

							<td><b>SSO Evaluation:</b> {$all_evidence[row].ev_sso_evaluation}</td>

						{/if}
						{else}

							<td><b>SSO Evaluation:</b> {$all_evidence[row].ev_sso_evaluation}</td>

						{/if}

					</tr>

					{* FSA EVALUATION *}
					<tr>
                        <td>
						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_evidence_fsa_approval eq '1'}
						{if $remediation_status eq 'EP'}

							{* FSA ONLY NEEDS TO APPROVE IF THE SSO APPROVES AND NO EXISTING EVALUATION OR EXCLUSION*}
							{if $all_evidence[row].ev_sso_evaluation eq 'APPROVED' && $all_evidence[row].ev_fsa_evaluation eq 'NONE'}

								<form action='remediation_modify.php' method='POST'>
								<input type='hidden' name='remediation_id' value='{$remediation_id}'>
								<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
								<input type='hidden' name='root_comment'   value='{$root_comment}'>
								<input type='hidden' name='target'         value='evidence'>
								<input type='hidden' name='action'         value='fsa_evaluate'>
								<input type='hidden' name='validated'      value='no'>
								<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

								<b>FSA Evaluation:</b> 
									<input type='hidden' name='form_action' value='Evaluate'>
									<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
									<span>{$all_evidence[row].ev_fsa_evaluation}</span>

								</form>
                            </td>
							{else}

								<td><b>FSA Evaluation:</b> {$all_evidence[row].ev_fsa_evaluation} </td>

							{/if}

						{else}

							<td><b>FSA Evaluation:</b> {$all_evidence[row].ev_fsa_evaluation}</td>

						{/if}
						{else}

							<td><b>FSA Evaluation:</b> {$all_evidence[row].ev_fsa_evaluation}</td>

						{/if}

					</tr>


					{* IVV EVALUATION *}
					<tr>
                    <td>
						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_evidence_ivv_approval eq '1'}
						{if $remediation_status eq 'ES'}

							{* IVV ONLY NEEDS TO APPROVE IF THE SSO AND FSA APPROVE AND NO EXISTING EVALUATION OR EXCLUSION*}
							{if $all_evidence[row].ev_sso_evaluation eq 'APPROVED' && $all_evidence[row].ev_fsa_evaluation eq 'APPROVED'}

								<form action='remediation_modify.php' method='POST'>
								<input type='hidden' name='remediation_id' value='{$remediation_id}'>
								<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
								<input type='hidden' name='root_comment'   value='{$root_comment}'>
								<input type='hidden' name='target'         value='evidence'>
								<input type='hidden' name='action'         value='ivv_evaluate'>
								<input type='hidden' name='validated'      value='no'>
								<input type='hidden' name='approved'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>


								<b>IVV Evaluation:</b> 
									<input type='hidden' name='form_action' value='Evaluate'>
									<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
									<span>{$all_evidence[row].ev_ivv_evaluation}</span>

								</form>
                            </td>
							{else}

								<td><b>IVV Evaluation:</b> {$all_evidence[row].ev_ivv_evaluation} </td>

							{/if}

						{else}

							<td><b>IVV Evaluation:</b> {$all_evidence[row].ev_ivv_evaluation}</td>

						{/if}
						{else}

							<td><b>IVV Evaluation:</b> {$all_evidence[row].ev_ivv_evaluation}</td>

						{/if}

				</table>
				</td>

			</tr>

			{/if}

			{/section}

		{/if}


		{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
		{if $modify_evidence_upload eq '1'}
		{if $remediation_status eq 'EN' || $remediation_status eq 'EO'}

			<tr align='left'>
					<td>
				<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='remediation_id' value='{$remediation_id}'>
						<input type='hidden' name='root_comment'   value='{$root_comment}'>
						<input type='hidden' name='target'         value='evidence'>
						<input type='hidden' name='action'         value='add'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>
						<input type='hidden' name='uploaded'       value='no'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>
						<input type='hidden' name='form_action' value='Submit Evidence'>
						<input type='image' src='images/button_submit_evidence.png' name='form_action'    value='Submit Evidence'>
				</form>
					</td>
			</tr>

		{/if}
		{/if}

	</table>

	<br>

{/if}
{/if}

<!-- ------------------------------------------------------------------------ -->


{* COMMENT RESTRICTIONS HERE *}
{if $view_comments eq '1'}

	<!-- COMMENT LINE -->

<!-- Heading Block -->
<table class="tbline">
<tr>
<td id="tbheading"><img src="images/contract.gif" class="expend_btn" />Finding Audit Log</td>
</tr>
</table>
<!-- End Heading Block -->

	<br>

	<!-- COMMENT TABLE -->
	<table border="0" cellpadding="3" cellspacing="1" width="100%%" class="tipframe">

	   	<th align="left">Logs <i>({$num_logs} total)</i></th>

		{* loop through the logs *}
		{if $num_logs gt "0"}

			{section name=row loop=$logs}

			<!-- comments row -->
			<tr>

				<!-- COMMENTS TABLE -->
	    		<td colspan="2" width="90%">
	    		<table border="0" cellpadding="3" cellspacing="1" width="100%">

					<tr>
					<td>
			    	<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
						<th align='left'>{$logs[row].event}</th>
						<tr><td colspan='2'>{$logs[row].description}</td></tr>
						<tr><td align='right'><i>{$logs[row].time} by {$logs[row].user_name}</i></td></tr>
					</table>
					</td>
					</tr>
<!--
					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $remediation_status eq 'OPEN' || $remediation_status eq 'EN' || $remediation_status eq 'EO' || $remediation_status eq 'EP'}

						<tr> 
                            <td align='left'>
							<form action='remediation_modify.php' method='POST'>
									<input type='hidden' name='remediation_id' value='{$remediation_id}'>
									<input type='hidden' name='root_comment'   value='{$comments[row].comment_id}'>
									<input type='hidden' name='target' 		   value='comment'>
									<input type='hidden' name='action'         value='respond'>
									<input type='hidden' name='validated'      value='no'>
									<input type='hidden' name='approved'       value='no'>

									<input type='hidden' name='form_action' value='Respond'>
									<input type='image' src='images/button_respond.png' name='form_action' value='Respond'>
							</form>
                            </td>
						</tr>

					{/if}
-->
				</table>
				</td>

			</tr>

			{/section}

		{/if}

	</table> <!-- COMMENT TABLE -->

	<br>

{/if}

<table>
<tr><td>
{* RETURN TO THE SUMAMRY LIST *}
<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='action'         value='add'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>
    <input type='hidden' name='target'         value='save_poam'>
    <input type='hidden' name='remediation_id' value='{$remediation_id}'>
    <input type='hidden' name='form_action' value=''>
    <input type='image' src='images/button_submit.png' value='Save Changes'>
</form>
{* END COMMENT RESTRICTIONS *}
</td>
<td>
{* RETURN TO THE SUMAMRY LIST *}
<form action='remediation.php' method='POST'>

			<input type='hidden' name='filter_source'          value='{$filter_source}'>
			<input type='hidden' name='filter_system'          value='{$filter_system}'>
			<input type='hidden' name='filter_status'          value='{$filter_status}'>
			<input type='hidden' name='filter_type'            value='{$filter_type}'>

			<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
			<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
			<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
			<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

			<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
			<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>

<input type='hidden' name='form_action' value='Return to Summary List'>
<input type='image' src='images/button_back.png' name='form_action' value='Return to Summary List'>
</form>
</td></tr>
</table>
<!-- ----------------------------------------------------------------------- -->
<!-- FOOTER INCLUDE                                                          -->
<!-- ----------------------------------------------------------------------- -->

{include file="footer.tpl"}

<br>
