<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 
{literal} {/literal}
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
  <tr>
    <td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>{$pageName}</b></td>
    <td align="right" valign="bottom">{$now}</td>
  </tr>
</table>
{if $view_right eq 1} <br>
<FIELDSET>
<LEGEND>
<LABEL> General Infomation </LABEL>
</LEGEND>
<br>
<table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr>
    <td width="30%" valign="center" align="left"><b>Asset Name </b></td>
    <td valign="center" align="left">{$assetname}</td>
  </tr>
  <tr>
    <td valign="center" align="left"><b>System:</b></td>
    <td valign="center" align="left"> {foreach from=$system_list key=sid item=sname} {if $sid eq $system } {$sname} {/if} {/foreach} </td>
  </tr>
  <tr>
    <td valign="center" align="left"><b>Network:</b></td>
    <td valign="center" align="left"> {foreach from=$network_list key=sid item=sname} {if $sid eq $network } {$sname} {/if} {/foreach} </td>
  </tr>
  <tr>
    <td valign="center" align="left"><b>IP Address:</b></td>
    <td valign="center" align="left">{$ip}</td>
  </tr>
  <tr>
    <td valign="center" align="left"><b>Port:</b></td>
    <td valign="center" align="left">{$port}</td>
  </tr>
</table>
<br>
</FIELDSET>
<br>
<FIELDSET>
<LEGEND>
<LABEL> Product List</LABEL>
</LEGEND>
<table width="100%"  border="0"  cellpadding="5" cellspacing="0">
  {section name=row loop=$prod_search_data}
  <tr>
    <td width="30%"><b>Vendor</b></td>
    <td align="left" id="prod_vendor_{$prod_search_data[row].sid}">{$prod_search_data[row].svendor}</td>
  </tr>
  <tr>
    <td><b>Product</b></td>
    <td align="left" id="prod_name_{$prod_search_data[row].sid}">{$prod_search_data[row].sname}</td>
  </tr>
  <tr>
    <td><b>Version</b></td>
    <td align="left" id="prod_version_{$prod_search_data[row].sid}">{$prod_search_data[row].sversion}</td>
  </tr>
  {/section}
</table>
</FIELDSET>
<br>
{else}
<p>No right do your request.</p>
{/if}
<p>&nbsp;</p>
{include file="footer.tpl"} 
