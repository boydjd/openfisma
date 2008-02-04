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


<!-- ----------------------------------------------------------------------- -->
<!-- MAIN PAGE                                                               -->
<!-- ----------------------------------------------------------------------- -->

<br>

{if $view_right eq 1}

<!-- Heading Block -->
<table class="tbline">        
<tr>     
<td id="tbheading"><img src="images/contract.gif" class="expend_btn" /><b>Finding Detail</b></td>
<td id="tbtime">{$now}</td>
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
		    <table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
	        	<th align="left">Finding Description</th>
		        <tr><td>{if $finding.finding_data eq ""}<i>(none given)</i>{else}{$finding.finding_data}{/if}</td></tr>
			</table> 
            <!-- END INSTANCE DATA TABLE -->

	    </td>
	</tr>
</table> 
<!-- END FINDING TABLE -->

<br>

<!-- Heading Block -->
<table class="tbline">
	<tr>
		<td id="tbheading"><img src="images/contract.gif" class="expend_btn" /><b>Additional Finding Information</b></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- VULNERABILITY DETAIL TABLE -->
<table border="0" cellpadding="3" cellspacing="1" width="95%" align="center" class="tipframe">
	<th align='left'>Vulnerability Detail</th>
		<!-- VULNERABILITY ROW(S) -->
		{section name=row loop=$vulnerabilities step='-1'}
	<tr>
    	<td colspan="2" width="90%">

			<!-- VULERABILITIES TABLE -->
		    <table border="0" cellpadding="3" cellspacing="1" width="100%">
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
<!-- END VULNERABILITY DETAIL TABLE -->

<br>

<table width="98%" align="center">
	<tr>
    	<td align="left">
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
				<input name="button" type="submit" id="button" value="Go Back" style="cursor: hand;">
			</form>
		</td>
  	</tr>
</table>

<!-- REMEDIATION DETAIL LINE -->

<!-- Heading Block -->
<table class="tbline">
	<tr>
		<td id="tbheading"><img src="images/contract.gif" class="expend_btn" /><b>Remediation Detail</b></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- REMEDIATION TABLE -->
<table border="0" cellpadding="3" cellspacing="1" width="95%" align="center" >
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
<<<<<<< .mine
                        <input name="button" type="submit" id="button" value="Generate RAF" style="cursor: hand;">
=======
						<input type='submit' name='form_action' value='Generate RAF'>
>>>>>>> .r78
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
		<td width='50%' valign='top'>
			<table border="0" cellpadding="3" cellspacing="1" width="100%" class="tipframe">
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
    					<b>Previous Audits: </b>
						<span>{$remediation.poam_previous_audits}</span>
						</form>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr> 
	    <td colspan="2">

			<!-- BLSCR TABLE -->
   			<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
	       		<th align="left" >Baseline Security Requirements</th>
					{if $blscr.blscr_number eq ""}
                <tr>
                	<td><i>(none given)</i></td>
                </tr>
					{/if}
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
	    	</table> 
            <!-- END BLSCR TABLE-->

	    </td>

	</tr>
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
		
        	<!-- MITIGATION STRATEGY -->
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

						<b>Recommendation:</b> 

						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_mitigation_recommendation eq '1'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

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

						<b>Course of Action:</b> 

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
		    	<tr>
					<td colspan='2'>
						<form action='remediation_modify.php' method='POST'>
                        <input type='hidden' name='remediation_id' value='{$remediation_id}'>
                        <input type='hidden' name='root_comment'   value='{$root_comment}'>
                        <input type='hidden' name='target' 		   value='action_resources'>
                        <input type='hidden' name='action'         value='update'>
                        <input type='hidden' name='validated'      value='no'>
                        <input type='hidden' name='approved'       value='no'>

						<b>Resources:</b> 

						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_mitigation_resources eq '1'}

						<input type='hidden' name='form_action' value='Update'>
						<input type='image' src='images/button_modify.png' name='form_action' value='Update'>

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
					{if $num_comments_est > 0}
				<tr>
                	<th align="left" colspan="2">Comments For Estimated Completion Date Changed <i>({$num_comments_est} total)</i></th>
               	</tr>
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
			<!-- END MITIGATION STRATEGY TABLE -->

		</td>
	</tr>
 	<tr>
		<td colspan='2'>
			<table border="0" cellpadding="3" cellspacing="1" width="100%" class="tipframe">
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
						{if $remediation_type neq 'NONE' && $is_completed eq 'yes'}
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
			
            		<!-- Display ISSO Comments -->
            		{if $num_comments_sso > 0}
			
				<tr>
                	<th align="left" colspan="2">Comments From SSO <i>({$num_comments_sso} total)</i></th>
             	</tr>
					
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
</table> 
<!-- END REMEDIATION TABLE -->

