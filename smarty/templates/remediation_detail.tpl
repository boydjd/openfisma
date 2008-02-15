<!-- ----------------------------------------------------------------------- -->
<!-- FILE    : remediation_detail.tpl                                        -->
<!-- AUTHOR  : Brian Gant                                                    -->
<!-- DATE    : 02/01/06                                                      -->
<!-- PURPOSE : establishes template for remediation_detail  page             -->
<!-- ----------------------------------------------------------------------- -->

<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

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

{if $view_right eq 1}

<br>
<table width="95%" border="0" align="center">
<tr>
<td>
<table border="0" align="left">
	<tr>
		<td>
			<!-- SAVE MODIFICATIONS TO REMEDIATION -->
			<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='action'         value='add'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>
				<input type='hidden' name='target'         value='save_poam'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='form_action' value=''>
				<input type='submit' title='Save or Submit' value="Save" style="cursor: pointer;">
			</form>
		</td>
		<td>
			<!-- RETURN TO THE SUMAMRY LIST -->
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
				<input name="button" type="submit" id="button" value="Go Back" style="cursor: pointer;">
			</form>
		</td>
	</tr>
</table>
</td>
</tr>
</table>

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Finding Description</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- FINDING DETAIL TABLE -->
<table align="center" border="0" cellpadding="3" cellspacing="1" width="95%">

	<!-- finding and asset row -->
	<tr>

	    <!-- finding information -->
	    <td width="50%" valign="top">

			<!-- FINDING TABLE -->
			<table border="0" cellpadding="5" cellspacing="1" class="tipframe" width="100%">
				<!--<th align="left" colspan="2">Finding Information - #{$finding.finding_id}</th> -->
				<th align="left" colspan="2">Finding Information</th>
				<tr><td><b>Finding ID:</b> {$remediation_id}</td></tr>
				<tr><td><b>Date Opened:</b> {$finding.finding_date_created}</td></tr>
				<tr><td><b>Finding Source:</b> ({$finding.source_nickname}) {$finding.source_name}</td></tr>
				<tr><td><b>Finding Status:</b> {$remediation.poam_status}</td></tr>
				<!--<tr><td><b>Finding Status:</b> {$finding.finding_status}</td></tr>-->
	        	<!--<tr><td><b>Scan Date:</b> {$finding.finding_date_discovered}</td></tr>-->
				<tr>
					<td>
						<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='remediation_id' value='{$remediation_id}'>
						<input type='hidden' name='root_comment'   value='{$root_comment}'>
						<input type='hidden' name='target' 		   value='remediation_owner'>
						<input type='hidden' name='action'         value='update'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>

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
				
				
				
	    	</table> 
			<!-- FINDING TABLE -->

	    </td>

	    <!-- asset information -->
    	<td width="50%" valign="top">

			<!-- ASSET TABLE -->
	    	<table border="0" cellpadding="5" cellspacing="1" class="tipframe" width="100%">
            	<th align="left" colspan="2">Asset Information</th>
				<tr>
                   	<td><b>Asset Owner:</b> ({$finding.system_nickname}) {$finding.system_name}</td>
               	</tr>
				<tr>
                   	<td><b>Asset Name:</b> 
						{if $finding.asset_name eq "NULL"}<i>(none given)</i>
						{else}{$finding.asset_name}{/if}
					</td>
               	</tr>
				<tr>
                   	<td><b>Known Address(es):</b>
						{section name=row loop=$asset_addresses}
						({$asset_addresses[row].network_nickname}) 
						{if $asset_addresses[row].address_ip eq ""}<i>(none given)</i> :
                        {else}{$asset_addresses[row].address_ip} :{/if}
						{if $asset_addresses[row].address_port eq ""}<i>(none given)</i>{else}{$asset_addresses[row].address_port}{/if}
						{/section}
					</td>
				</tr>
				<tr>
                   	<td><b>Product Information:</b>
						{if $product.prod_id eq ""}<i>(none given)</i>
						{else}{$product.prod_vendor} {$product.prod_name} {$product.prod_version}
						{/if}
					</td>
				</tr>
			</table> 
            <!-- END ASSET TABLE -->
    	</td>
	</tr>
	<tr> <!-- INSTANCE SPECIFIC DATA ROW -->
   		<td colspan="2" width="90%">

			<!-- INSTANCE DATA TABLE -->
		    <table border="0" cellpadding="5" cellspacing="1" class="tipframe" width="100%">
	        	<th align="left">Finding Description</th>
		        <tr><td>{if $finding.finding_data eq ""}<i>(none given)</i>{else}{$finding.finding_data}{/if}</td></tr>
			</table> 
            <!-- END INSTANCE DATA TABLE -->

	    </td>
	</tr>
	<tr>
		<td colspan="2">

			<table border="0" cellpadding="5" cellspacing="1" width="100%" align="center" class="tipframe">
				<th align='left'>Additional Vulnerability Detail</th>
				<!-- VULNERABILITY ROW(S) -->
		
				{section name=row loop=$vulnerabilities step='-1'}
				<tr>
					<td colspan="2">

						<!-- VULERABILITIES TABLE -->
						<table border="0" cellpadding="5" cellspacing="1" width="100%">
							<tr><td><b>Vulnerability ID:</b> {$vulnerabilities[row].vuln_type}-{$vulnerabilities[row].vuln_seq}</td></tr>
							<tr><td><b>Primary Description:</b> {$vulnerabilities[row].vuln_desc_primary}</td></tr>
							<tr>
								<td><b>Secondary Description:</b> 
									{if $vulnerabilities[row].vuln_desc_secondary eq "0"}<i>(none given)</i>
									{else}{$vulnerabilities[row].vuln_desc_secondary}
									{/if}
								</td>
							</tr>
						</table> 
						<!-- END VULERABILITIES TABLE -->
					</td>
				</tr>
				{/section}
			</table> 

		</td>
	</tr>
	<tr>
		<td colspan="2">

		   	<table cellpadding="5" width="100%" class="tipframe">
				<th align='left' colspan='2'>Recommendation</th>
				<tr>
					<td colspan='2'>
						<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='remediation_id' value='{$remediation_id}'>
						<input type='hidden' name='root_comment'   value='{$root_comment}'>
						<input type='hidden' name='target' 		   value='action_suggested'>
						<input type='hidden' name='action'         value='update'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>

						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_mitigation_recommendation eq '1'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

						{/if}

						<span>{$remediation.poam_action_suggested}</span>
						</form>
					</td>
				</tr>
			</table>

		</td>
	</tr>
