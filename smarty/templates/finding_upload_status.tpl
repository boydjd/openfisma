<!-- Display Header -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 

<!-- Check to see if user has correct permissions -->
{if $upload_right eq 1}

<!-- Heading Block -->
<br>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Finding Upload Status</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<br>
<!-- End Heading Block -->

<!-- Check to see if error message exists, if so display error -->
{if isset($err_msg)}
	<div class="error_msg">{$err_msg}</div>
{/if}

<!-- Check to see if status message exists, if so display error -->
{if isset($status_msg)}
	<div class="status_msg">{$status_msg}</div>
{/if}

<!-- If user does not have correct permissions display error -->
{else}
	<div class="noright">{$noright}</div>
{/if}

<!-- Display Footer -->
{include file="footer.tpl"}

