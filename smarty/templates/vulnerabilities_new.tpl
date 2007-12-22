{include file="header.tpl" title="OVMS" name="Vulnerability : New Vulnerability"}
{literal}
<script language="javascript">
function selectall(num, flag) {
	if(num == 0){ 
		return;
	}
	if(num == 1) {
		document.finding.fid.checked = flag;
	}

	if(num > 1) {
		for(var i = 0; i < num; i++) {
			document.finding.fid[i].checked = flag;
		}
	}
}


function select_it(box_id) 
{
	document.vuln_new.box_id.checked = true;
}

</script>
{/literal}
{$return_results}

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Vulnerability: </b> New Vulnerability</td>
	<td align="right" valign="bottom">{$now}</td>
</tr>
</table>
{if $add_right eq 1}
<br>
      <form name="vuln_new" method="post" action="vulnerabilities_prod.php">
<table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
  <tr>
    <td width="333" rowspan="2" align="left" valign="top">

      <table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th  align="left">Vulnerability Primary Description: </th>
        </tr>
        <tr>
          <td  align="left"><textarea name="vuln_desc_primary" rows="3" cols="55">{$vuln_desc_primary}</textarea></td>
        </tr>
      </table>
<br>
      <table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th  align="left">Vulnerability Secondary Description: </th>
        </tr>
        <tr>
          <td  align="left"><textarea name="vuln_desc_secondary" rows="3" cols="55">{$vuln_desc_secondary}</textarea></td>
        </tr>
 		</table>
 
<br> 
      <table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
         <tr>
          <th  align="left"> Vulnerability Severity  
	      <input type="text" name="vuln_severity" size="5" value="{$vuln_severity}" >  </th>
        </tr>
 
 		</table>
 
 
      </td>
    <td colspan="2">
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th colspan="2"  align="left">The Vulnerability will cost the loss of </th>
          </tr>
        <tr>
          <td width="50%"  align="left"><input type="checkbox" name="vuln_loss_confidentiality" value="1" {$vuln_loss_confidentiality} >Confidentiality </td>
          <td width="50%"  align="left"><input type="checkbox" name="vuln_loss_security_admin" value="1" {$vuln_loss_security_admin}>Security Admin </td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_loss_availability" value="1" {$vuln_loss_availability}>Availability </td>
          <td  align="left"><input type="checkbox" name="vuln_loss_security_user" value="1" {$vuln_loss_security_user}>Security User </td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_loss_integrity" value="1" {$vuln_loss_integrity}>Integrity </td>
          <td  align="left"><input type="checkbox" name="vuln_loss_security_other" value="1"  {$vuln_loss_security_other}>Security Other </td>
        </tr>
      </table>
	  
	
	
	
	</td>
    <td width="254" colspan="2" align="right">
	
	
	<table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th  align="left">Vulnerability Range </th>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_range_local" value="1" {$vuln_range_local}>Local</td>
          </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_range_remote" value="1" {$vuln_range_remote}>Remote</td>
          </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_range_user" value="1" {$vuln_range_user}>User</td>
          </tr>
      </table>
	  
	  
	  
	  
	  </td>
  </tr>
  <tr>
    <td  colspan="4">
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
      <tr>
        <th	 colspan="4"  align="left">Type of Vulnerability</th>
      </tr>
      <tr>
        <td width="219"><input type="checkbox" name="vuln_type_access" value="1" {$vuln_type_access}>      Acccess</td>
        <td width="221"><input type="checkbox" name="vuln_type_input_buffer" value="1" {$vuln_type_input_buffer}>      Input buffer </td>
        <td width="208"  align="left"><input type="checkbox" name="vuln_type_exception" value="1" {$vuln_type_exception}>      Except</td>
        <td width="161"  align="left"><input type="checkbox" name="vuln_type_other" value="1" {$vuln_type_other}>      Other</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="vuln_type_input" value="1" {$vuln_type_input}>      Input</td>
        <td><input type="checkbox" name="vuln_type_race" value="1" {$vuln_type_race}>      Race</td>
        <td  align="left"><input type="checkbox" name="vuln_type_environment" value="1" {$vuln_type_environment}>      Environment</td>
        <td  align="left">&nbsp;</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="vuln_type_input_bound" value="1" {$vuln_type_input_bound}>      Input bound </td>
        <td><input type="checkbox" name="vuln_type_design" value="1" {$vuln_type_design}>      Design</td>
        <td  align="left"><input type="checkbox" name="vuln_type_config" value="1" {$vuln_type_config}>      Config</td>
        <td  align="left">&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>

<p  align="center"><input type="submit" name="submit" value="Create new vulnerability"> <input type="submit" name="submit" value="Cancel"> </p>




</form>

{else}
<p>No right do your request.</p>
{/if}
<p>&nbsp;</p>

{include file="footer.tpl"}
