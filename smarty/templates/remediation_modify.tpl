{*
<!-- ----------------------------------------------------------------------- -->
<!-- FILE    : remediation_modify.tpl                                        -->
<!-- AUTHOR  : Brian Gant                                                    -->
<!-- DATE    : 02/01/06                                                      -->
<!-- PURPOSE : establishes template for remediation_modify page              -->
<!-- ----------------------------------------------------------------------- -->
*}

{* FORM WAS CANCELLED *}
{if $form_action eq 'Cancel'}

	<!-- CANCELLATION FORM -->
	<form action="remediation_detail.php" enctype='multipart/form-data' method="POST">
	<input type='hidden' name='remediation_id' value='{$remediation_id}'>
	<input type='hidden' name='form_action' value='Continue'>

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


	<input type='image' src='images/button_continue.png' name='form_action'    value='Continue'>
	</form>


{* FORM WAS SUBMITTED *}
{elseif $form_action eq 'Submit'}

	{* main table *}

	{* DISPLAY UNVALIDATED FORM *}
	{if $validated eq 'no'}

		{* OPEN THE MAIN FORM *}
		{* main form *}
		
		{*
		<form name='remediation_modify' action="remediation_modify.php" enctype='multipart/form-data' method="POST">
		<input type='hidden' name='remediation_id' value='{$remediation_id}'>
		<input type='hidden' name='target'         value='{$target}'>
		<input type='hidden' name='action'         value='{$action}'>
		<input type='hidden' name='validated'      value='{$validated}'>
		<input type='hidden' name='approved'       value='{$approved}'>
		<input type='hidden' name='current_value'  value='{$current_value}'>

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
		*}

		{* display any errors we may have *} 
		{* section name=row loop=$form_errors step=-1}<b>ERROR:</b> {$form_errors[row]}{/section *}

		{* SAVE_POAM *}
		{if $target eq 'save_poam'}

			<input type='hidden' name='comment_parent'   value='{$root_comment}'>
			<input type='hidden' name='poam_id'   value='{$remediation_id}'>

				<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

			        <th align="left" colspan='2'>{$table_header_comment}</th>

					<tr><td><b>Topic: </b></td><td><input name="comment_topic" maxlength="64" type="text" size="64" style="border:0pt none" value="{$comment_topic}"></td></tr>
					<tr><td><b>Body: </b></td><td><textarea name="comment_body" cols="120" rows="10">{$comment_body}</textarea></td></tr>
					<tr><td><b>Changes: </b></td><td><textarea name="comment_log" cols="120" rows="8" readonly>{$comment_log}</textarea></td></tr>

				</table>
		{/if} {* target == save_poam *}
		

		{* COMMENT *}
		{if $target eq 'comment'}

			<input type='hidden' name='comment_parent'   value='{$root_comment}'>
			<input type='hidden' name='poam_id'   value='{$remediation_id}'>

				<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

			        <th align="left" colspan='2'>{$table_header_comment}</th>

					<tr><td><b>Topic: </b></td><td><input name="comment_topic" maxlength="64" type="text" size="64" style="border:0pt none" value="{$comment_topic}"></td></tr>
					<tr><td><b>Body: </b></td><td><textarea name="comment_body" cols="120" rows="10">{$comment_body}</textarea></td></tr>

				</table>

			{* DISPLAY PARENT COMMENT ON A RESPONSE *}
			{if $action eq 'respond'}

<!--
						<div><b>Topic: </b>{$parent_comment.comment_topic}</div>
						<div><b>Body: </b>{$parent_comment.comment_body}</div>
	
-->
			{/if} {* action == respond *}

		{/if} {* target == comment *}


		{* REMEDIATION *}
		{if $target eq 'remediation'}

						<div><b>Evaluation:</b> <select name='new_value'>
							<option value='CLOSED' {if $new_value eq 'CLOSED'}selected{/if}>Approve</option>
							<option value='EN'     {if $new_value eq 'EN'    }selected{/if}>Deny</option>
						</div>

		{/if}


		{* EVIDENCE *}
		{if $target eq 'evidence'}