</table> 
<!-- END FINDING TABLE -->
<br>
<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Mitigation Strategy</b></td>
		<td bgcolor="#DFE5ED" align="right"></td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- MITIGATION STRATEGY -->
<table border="0" width="95%" align="center">
   	<tr>
		<td colspan='2'>
            
			<!-- Course of Action Table -->
			<table width="100%" cellpadding="5" class="tipframe">
				<th align="left">Course of Action</th>

				<tr>
					<td align="left">
	
						<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='remediation_id' value='{$remediation_id}'>
						<input type='hidden' name='root_comment'   value='{$root_comment}'>
						<input type='hidden' name='target' 		   value='remediation_type'>
						<input type='hidden' name='action'         value='update'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>
				
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
				</tr>
				<tr>
					<td>
						<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='remediation_id' value='{$remediation_id}'>
						<input type='hidden' name='root_comment'   value='{$root_comment}'>
						<input type='hidden' name='target' 		   value='action_planned'>
						<input type='hidden' name='action'         value='update'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>

						<b>Description:</b> 
						
						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_mitigation_course_of_action eq '1'}
						{if $remediation_status eq 'OPEN'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

						{/if}
						{/if}

						<span>{$remediation.poam_action_planned}</span>
						</form>
					
					</td>
				</tr>
			</table>
			<!-- End Course of Action Table -->
			
		</td>
	</tr>
	<tr>
		<td colspan='2'>
		
			<!-- Resources Required for Course of Action Table -->
			<table width="100%" cellpadding="5" class="tipframe">
				<th align="left">Resources Required for Course of Action</th>
				<tr>
					<td>

						<form action='remediation_modify.php' method='POST'>
                        <input type='hidden' name='remediation_id' value='{$remediation_id}'>
                        <input type='hidden' name='root_comment'   value='{$root_comment}'>
                        <input type='hidden' name='target' 		   value='action_resources'>
                        <input type='hidden' name='action'         value='update'>
                        <input type='hidden' name='validated'      value='no'>
                        <input type='hidden' name='approved'       value='no'>

						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_mitigation_resources eq '1'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

						{/if}

						<span>{$remediation.poam_action_resources}</span>
						</form>

						</td>
				</tr>
			</table>
			<!-- End Resources Required for Course of Action Table -->
		
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

			<b>Estimated Completion Date:</b> 

			{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
			{if $modify_mitigation_completion_date eq '1'}
			{if $remediation_status eq 'OPEN'}

			<input type='hidden' name='form_action' value='Update'>
			<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

			{/if}
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
			{ if $num_comments_est gt 0 }
	<tr>
		<td colspan='2'>
					
			<!-- Comments for ECD Modification Table -->
			<table width="100%" border="0" cellpadding="5" class="tipframe">
				<th align="left">Comments For Date Modification <i>({$num_comments_est} total)</i></th>
				<tr>
					<td>
	    		
						<!-- COMMENT TABLE -->
						<table border="1" align="left" cellpadding="5" cellspacing="1" width="100%" class="tbframe">
							<tr>
								<th nowrap>Changed On</td>
								<th nowrap>Changed By</td>
								<th nowrap>Event</td>
								<th nowrap>Reason for Change</td>
							</tr>

							{section name=row loop=$comments_est}
				
							<tr>
								<td class="tdc" nowrap>{$comments_est[row].comment_date}</td>
								<td class="tdc" nowrap>{$comments_est[row].user_name}</td>
								<td class="tdc">{$comments_est[row].comment_topic}</td>
								<td class="tdc">{$comments_est[row].comment_body}</td>
							</tr>
				
							{/section}
				
						</table>
						<!-- COMMENT TABLE -->

					</td>
				</tr>
			</table>
			<!-- End Comments for ECD Modification Table -->
					
		</td>
	</tr>
					{/if}
</table>
<!-- END MITIGATION STRATEGY TABLE -->

<br>
<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>NIST 800-53 Control Mapping</b></td>
		<td bgcolor="#DFE5ED" align="right"></td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->
<br>

	{if $blscr.blscr_number eq ""}

		<!-- BLSCR TABLE -->
   		<table border="1" width="95%" align="center" cellpadding="5" cellspacing="1" class="tipframe">
	       	<th align="left" >Security Control</th>
			<tr><td><i>(none given)</i></td></tr>
			<tr>
				<td>
					<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='remediation_id' value='{$remediation_id}'>
						<input type='hidden' name='root_comment'   value='{$root_comment}'>
						<input type='hidden' name='target' 		   value='blscr_number'>
						<input type='hidden' name='action'         value='update'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>
					
					<b>Number:</b>
					
						{if $modify_blscr eq '1'}
							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>
						{/if}
							<span>{$blscr.blscr_number}</span>
					</form>
				</td>
			</tr>
		</table>

	{/if}
		        
	{if $blscr.blscr_number neq ""}			

   		<table border="0" width="95%" align="center" cellpadding="5" class="tipframe">
			<tr>
				<td>

			<table align="left" border="0" cellpadding="5" class="tbframe">
				<tr>
					<th class="tdc">Control Number</th>
					<th class="tdc">Class</th>
					<th class="tdc">Family</th>
					<th class="tdc">Subclass</th>
					<th class="tdc">Low</th>
					<th class="tdc">Moderate</th>
					<th class="tdc">High</th>
				</tr>
				<tr>
					<td class="tdc" align="center">
						<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target' 		   value='blscr_number'>
							<input type='hidden' name='action'         value='update'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>
							{if $modify_blscr eq '1'}
								<input type='hidden' name='form_action' value='Update'>
								<input type='image' src='images/button_modify.png' name='form_action' value='Update'>
							{/if}
							<span>{$blscr.blscr_number}</span>
						</form>
					</td>
					<td class="tdc">{$blscr.blscr_class}</td>
					<td class="tdc">{$blscr.blscr_family}</td>
					<td class="tdc">{$blscr.blscr_subclass}</td>	
					<td class="tdc" align="center">{if $blscr.blscr_low eq '1'}Control Required{else}Control Not Required{/if}</td>
					<td class="tdc" align="center">{if $blscr.blscr_moderate eq '1'}Control Required{else}Control Not Required{/if}</td>
					<td class="tdc" align="center">{if $blscr.blscr_high eq '1'}Control Required{else}Control Not Required{/if}</td>
				</tr>
			</table>

				</td>
			</tr>	
			<tr><td><b>Control: </b> {$blscr.blscr_control}</td></tr>
		    <tr><td><b>Guidance: </b> {$blscr.blscr_guidance}</td></tr>
		    <tr><td><b>Enhancements: </b>{if $blscr.blscr_enhancements eq '.'}<i>(none given)</i>{else}{$blscr.blscr_enhancements}{/if}</td></tr>
	    	<tr><td><b>Supplement: </b>{if $blscr.blscr_supplement eq '.'}<i>(none given)</i>{else}{$blscr.blscr_supplement}{/if}</td></tr>
		</table>

	{/if}

<br>
<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Risk Analysis</b></td>
		<td bgcolor="#DFE5ED" align="right"></td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- REMEDIATION TABLE -->
<table border="0" cellpadding="5" cellspacing="1" width="95%" align="center">
	<tr> <!-- REMEDIATION INFORMATION ROW -->
		<td width='50%' valign='top'>

			<table border="0" cellpadding="5" cellspacing="1" width="100%" class="tipframe">
				<th align="left" colspan='2'>Risk Analysis Form</th>
				<tr>
					<td>
						Based on the guidance provided by NIST Special Publication 800-37, to derive an overall likelihood rating that indicates the probability that a potential vulnerability may be exercised, we must first define the threat-source motivation and capability while considering the nature of the vulnerability and the existence and effectiveness of current controls or countermeasures. The following two sections on Threat Information and Countermeasure Information will help us define the iformation required to generate a threat likelihood risk level which will be used to generate the overall risk level of this vulnerability as it pertains to your information system.
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
						<input type='submit' name='form_action' value='Generate RAF'>
					</td>
						</form>
						{else}
					<td colspan='2'><i>(Threat and Countermeasure information must be completed to generate a RAF)</i></td>
						{/if}
						{else}
					<td colspan='2'>&nbsp;</td>
						{/if}
				</tr>
			</table>

		</td>
		
	</tr>
	<tr> <!-- THREATS ROW -->
	    <td colspan="2">

			<!-- THREATS TABLE -->
    		<table border="0" cellpadding="5" cellspacing="1" class="tipframe" width="100%">
	        	<th align='left'>Threat Information</th>
					<tr>
						<td>
							A threat is the potential for a particular threat-source to successfully exercise a particular vulnerability. A vulnerability is a weakness that can be accidentally triggered or intentionally exploited. A threat-source does not present a risk when there is no vulnerability that can be exercised. In determining the likelihood of a threat, one must consider threat-sources, potential vulnerabilities, and existing controls. Common threat sources are: (1) Natural Threats: Floods, earthquakes, tornadoes, landslides, avalanches, electrical storms, and other such events, (2) Human Threats: Events that are either enabled by or caused by human beings, such as unintentional acts (inadvertent data entry) or deliberate actions (network based attacks, malicious software upload, unauthorized access to confidential information), and (3) Environmental Threats: Long-term power failure, pollution, chemicals, liquid leakage.
						</td>
					</tr>
					<tr>
						<td>
							<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target' 		   value='threat_level'>
							<input type='hidden' name='action'         value='update'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>
							<b>Level:</b> 
		
        					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
							{if $modify_threat_level eq '1'}
							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>
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
							<b>Source:</b> 

							{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
							{if $modify_threat_source eq '1'}

							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

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

							<b>Justification:</b> 

							{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
							{if $modify_threat_justification eq '1'}

							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

							{/if}

							<span>{$remediation.poam_threat_justification}</span>
							</form>
						</td>
					</tr>
	    		</table> 
                <!-- END THREATS TABLE -->
		    </td>
		</tr>
	<tr> <!-- COUNTERMEASURES ROW -->
	    <td colspan="2">
			<!-- COUNTERMEASURE TABLE -->
    		<table border="0" cellpadding="5" cellspacing="1" class="tipframe" width="100%">
	        	<th align="left" colspan="2">Countermeasure Information</th>
					<tr>
						<td>
							The goal of this step is to analyze the controls that have been implemented, or are planned for implementation, by the organization to minimize or eliminate the likelihood (or probability) of a threat's exercising a system vulnerability. Countermeasures or Security controls encompass the use of technical and nontechnical methods. Technical controls are safeguards that are incorporated into computer hardware, software, or firmware (e.g., access control mechanisms, identification and authentication mechanisms, encryption methods, intrusion detection software). Nontechnical controls are management and operational controls, such as security policies; operational procedures; and personnel, physical, and environmental security.
						</td>
					</tr>
					<tr>
						<td>
							<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target' 		   value='cmeasure_effectiveness'>
							<input type='hidden' name='action'         value='update'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>

							<b>Effectiveness:</b> 

							{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
							{if $modify_cmeasure_effectiveness eq '1'}

							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

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

							<b>Countermeasure:</b> 

							{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
							{if $modify_cmeasure eq '1'}

							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

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
	
							<b>Justification:</b> 
	
							{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
							{if $modify_cmeasure_justification eq '1'}

							<input type='hidden' name='form_action' value='Update'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

							{/if}

							<span>{$remediation.poam_cmeasure_justification}</span>
							</form>
					</td>
				</tr>
		    </table> 
            <!-- END COUNTERMEASURE TABLE -->
	    </td>
	</tr>

 	<tr>

		<td colspan='2'>
		<table border="0" cellpadding="5" cellspacing="1" width="100%" class="tipframe">

			<th align='left'>Approval</th>
			<tr>
    			<td colspan="2">
                    <i>(All fileds above must be set and saved to make SSO approval field editable.)</i>
    			</td>
			</tr>
			<tr>

				<td colspan='2'>
			<form action='remediation_modify.php' method='POST'>
			<input type='hidden' name='remediation_id' value='{$remediation_id}'>
			<input type='hidden' name='root_comment'   value='{$root_comment}'>
			<input type='hidden' name='target' 		   value='action_approval'>
			<input type='hidden' name='action'         value='update'>
			<input type='hidden' name='validated'      value='no'>
			<input type='hidden' name='approved'       value='no'>

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

			<tr>
	    		<td colspan="2" width="90%">
					<table border="1" cellpadding="5" cellspacing="0" width="100%" class="tbframe">
						<th nowrap>Comment On</th>
						<th nowrap>Comment By</th>
						<th nowrap>Event</th>
						<th nowrap>Description</th>
						
						{section name=row loop=$comments_sso}

						<tr>
							<td nowrap>{$comments_sso[row].comment_date}</td>
							<td nowrap>{$comments_sso[row].user_name}</td>
							<td nowrap>{$comments_sso[row].comment_topic}</td>
							<td nowrap>{$comments_sso[row].comment_body}</td>
						</tr>
						
						{/section}
						
					</table>

				</td>
			</tr>
			
			{/if}
		</table>
		</td>

	</tr>


</table> <!-- REMEDIATION TABLE -->

<br>

<!-- ------------------------------------------------------------------------ -->
<table width="95%" border="0" align="center">
<tr>
<td>
<table border="0" align="left">
	<tr>
		<td>
			<!-- SAVE MODIFICATIONS TO REMEDIATION -->
			<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='action'         value='add'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>
				<input type='hidden' name='target'         value='save_poam'>
				<input type='hidden' name='remediation_id' value='{$remediation_id}'>
				<input type='hidden' name='form_action' value=''>
				<input type='submit' title='Save or Submit' value="Save" style="cursor: pointer;">
			</form>
		</td>
		<td>
			<!-- RETURN TO THE SUMAMRY LIST -->
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
				<input name="button" type="submit" id="button" value="Go Back" style="cursor: pointer;">
			</form>
		</td>
	</tr>
</table>
</td>
</tr>
</table>

{* NO REAL NEED TO SHOW UNTIL EN, EO, EP, ES or CLOSED *}
{if $view_evidence eq '1'} <!-- Statement 1 -->
{if $remediation_status neq 'OPEN'} <!-- Statement 2 -->

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Supporting Evidence</b></td>
		<td bgcolor="#DFE5ED" align="right"></td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- EVIDENCE TABLE -->
<table border="0" cellpadding="5" cellspacing="1" width="95%" align="center" class="tipframe">

	<th align='left'>Evidence Submissions <i>({$num_evidence} total)</i></th>
	
		{* loop through the evidence *}
		{if $num_evidence gt 0} <!-- Statement 3 -->

		{section name=row loop=$all_evidence}

		{* DO NOT SHOW BAD EVIDENCE AT STATUS ES *}
		{if $remediation_status eq 'ES' && $all_evidence[row].ev_sso_evaluation neq 'APPROVED' || $remediation_status eq 'ES' && $all_evidence[row].ev_fsa_evaluation neq 'APPROVED'}
		{else}

	<tr>

		{* EVIDENCE TABLE *}
		<td colspan='2' width='100%'>

			<table border='0' cellpadding='3' cellspacing='1' class='tipframe' width='100%'>

				<tr><th align='left' colspan="2">Evidence Submitted by {$all_evidence[row].submitted_by} on {$all_evidence[row].ev_date_submitted}</th></tr>
				<tr colspan="2">
                    <td><b>Evidence:</b>{if $all_evidence[row].fileExists eq 1}<a href="javascript:void(0)" onClick="window.open('{$all_evidence[row].ev_submission}', 'evidence_window', config='resizable=yes,menubar=no,scrollbars=yes')">{$all_evidence[row].fileName}</a>{else}{$all_evidence[row].fileName}{/if}</td>
				</tr>

				{* SSO EVALUATION *}
				<tr>

				{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
				{if $modify_evidence_sso_approval eq '1' && $remediation_status eq 'EP' && $all_evidence[row].ev_sso_evaluation eq 'NONE'}

					<td>
						<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target'         value='evidence'>
							<input type='hidden' name='action'         value='sso_evaluate'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>
		
						<b>ISSO Evaluation:</b> 
							<input type='hidden' name='form_action' value='Evaluate'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
							<span>{$all_evidence[row].ev_sso_evaluation} </span>

							</form>
                    </td>
					
				{else}

					<td><b>ISSO Evaluation:</b> {$all_evidence[row].ev_sso_evaluation}</td>

					{if $all_evidence[row].comments.EV_SSO neq ''}

					<td width="85%">

						<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
        					<tr><th align='left'>{$all_evidence[row].comments.EV_SSO.comment_topic}</th></tr>
        					<tr><td >{$all_evidence[row].comments.EV_SSO.comment_body}</td></tr>
        					<tr><td align='right'><i>{$all_evidence[row].comments.EV_SSO.comment_date} by {$all_evidence[row].comments.EV_SSO.user_name}</i></td></tr>
        				</table>
                        
					</td>

					{/if}

				{/if}

				</tr>

				{* FSA EVALUATION *}
				<tr>

				{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
				{if $modify_evidence_fsa_approval eq '1' && $remediation_status eq 'EP' && $all_evidence[row].ev_sso_evaluation eq 'APPROVED' && $all_evidence[row].ev_fsa_evaluation eq 'NONE'}

                    <td>
						<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target'         value='evidence'>
							<input type='hidden' name='action'         value='fsa_evaluate'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>

						<b>IV&V Evaluation:</b> 
					
							<input type='hidden' name='form_action' value='Evaluate'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
							<span>{$all_evidence[row].ev_fsa_evaluation}</span>

						</form>
					</td>

				{else}

					<td><b>IV&V Evaluation:</b> {$all_evidence[row].ev_fsa_evaluation}</td>

						{if $all_evidence[row].comments.EV_FSA neq ''}

					<td width="85%">

						<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
							<tr><th align='left'>{$all_evidence[row].comments.EV_FSA.comment_topic}</th></tr>
							<tr><td >{$all_evidence[row].comments.EV_FSA.comment_body}</td></tr>
							<tr><td align='right'><i>{$all_evidence[row].comments.EV_FSA.comment_date} by {$all_evidence[row].comments.EV_FSA.user_name}</i></td></tr>
						</table>
                    
					</td>

						{/if}
				{/if}
					
				</tr>


				{* IVV EVALUATION *}
				<tr>

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_evidence_ivv_approval eq '1' && $remediation_status eq 'ES' && $all_evidence[row].ev_sso_evaluation eq 'APPROVED' && $all_evidence[row].ev_fsa_evaluation eq 'APPROVED'}

						<td>
							<form action='remediation_modify.php' method='POST'>
								<input type='hidden' name='remediation_id' value='{$remediation_id}'>
								<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
								<input type='hidden' name='root_comment'   value='{$root_comment}'>
								<input type='hidden' name='target'         value='evidence'>
								<input type='hidden' name='action'         value='ivv_evaluate'>
								<input type='hidden' name='validated'      value='no'>
								<input type='hidden' name='approved'       value='no'>

							<b>Final Evaluation:</b> 
						
								<input type='hidden' name='form_action' value='Evaluate'>
								<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
								<span>{$all_evidence[row].ev_ivv_evaluation}</span>

							</form>
						</td>
						
					{else}
					
						<td><b>Final Evaluation:</b> {$all_evidence[row].ev_ivv_evaluation}</td>

						{if $all_evidence[row].comments.EV_IVV neq ''}
                            
						<td width="85%">
        			    	<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
        						<tr><th align='left'>{$all_evidence[row].comments.EV_IVV.comment_topic}</th><tr>
        						<tr><td >{$all_evidence[row].comments.EV_IVV.comment_body}</td></tr>
        						<tr><td align='right'><i>{$all_evidence[row].comments.EV_IVV.comment_date} by {$all_evidence[row].comments.EV_IVV.user_name}</i></td></tr>
							</table>
                        </td>
                        
						{/if}

					{/if}
				
				</tr>
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
						<input type='hidden' name='form_action'    value='Submit Evidence'>
						<input type='button' name="form_action" title='Submit Evidence' value="Upload Evidence">
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


<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Audit Log</b></td>
		<td bgcolor="#DFE5ED" align="right"></td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

	<br>

<!-- COMMENT TABLE -->
<!-- <th align="left">Logs <i>({$num_logs} total)</i></th> -->

	{* loop through the logs *}
	{if $num_logs gt "0"}

<table border="0" align="center" cellpadding="5" cellspacing="1" width="95%" class="tbframe">
	<tr>
		<th>Timestamp</td>
		<th>User</td>
		<th>Event</td>
		<th>Description</td>
	</tr>

	{section name=row loop=$logs}
				
	<tr>
		<td class="tdc">{$logs[row].time}</td>
		<td class="tdc">{$logs[row].user_name}</td>
		<td class="tdc">{$logs[row].event}</td>
		<td class="tdc">{$logs[row].description}</td>
	</tr>
				
	{/section}
				
</table>
{/if}
<!-- COMMENT TABLE -->

<br>

{else}
<p class="errormessage">{$noright}</p>
{/if}

{include file="footer.tpl"}
