<!-- Display Header -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 

<!-- Check to see if error message exists, if so display error -->
{if isset($err_msg)}
	<div class="error_msg">{$err_msg}</div>
{/if}

<!-- Check to see if status message exists, if so display error -->
{if isset($status_msg)}
	<p><b>Status:</b> {$status_msg}</p><br/>
{/if}

<!-- Display Footer -->
{include file="footer.tpl"}

