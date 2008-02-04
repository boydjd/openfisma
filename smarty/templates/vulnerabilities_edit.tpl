<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}

<script language="javascript">

function del_product(para) 
{
	var p_id = para ; 
	document.vuln_prod.remove_product.value = p_id ;

	document.vuln_prod.submit();
}
</script>

{/literal}

<br>

<!-- Header Block -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><b>Vulnerability Detail</b></td>
		<td align="right" valign="bottom">{$now}</td>
	</tr>
</table>
<!-- End Header Block -->

<br>

<!-- Back Button -->
<table width="95%" align="center">
	<tr>
		<td align="left">
			<form method="post" action="vulnerabilities.php">
			<INPUT TYPE="submit"  name="go_back"  value="Return to Vulnerability Summary">
			</form>
		</td>
	</tr>
</table>
<!-- End Back Button -->


{if $view_right eq 1 or $del_right eq 1 or $edit_right eq 1}
{$update_msg}

<br>

<form name="vuln_prod" method="post" action="">
<input  type="hidden" name="vn"  value="{$v_table.vuln_seq}"  >

<table width="95%" align="center" border="0" cellpadding="3" cellspacing="1">
	<tr>
		<td width="363" rowspan="2" align="left" valign="top">
		
			<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2" align="left">
						Vulnerability Name: {$v_table.vuln_type}-{$v_table.vuln_seq} 
					</th>
				</tr>
			</table>
  	    
		<br>
  	    
			<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2"  align="left">Vulnerability Primary Description: </th>
				</tr>
				<tr>
					<td  align="left">  
						<textarea name="vuln_desc_primary" cols="55" rows="2">
							{$v_table.vuln_desc_primary} 
						</textarea>
					</td>
				</tr>
			</table>
  	    
		<br>
		
			<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2"  align="left">Vulnerability Primary Description: </th>
				</tr>
				<tr>
					<td  align="left"> 
						<textarea name="vuln_desc_secondary" cols="55" rows="2">
							{$v_table.vuln_desc_secondary} 
						</textarea>
					</td>
				</tr>
			</table>
		
		<br>
        
			<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2" align="left">
						Vulnerability Severity:  
						<input name="vuln_severity" type="text" value="{$v_table.vuln_severity}"> 
					</th>
				</tr>
			</table>
		
		</td>
		<td colspan="2">
	
			<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2"  align="left">The Vulnerability will cost the loss of </th>
				</tr>
				<tr>
					<td width="50%" align="left">
						<input type="checkbox" name="vuln_loss_confidentiality" value="1"  {$v_table.vuln_loss_confidentiality} >Confidentiality   </td>
          <td width="50%"  align="left"><input type="checkbox" name="vuln_loss_security_admin"  value="1" {$v_table.vuln_loss_security_admin}>Security Admin </td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_loss_availability"  value="1" {$v_table.vuln_loss_availability}>Availability </td>
          <td  align="left"><input type="checkbox" name="vuln_loss_security_user"  value="1"  {$v_table.vuln_loss_security_user}>Security User </td>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_loss_integrity"  value="1" {$v_table.vuln_loss_integrity}>Integrity</td>
          <td  align="left"><input type="checkbox" name="vuln_loss_security_other"   value="1" {$v_table.vuln_loss_security_other}>Security Other </td>
        </tr>
      </table>
	  
	
	
	
	</td>
    <td width="252" colspan="2" align="right">
	  
	  
	  
	  
	  	<table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
        <tr>
          <th  align="left">Vulnerability Range </th>
        </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_range_local" value="1" {$v_table.vuln_range_local} >Local</td>
          </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_range_remote" value="1"  {$v_table.vuln_range_remote} >Remote</td>
          </tr>
        <tr>
          <td  align="left"><input type="checkbox" name="vuln_range_user" value="1"{$v_table.vuln_range_user} >User</td>
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
        <td width="219"><input type="checkbox" name="vuln_type_access" value="1" {$v_table.vuln_type_access}>      Acccess</td>
        <td width="221"><input type="checkbox" name="vuln_type_input_buffer" value="1" {$v_table.vuln_type_input_buffer}>      Input buffer </td>
        <td width="208"  align="left"><input type="checkbox" name="vuln_type_exception" value="1" {$v_table.vuln_type_exception}>      Except</td>
        <td width="161"  align="left"><input type="checkbox" name="vuln_type_other" value="1" {$v_table.vuln_type_other}>      Other</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="vuln_type_input" value="1" {$v_table.vuln_type_input}>       Input</td>
        <td><input type="checkbox" name="vuln_type_race" value="1" {$v_table.vuln_type_race}>      Race</td>
        <td  align="left"><input type="checkbox" name="vuln_type_environment" value="1" {$v_table.vuln_type_environment}>      Environment</td>
        <td  align="left">&nbsp;</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="vuln_type_input_bound" value="1" {$v_table.vuln_type_input_bound}>      Input bound </td>
        <td><input type="checkbox" name="vuln_type_design" value="1" {$v_table.vuln_type_design}>      Design</td>
        <td  align="left"><input type="checkbox" name="vuln_type_config" value="1" {$v_table.vuln_type_config}>      Config</td>
        <td  align="left">&nbsp;</td>
      </tr>
    </table>
	
	</td>
  </tr>
