<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 
{if isset($err_msg)}
  <p><b>Error:</b> {$err_msg}</p><br/>
  {/if}

{if isset($status_msg)}
  <p><b>Status:</b> {$status_msg}</p><br/>
  {/if}

{include file="footer.tpl"}