<br>

<table>
<<<<<<< .mine
	<tr>
    	<td>
			{* RETURN TO THE SUMAMRY LIST *}
			<form action='remediation_modify.php' method='POST'>
				<input type='hidden' name='action'         value='add'>
				<input type='hidden' name='validated'      value='no'>
				<input type='hidden' name='approved'       value='no'>
    			<input type='hidden' name='target'         value='save_poam'>
    			<input type='hidden' name='remediation_id' value='{$remediation_id}'>
    			<input type='hidden' name='form_action' value=''>
    			<input type='image' src='images/button_save.png' value='Save or Submit'>
				<input name="button" type="button" id="button" value="Save or Submit" style="cursor: hand;">
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
                <input name="button" type="submit" id="button" value="Go Back" style="cursor: hand;">
			</form>
		</td>
	</tr>
=======
<tr><td>
{* RETURN TO THE SUMAMRY LIST *}
<form action='remediation_modify.php' method='POST'>
						<input type='hidden' name='action'         value='add'>
						<input type='hidden' name='validated'      value='no'>
						<input type='hidden' name='approved'       value='no'>
    <input type='hidden' name='target'         value='save_poam'>
    <input type='hidden' name='remediation_id' value='{$remediation_id}'>
    <input type='hidden' name='form_action' value=''>
    <input type='submit' title='Save or Submit' value="Save">
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
				<input name="button" type="submit" id="button" value="Go Back" style="cursor: hand;">
			</form>
</td></tr>
>>>>>>> .r78
</table>

<!-- ------------------------------------------------------------------------ -->


{* NO REAL NEED TO SHOW UNTIL EN, EO, EP, ES or CLOSED *}
{if $view_evidence eq '1'}
{if $remediation_status neq 'OPEN'}

<!-- EVIDENCE LINE -->

<!-- Heading Block -->
<table class="tbline">
	<tr>
		<td id="tbheading"><img src="images/contract.gif" class="expend_btn" /><b>Evidence Detail</b></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- EVIDENCE TABLE -->
