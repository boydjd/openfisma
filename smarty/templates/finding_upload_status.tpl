<!-- Display Header -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 

<!-- Check to see if user has correct permissions -->
{if $upload_right eq 1}

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

