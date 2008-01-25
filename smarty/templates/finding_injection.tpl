<!-- PURPOSE : provides template for the finding_injection page                -->

<!-- ---------------------------------------------------------------------- -->
<!-- HEADER TEMPLATE INCLUDE                                                -->
<!-- ---------------------------------------------------------------------- -->

{include file="header.tpl" title="OVMS" name="Finding Upload"}
<font color="Red">{$error_msg}</font>
<p>Step 1. Download EXCEL templete file from <a href="OVMS_Injection_Template.xls">here</a>.</p>
<p>Step 2. Fill the work sheet with your fingding data and save it as CSV fromat.</p>

<p>Step 3. Upload the CSV file here.
<form action="finding_injection.php" method="POST" enctype="multipart/form-data">
<input type="file" name="csv">
<input type="submit"></form></p>

<p>Step 4. View the injection summary or download error log file which contains data with wrong format then go to step 1.</p>

<br />
<br />
{include file="footer.tpl"}