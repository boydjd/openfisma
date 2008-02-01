<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Vulnerability:</b> Detail </td>
	<td align="right" valign="bottom">{$now}</td>
</tr>
</table>
<div align="right">

	<form method="post" action="vulnerabilities.php">
	<!--<INPUT TYPE="submit"  name="go_back"  value="Return to Vulnerability Summary">-->
	<INPUT TYPE="image" name="go_back" src="./images/button_back.png" VALUE="Return to Vulnerability Summary">
	</form>

</div>

{if $view_right eq 1 or $del_right eq 1 or $edit_right eq 1}
<br>
<table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
  <tr>
    <td width="333" rowspan="2" align="left" valign="top">
	
	
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th colspan="2"  align="left">Vulnerability Name:  {$v_table.vuln_type}-{$v_table.vuln_seq} </th>
        </tr>
      </table>
	
	
	<br><table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th colspan="2"  align="left">Vulnerability Description: </th>
        </tr>
        <tr>
          <td  align="left">  {$v_table.vuln_desc_primary}  <br><br>
	  {$v_table.vuln_desc_secondary} </td>
        </tr>
      </table>
	  <br>
	  
	  	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th colspan="2"  align="left"> Vulnerability Severity: {$v_table.vuln_severity}  </th>
        </tr>
      </table>

  	</td>
    <td colspan="2">
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th colspan="2"  align="left">The Vulnerability will cost the loss of </th>
        </tr>
        <tr>
          <td width="50%"  align="left"><input type="checkbox" name="vul_cost" disabled {$v_table.vuln_loss_confidentiality} >Confidentiality   </td>
          <td width="50%"  align="left"><input type="checkbox" name="vul_cost" disabled {$v_table.vuln_loss_security_admin}>Security Admin </td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_loss_availability}>Availability </td>
          <td  align="left"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_loss_security_user}>Security User </td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_loss_integrity}>Integrity</td>
          <td  align="left"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_loss_security_other}>Security Other </td>
        </tr>
      </table>
	  
	
	
	
	</td>
    <td colspan="2" align="right">
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe" align="right">
        <tr>
          <th  align="left">Vulnerability Range </th>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_range_local} >Local</td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_range_remote} >Remote</td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_range_user}>User</td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td  colspan="4">
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
      <tr>
        <th colspan="4"  align="left">Type of Vulnerability</th>
      </tr>
      <tr>
        <td width="219"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_type_access}>      Acccess</td>
        <td width="221"><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_type_input_buffer}>      Input buffer </td>
        <td width="208"  align="left"><input type="checkbox" name="vul_cost" disabled value="Confidentiality" {$v_table.vuln_type_exception}>      Except</td>
        <td width="161"  align="left"><input type="checkbox" name="vul_cost" disabled value="Confidentiality" {$v_table.vuln_type_other}>      Other</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_type_input}>      Input</td>
        <td><input type="checkbox" name="vul_cost" value="Confidentiality"  disabled {$v_table.vuln_type_race}>      Race</td>
        <td  align="left"><input type="checkbox" name="vul_cost" disabled value="Confidentiality" {$v_table.vuln_type_environment}>      Environment</td>
        <td  align="left">&nbsp;</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_type_input_bound}>      Input bound </td>
        <td><input type="checkbox" name="vul_cost" value="Confidentiality" disabled {$v_table.vuln_type_design}>      Design</td>
        <td  align="left"><input type="checkbox" name="vul_cost"  disabled value="Confidentiality" {$v_table.vuln_type_config}>      Config</td>
        <td  align="left">&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>

<br>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Product Effected </b></td>
	<td align="right" valign="bottom">&nbsp;</td>
</tr>
</table>
<br>
<table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
  <tr>
    <th width="15%"  align="left"> Vendor:  </th>
    <th width="70%"  align="left">Product: </th>
    <th width="15%"  align="left">Version:</th>
  </tr>
{section name=row loop=$v_table}  
 {if $v_product[row].prod_vendor != '' } 
 <tr >
    <td  align="left" class="tdc">{$v_product[row].prod_vendor} </td>
    <td  align="left" class="tdc">{$v_product[row].prod_name} </td>
    <td  align="left" class="tdc">{$v_product[row].prod_version}</td>
  </tr>
  {/if}
{/section}
</table>

{else}
<p>No right do your request.</p>
{/if}
<p>&nbsp;</p>

{include file="footer.tpl"}
