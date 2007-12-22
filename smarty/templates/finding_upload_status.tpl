{include file="header.tpl" title="OVMS" name="Finding Upload Status"}
{if isset($err_msg)}
  <p><b>Error:</b> {$err_msg}</p><br/>
  {/if}

{if isset($status_msg)}
  <p><b>Status:</b> {$status_msg}</p><br/>
  {/if}

{include file="footer.tpl"}

