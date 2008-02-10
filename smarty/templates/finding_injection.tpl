<!-- PURPOSE : provides template for the finding_injection page                -->

<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<!-- ---------------------------------------------------------------------- -->
<!-- MAIN PAGE DISPLAY                                                      -->
<!-- ---------------------------------------------------------------------- -->

<br>

{if $upload_right eq 1}

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Spreadsheet Upload</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>
<table width="95%" align="center" border="0">

	<tr>
		<td>

			<table border="0" cellpadding="10" cellspacing="0" class="tbframe">
				
				{if $error_msg neq ''}
				
				<tr>
					<td align="center"><b>Results:</b></td>
					<td align="left">
					  <font color="Red">{$error_msg}</font>
					</td>
				</tr>
				
				{/if}

			  <tr>
				  <td align="center" NOWRAP><strong>Step 1.</strong></td>
				<td align="left">
						Download EXCEL templete file from <a href="OpenFISMA_Injection_Template.xls">here</a>.
				</td>
				</tr>

			  <tr>
				<td align="center" NOWRAP><strong>Step 2.</strong></td>
				<td align="left">
						Fill the work sheet with your fingding data and save it as CSV format.
				</td>
				</tr>

			  <tr>
				<td align="center" NOWRAP><strong>Step 3.</strong></td>
				<td>Upload the CSV file here.
		          <form action="finding_injection.php" method="POST" enctype="multipart/form-data">
           				<input type="file" name="csv">
   			      <input type="submit"></form>
					</td>
				</tr>
	
			  <tr>
				<td align="center" NOWRAP><strong>Step 4.</strong></td>
				<td>
		  				View the injection summary or download error log file which contains data with wrong format then go to step 1.
				</td>
				</tr>

			</table>

		</td>
	</tr>

</table>

{else}
<p class="errormessage">{$noright}</p>
{/if}

{include file="footer.tpl"}