</table>



<br>



<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Product Effected </b></td>
	<td align="right" valign="bottom">&nbsp;</td>
</tr>
</table>
<br	>
  Current Products for Vulnerability : {$v_table.vuln_seq}

    <table width="98%" border="0" cellpadding="3" cellspacing="1" class="tbframe" >
            <tr>
              <th width="5%"  align="center">Remove</th>
              <th width="20%"  align="center">Vendor</th>
              <th width="60%"  align="center">Product</th>
              <th width="15%"  align="center">Version</th>
            </tr>

		<input type="hidden"  name="remove_product" id="{$v_product[row].prod_id}">

  {section name=row loop=$v_product}
  <tr>
    <td  align="center"  class="tdc">
	

        <input type="image"   src="images/del.png" border="0" onClick="del_product({$v_product[row].prod_id})" >
	
	 </td>
    <td  align="left" class="tdc">{$v_product[row].prod_vendor} </td>
    <td  align="left" class="tdc">{$v_product[row].prod_name} </td>
    <td  align="left" class="tdc">{$v_product[row].prod_version}</td>
  </tr>
  {/section}
  </table>





  <br>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Product List </b></td>
	<td align="right" valign="bottom">&nbsp;</td>
</tr>
</table>
<br>
<table width="98%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
	<tr>
	  <td colspan="2" align="left">&nbsp;Product Search: 
		  <input type="text" name="p_keyword" size="35" value="{$p_keyword}"> 
		  <input type="submit" name="submit" value="Search"> 
		  
		  <input type="submit" name="submit" value="Add products"> 
		  <INPUT TYPE="BUTTON" VALUE="Create New Product" ONCLICK="window.location.href='tbadm.php?tid=4&r_do=form'">
		  
		  </td>
    </tr>
	<tr>
	  <td width="63%" align="left">&nbsp;Result of Search:</td>
      <td width="37%" align="right">
	  

	Total pages: {$p_amount}  
	<input  src="" type="submit" name="submit" value="Prev Page" {$prev_page_disabled}> 
	
	<input type="text" name="p_page" size="3" maxlength="3" value="{$p_page}">
	
	<input type="submit" name="submit" value="Next Page" {$next_page_disabled}> 



	  
	  </td>
	</tr>
	<tr>
	  <td colspan="2" align="left">
		
		
		  <table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbframe" >
          
            <tr>
              <th width="5%"  align="center">&nbsp;</th>
              <th width="20%"  align="center">Vendor</th>
              <th width="60%"  align="center">Product</th>
              <th width="15%"  align="center">Version</th>
            </tr>

{section name=row loop=$p_list}  

            <tr>
                <td  align="left"  class="tdc">
				<input type="checkbox" id="{$p_list[row].prod_id}"  value="{$p_list[row].prod_id}" name="product_id[]">
				
				</td>
				<td  align="left" class="tdc">{$p_list[row].prod_vendor}  </td>
				<td  align="left" class="tdc">{$p_list[row].prod_name} </td>
				<td  align="left" class="tdc">{$p_list[row].prod_version}</td>
           </tr>

{/section}



















          </table> 
		  
	  </td>
    </tr>
</table>


   <p  align="center"><input type="submit" name="submit" value="Update Vulnerability">
</p>


</form>








{else}
<p>No right do your request.</p>
{/if}
<p>&nbsp;</p>

{include file="footer.tpl"}