<table border="0" cellpadding="3" cellspacing="1" width="95%" align="center" class="tipframe">
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
					<tr><th align='left' colspan="2">Submitted: {$all_evidence[row].ev_date_submitted} by {$all_evidence[row].submitted_by}</th></tr>
					<tr colspan="2">
                    	<td><b>Evidence:</b> 
						<a href="javascript:void(0)" onClick="window.open('{$all_evidence[row].ev_submission}', 'evidence_window', config='resizable=yes,menubar=no,scrollbars=yes')">{$all_evidence[row].ev_submission}</a>
						</td>
                  	</tr>

					{* SSO EVALUATION *}
					<tr>

						{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
						{if $modify_evidence_sso_approval eq '1' 
                        	&& $remediation_status eq 'EP' 
                            && $all_evidence[row].ev_sso_evaluation eq 'NONE'}

						<td>
							<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target'         value='evidence'>
							<input type='hidden' name='action'         value='sso_evaluate'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>
		
							<b>SSO Evaluation:</b> 
							<input type='hidden' name='form_action' value='Evaluate'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
							<span>{$all_evidence[row].ev_sso_evaluation} </span>
							</form>
 						</td>

							{else}
						
                        <td><b>SSO Evaluation:</b> {$all_evidence[row].ev_sso_evaluation}</td>
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
					{if $modify_evidence_fsa_approval eq '1' 
                    	&& $remediation_status eq 'EP' 
                        && $all_evidence[row].ev_sso_evaluation eq 'APPROVED' 
                        && $all_evidence[row].ev_fsa_evaluation eq 'NONE'}
                    	
                        <td>
							<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target'         value='evidence'>
							<input type='hidden' name='action'         value='fsa_evaluate'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>

							<b>FSA Evaluation:</b> 
							<input type='hidden' name='form_action' value='Evaluate'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
							<span>{$all_evidence[row].ev_fsa_evaluation}</span>
							</form>
						</td>
						
                        {else}
						
                        <td><b>FSA Evaluation:</b> {$all_evidence[row].ev_fsa_evaluation}</td>
						
                        	{if $all_evidence[row].comments.EV_FSA neq ''}
                        
                        <td width="85%">
        			    	
                            <table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">
        						<tr>
                                	<th align='left'>{$all_evidence[row].comments.EV_FSA.comment_topic}</th>
                               	</tr>
        						<tr>
                                	<td>{$all_evidence[row].comments.EV_FSA.comment_body}</td>
                              	</tr>
        						<tr>
                                	<td align='right'><i>{$all_evidence[row].comments.EV_FSA.comment_date} by {$all_evidence[row].comments.EV_FSA.user_name}</i></td>
                               	</tr>
        					</table>

 						</td>
                        
                        {/if}
						{/if}
					</tr>

					{* IVV EVALUATION *}
					<tr>

					{* RESTRICT UPDATE BASED ON STATUS AND ROLE *}
					{if $modify_evidence_ivv_approval eq '1' 
                    	&& $remediation_status eq 'ES' 
                        && $all_evidence[row].ev_sso_evaluation eq 'APPROVED' 
                        && $all_evidence[row].ev_fsa_evaluation eq 'APPROVED'}
                   		
                        <td>
							<form action='remediation_modify.php' method='POST'>
							<input type='hidden' name='remediation_id' value='{$remediation_id}'>
							<input type='hidden' name='ev_id'          value='{$all_evidence[row].ev_id}'>
							<input type='hidden' name='root_comment'   value='{$root_comment}'>
							<input type='hidden' name='target'         value='evidence'>
							<input type='hidden' name='action'         value='ivv_evaluate'>
							<input type='hidden' name='validated'      value='no'>
							<input type='hidden' name='approved'       value='no'>

							<b>IVV Evaluation:</b> 
							<input type='hidden' name='form_action' value='Evaluate'>
							<input type='image' src='images/button_modify.png' name='form_action' value='Evaluate'> 
							<span>{$all_evidence[row].ev_ivv_evaluation}</span>
							</form>
						</td>
						
                        {else}
						
                        <td><b>IVV Evaluation:</b> {$all_evidence[row].ev_ivv_evaluation}</td>
						
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
			<!-- If no evidence has been provided allow evidence upload -->
            {if $modify_evidence_upload eq '1'}
			{if $remediation_status eq 'EN' || $remediation_status eq 'EO'}

			<tr align='left'>
<<<<<<< .mine
				<td>
					<form action='remediation_modify.php' method='POST'>
					<input type='hidden' name='remediation_id' value='{$remediation_id}'>
					<input type='hidden' name='root_comment'   value='{$root_comment}'>
					<input type='hidden' name='target'         value='evidence'>
					<input type='hidden' name='action'         value='add'>
					<input type='hidden' name='validated'      value='no'>
					<input type='hidden' name='approved'       value='no'>
					<input type='hidden' name='uploaded'       value='no'>
					<input type='hidden' name='form_action' value='Submit Evidence'>
					<input type='image' src='images/button_submit_evidence.png' name='form_action'    value='Submit Evidence'>
					<input name="form_action" type="submit" id="button" value="Submit Evidence" style="cursor: hand;">
                    </form>
				</td>
=======
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
>>>>>>> .r78
			</tr>

<<<<<<< .mine
			{/if}
			{/if}
        	
            <tr>
            	<td>
                	<input type='image' src='images/button_submit.png' value='Submit Evidence Change'>
            	</td>
        	</tr>
		</table>
=======
		{/if}
		{/if}
        <tr>
            <td>
                <input type='button' title='Submit Evidence Change' value="Submit">
            </td>
        </tr>
	</table>
>>>>>>> .r78

	<br>

{/if}
{/if}

<!-- ------------------------------------------------------------------------ -->


<!-- COMMENT LINE -->

<!-- Heading Block -->
<table class="tbline">
	<tr>
		<td id="tbheading"><img src="images/contract.gif" class="expend_btn" /><b>Finding Audit Log</b></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- COMMENT TABLE -->
<table border="0" cellpadding="3" cellspacing="1" align="center" width="95%" class="tipframe">
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
			</table>
			
    	</td>
	</tr>

	{/section}
	{/if}

</table> 
<!-- END COMMENT TABLE -->

	<br>

{else}
<p class="errormessage">{$noright}</p>
{/if}

{include file="footer.tpl"}