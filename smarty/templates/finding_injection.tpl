<!-- PURPOSE : provides template for the finding_injection page                -->

<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<!-- ---------------------------------------------------------------------- -->
<!-- MAIN PAGE DISPLAY                                                      -->
<!-- ---------------------------------------------------------------------- -->

<!-- Heading Block -->
<table class="tbline">              
<tr>
 <td id="tbheading">Fingding Data Injection</td>
 <td id="tbtime">{$now}</td>
</tr>        
</table>
<!-- End Heading Block -->

<br>

<table border="0" cellpadding="3" cellspacing="0" class="tbframe">

	<tr>
		<td align="center">{if $error_msg neq ''}Result:{/if}</td>
		<td align="left">
			<font color="Red">{$error_msg}</font>
		</td>
	</tr>

	<tr>
		<td align="center">Step 1.</td>
		<td align="left">
			Download EXCEL templete file from <a href="OVMS_Injection_Template.xls">here</a>.
		</td>
	</tr>

	<tr>
		<td align="center">Step 2.</td>
		<td align="left">
			Fill the work sheet with your fingding data and save it as CSV fromat.
		</td>
	</tr>

	<tr>
		<td align="center">Step 3.</td>
		<td>Upload the CSV file here.
            <form action="finding_injection.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="csv">
            <input type="submit"></form>
		</td>
	</tr>
	
	<tr>
		<td align="center">Step 4.</td>
		<td>
		  View the injection summary or download error log file which contains data with wrong format then go to step 1.
		</td>
	</tr>


</table>
<br>

{include file="footer.tpl"}