<!--			<input type='hidden' name='ev_id' value='{$ev_id}'>-->

			{* SUBMIT NEW EVIDENCE*}
			{if $action eq 'add'}

<!--				<input type='hidden' name='uploaded' value='{$uploaded}'>-->

				<tr>

				    <td>
   					<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

				        <th align="left" colspan='2'>{$table_header_modify}</th>

						{* EVIDENCE NOT YET UPLOADED *}
						{if $uploaded eq 'no'}
							<tr><td>
							    <form id="upload_ev" enctype="multipart/form-data" method="POST" action="evidence_save.php">
								<b>Select File :</b> <input type='file' name='evidence' size='60' value=''>
								<input type="hidden" name="target" value="evidence">
								<input type="hidden" name="action" value="add">
								<input type="hidden" name="validated" value="yes">
								<input type="hidden" name="form_action" value="Evaluate">
                        		<input type='hidden' name='remediation_id' value='{$remediation_id}'>
<!--                        		<input type='hidden' name='validated'      value='{$validated}'>
                        		<input type='hidden' name='approved'       value='{$approved}'>-->
								<input type="hidden" name="uploaded" value="">
<!--								<input type="hidden" name="" value="">-->
								</form>
								<ul>
									<li>please submit <b>all evidence</b> for the finding in a <b>single package</b> (eg, zip file)</li>
									<li>evidence submissions must be <b>under 10 megabytes</b> in size</li>
								</ul>
							</td>
							</tr>

						{* EVIDENCE UPLOADED ALREADY *}
						{else}

							<input type='hidden' name='file_name' value='{$file_name}'>
							<input type='hidden' name='file_loc'  value='{$file_loc}'>

							<tr><td><b>File:</b> {$file_name}</td></tr>

						{/if}

	   				</table>
				    </td>

				</tr>



			{/if}


			{* *}
			{if $action eq 'sso_evaluate' || $action eq 'fsa_evaluate' || $action eq 'ivv_evaluate'}

				<select name='{$action}' id='{$ev_id}' class="ev">
						<option value='NONE' label='NONE' selected>NONE</option>
						<option value='APPROVED' label='APPROVED' >APPROVED</option>
						<option value='DENIED' label='DENIED' >DENIED</option>
				</select>

			{/if}

		{/if}


		{* REMEDIATION OWNER *}
		{if $target eq 'remediation_owner'}

                    <select name='poam_action_owner'>

						{section name=row loop=$all_values}
							<option value='{$all_values[row].system_id}' {if $new_value eq $all_values[row].system_id}selected{/if}
							label="({$all_values[row].system_nickname}) {$all_values[row].system_name}">
								({$all_values[row].system_nickname}) {$all_values[row].system_name}
							</option>
						{/section}
						
					</select>
		{/if}




		{* REMEDIATION TYPE *}
		{if $target eq 'remediation_type'}
                     <select name='poam_type'>
						{if $current_value neq 'CAP'}
							<option value='CAP' {if $new_value eq 'CAP'}selected{/if} label="(CAP) Corrective Action Plan">(CAP) Corrective Action Plan</option>
						{/if}

						{if $current_value neq 'AR' }
							<option value='AR'  {if $new_value eq 'AR' }selected{/if} label="(AR) Accepted Risk">(AR) Accepted Risk</option>
						{/if}

						{if $current_value neq 'FP' }
							<option value='FP'  {if $new_value eq 'FP' }selected{/if} label="(FP) False Positive">(FP) False Positive</option>
						{/if}
					</select>
		{/if}


		{* REMEDIATION STATUS *}
		{if $target eq 'remediation_status'}

                    <select name='poam_status'>
						{if $current_value neq 'OPEN'}
							<option value='OPEN' {if $new_value eq 'OPEN'}selected{/if} label="OPEN">OPEN</option>
						{/if}

						{if $current_value neq 'EN' }
							<option value='EN'  {if $new_value eq 'EN' }selected{/if} label="(EN) Evidence Needed">(EN) Evidence Needed</option>
						{/if}

					</select>

		{/if}


		{* BLSCR NUMBER *}
		{if $target eq 'blscr_number'}
                    
	              <select name='poam_blscr'>

						<option value='NULL'{if $new_value eq 'NULL'}selected{/if} label="None">None</option>

						{section name=row loop=$all_values}

							{if $current_value neq $all_values[row].value}
								<option value='{$all_values[row].value}'{if $current_value eq '{$all_values[row].value}'}selected{/if} label="{$all_values[row].value}">
									{$all_values[row].value}
								</option>
							{/if}

						{/section}

					</select>

		{/if}


		{* ACTION DATE EST *}
		{if $target eq 'action_date_est'}

            <input type="text" name="poam_action_date_est" class="date_picker" size="15">

		{/if}


		{* ACTION APPROVAL *}
		{if $target eq 'action_approval'}

				    <select name='poam_action_status'>
							<option value='APPROVED' {if $new_value eq 'APPROVED'}selected{/if} label="APPROVED">APPROVED</option>
							<option value='DENIED'   {if $new_value eq 'DENIED'  }selected{/if} label="DENIED">DENIED</option>
				    </select>

		{/if}


		{* COUNTERMEASURE EFFECTIVENESS, THREAT LEVEL *}
		{if $target eq 'cmeasure_effectiveness' ||
			$target eq 'threat_level'                    }

			         <select name='poam_cmeasure_effectiveness'>
						{if $current_value neq 'LOW'}
							<option value='LOW' {if $new_value eq 'LOW'}selected{/if} label="LOW">LOW</option>
						{/if}

						{if $current_value neq 'MODERATE' }
							<option value='MODERATE'  {if $new_value eq 'MODERTE' }selected{/if} label="MODERATE">MODERATE</option>
						{/if}

						{if $current_value neq 'HIGH' }
							<option value='HIGH'  {if $new_value eq 'HIGH' }selected{/if} label="HIGH">HIGH</option>
						{/if}
					</select>

		{/if}


		{* TEXTBOX ENTRIES *}
		{if $target eq 'action_suggested'             ||
			$target eq 'action_planned'               ||
			$target eq 'action_resources'             ||
			$target eq 'cmeasure'               ||
			$target eq 'cmeasure_justification' ||
			$target eq 'threat_source'                ||
			$target eq 'threat_justification'         ||
			$target eq 'previous_audits' }

			<textarea name='poam_{$target}' cols='120' rows='10'>{$current_value}</textarea>
			
		{/if}


		{* DISPLAY THE COMMENT ENTRY TABLE REGARDLESS *}
	
			

	{* VALIDATED *}
	{elseif $validated eq 'yes'}


		{* APPROVED *}
		{if $approved eq 'no'}


			<form action='remediation_modify.php' enctype='multipart/form-data' method='POST'>
			<input type='hidden' name='remediation_id' value='{$remediation_id}'>
			<input type='hidden' name='target'         value='{$target}'>
			<input type='hidden' name='action'         value='{$action}'>
			<input type='hidden' name='validated'      value='{$validated}'>
			<input type='hidden' name='approved'       value='{$approved}'>
			<input type='hidden' name='current_value'  value='{$current_value}'>

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


			<input type='hidden' name='comment_topic'  value='{$comment_topic}'>
			<input type='hidden' name='comment_body'   value='{$comment_body}'>


			<table border="0" cellpadding="3" cellspacing="1" width="70%">


			{* COMMENT *}
			{if $target eq 'comment'}

				<input type='hidden' name='root_comment'   value='{$root_comment}'>

			{else}

				<input type='hidden' name='new_value' value='{$new_value}'>


				<tr>
					<td colspan='2'>
					<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">


				        <th align="left" colspan='2'>{$table_header_modify}</th>

						{* EVIDENCE *}
						{if $target eq 'evidence'}

							{* EVALUATE *}
							{if $action eq 'sso_evaluate' || $action eq 'fsa_evaluate' || $action eq 'ivv_evaluate'}
								<input type='hidden' name='ev_id' value='{$ev_id}'>
								<tr><td><b>Status:</b> {$new_value}</td></tr>
							{/if}

							{* ADD *}
							{if $action eq 'add'}
								<input type='hidden' name='file_loc' value='{$file_loc}'>
								<tr><td><b>File:</b> {$file_loc}</td></tr>
							{/if}


						{* ACTION APPROVAL *}
						{elseif $target eq 'action_approval'}

							<tr><td><b>Corrective Action Plan:</b> {$current_value}</td></tr>
							<tr><td><b>Evaluation:</b> {$new_value}</td></tr>


						{* REMEDIATION *}
						{elseif $target eq 'remediation'}

							{if $new_value eq 'CLOSED'}
								<tr><td><b>Status:</b> APPROVED</td></tr>
							{else}
								<tr><td><b>Status:</b> DENIED</td></tr>
							{/if}


						{* RESPONSIBLE SYSTEM *}
						{elseif $target eq 'remediation_owner'}

							<tr><td><b>System:</b> ({$new_system.system_nickname}) {$new_system.system_name}</td></tr>

						{else}

							<tr><td><b>Status:</b> {$new_value}</td></tr>

						{/if}

					</table>
					</td>

				</tr>

			{/if}


			{* DISPLAY THE COMMENT *}
			<tr>
				<td colspan='2'>
				<table border="0" cellpadding="3" cellspacing="1" class="tipframe" width="100%">

			        <th align="left" colspan='2'>{$table_header_comment}</th>

					<tr><td><b>Topic: </b>{$comment_topic}</td></tr>
					<tr><td><b>Body: </b>{$comment_body}</td></tr>

				</table>
				</td>

			</tr>

			{* DISPLAY THE FORM SUBMISSION *}
			<tr>

				<td>
				<table border='0' cellpadding=='3' cellspacing='1'>

					<tr>

						<input type='hidden' name='form_action' value='Submit'>
						<td align='left' width='*'><input type='image' src='images/button_submit.png' name='form_action' value='Submit'></td>
						</form>

						<form action='remediation_modify.php' enctype='multipart/form-data' method='POST'>
						<input type='hidden' name='remediation_id' value='{$remediation_id}'>
						<input type='hidden' name='target'         value='{$target}'>
						<input type='hidden' name='action'         value='{$action}'>
						<input type='hidden' name='validated'      value='{$validated}'>
						<input type='hidden' name='approved'       value='{$approved}'>
						<input type='hidden' name='current_value'  value='{$current_value}'>

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


						<input type='hidden' name='comment_topic'  value='{$comment_topic}'>
						<input type='hidden' name='comment_body'   value='{$comment_body}'>

						<input type='hidden' name='form_action' value='Cancel'>
						<td align='left'><input type='image' src='images/button_cancel.png' name='form_action' value='Cancel'></td>
						</form>
					</tr>

				</table>
				</td>

			</tr>

			{* CLOSE THE TABLE*}
			</table>



		{* APPROVED *}
		{elseif $approved eq 'yes'}

			<form action='remediation_detail.php' enctype='multipart/form-data' method='POST'>
			<input type='hidden' name='remediation_id' value='{$remediation_id}'>
			<input type='hidden' name='form_action' value='Continue'>

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

			<input type='image' src='images/button_continue.png' name='form_action' value='Continue'>
			</form>

		{/if} {* APPROVED *}

		
	{/if} {* VALIDATED *}


	{* close out the main table and form *}

{/if} {* FORM_ACTION *}