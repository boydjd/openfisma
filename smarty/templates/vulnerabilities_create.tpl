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

function validate_submission() 
{
  if(document.vuln_prod.vuln_severity.value.match(/^\d+$/) == null ||
     document.vuln_prod.vuln_severity.value <= 0 ||
     document.vuln_prod.vuln_severity.value >= 100) {
    alert("Please set a severity value between 0 and 100");
    return false;
    }
  else {
    return true;
    }
}

// This call gets around IE's refusal to POST the value of an image
// input element
function set_submit_value(val) {
  document.vuln_prod.submit_val.value = val;
}

</script>

{/literal}

<b>{$create_success}</b>

<!-- Header Block -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><b>Vulnerability Creation</b></td>
		<td align="right" valign="bottom">{$now}</td>
	</tr>
</table>
<!-- End Header Block -->

{if $add_right eq 1}
{$update_msg}

<form name="vuln_prod" method="post" action="">
<input type="hidden" name="submit_val">
<input type="hidden" name="vn"  value="{$vuln_seq}">

<table width="98%" align="center">
	<tr>
		<td align="left">
			<input type="submit" name="submit" 
				onClick="set_submit_value('Create New Vulnerability');return validate_submission();" 
				value="Create New Vulnerability">
		</td>
	</tr>
</table>

<!-- Vulnerability Detail Table -->
<table width="95%" align="center" border="0" cellpadding="3" cellspacing="1">
	<tr>
		<td width="363" rowspan="2" align="left" valign="top">  	      
		
			<table width="100%" border="0" cellpadding="5" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2"  align="left">Vulnerability Primary Description: </th>
				</tr>
				<tr>
					<td align="left">
						<textarea name="vuln_desc_primary" cols="55" rows="3">
							{$vuln_desc_primary}
						</textarea>
					</td>
				</tr>
			</table>
			
			<br>
			
			<table width="100%" border="0" cellpadding="5" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2"  align="left">Vulnerability Secondary Description: </th>
				</tr>
				<tr>
					<td align="left">
						<textarea name="vuln_desc_secondary" cols="55" rows="3">
							{$vuln_desc_secondary}
						</textarea>
					</td>
				</tr>
			</table>
		
			<br>
          
			<table width="100%" border="0" cellpadding="5" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2"  align="left"> 
						Vulnerability Severity:  
						<input name="vuln_severity" type="text" 
								value="{$vuln_severity}" > (value 0-100)
					</th>
				</tr>
			</table>
      
		</td>
		<td colspan="2">
	
			<table width="100%" border="0" cellpadding="5" cellspacing="1" class="tipframe">
				<tr>
					<th colspan="2" align="left">The Vulnerability will cost the loss of </th>
				</tr>
				<tr>
					<td width="50%" align="left">
						<input type="checkbox" name="vuln_loss_confidentiality" value="1"
						{$vuln_loss_confidentiality_checked} > Confidentiality   
					</td>
					<td width="50%" align="left">
						<input type="checkbox" name="vuln_loss_security_admin"  value="1"
						{$vuln_loss_security_admin_checked} > Security Admin 
					</td>
				</tr>
				<tr>
					<td align="left">
						<input type="checkbox" name="vuln_loss_availability"  value="1"
						{$vuln_loss_availability_checked}>Availability 
					</td>
					<td align="left">
						<input type="checkbox" name="vuln_loss_security_user"  value="1"
						{$vuln_loss_security_user_checked} > Security User 
					</td>
				</tr>
				<tr>
					<td align="left">
						<input type="checkbox" name="vuln_loss_integrity"  value="1"
						{$vuln_loss_integrity_checked} > Integrity
					</td>
					<td align="left">
						<input type="checkbox" name="vuln_loss_security_other"   value="1"
						{$vuln_loss_security_other_checked} > Security Other 
					</td>
				</tr>
			</table>
	  
		</td>
		<td width="252" colspan="2" align="right">
	  
			<table width="100%" border="0" cellpadding="5" cellspacing="1" class="tipframe" align="right">
				<tr>
					<th align="left">Vulnerability Range </th>
				</tr>
				<tr>
					<td align="left">
						<input type="checkbox" name="vuln_range_local" value="1" 
						{$vuln_range_local_checked} > Local
					</td>
				</tr>
				<tr>
					<td align="left">
						<input type="checkbox" name="vuln_range_remote" value="1" 
						{$vuln_range_remote_checked} > Remote
					</td>
				</tr>
				<tr>
					<td align="left">
						<input type="checkbox" name="vuln_range_user" value="1"
						{$vuln_range_user_checked} > User
					</td>
				</tr>
			</table>
		
		</td>
	</tr>
	<tr>
		<td  colspan="4">

			<table width="100%" border="0" cellpadding="5" cellspacing="1" class="tipframe">
				<tr>
					<th	colspan="4" align="left">Type of Vulnerability</th>
				</tr>
				<tr>
					<td width="219">
						<input type="checkbox" name="vuln_type_access" value="1" 
						{$vuln_type_access_checked} > Acccess
					</td>
					<td width="221">
						<input type="checkbox" name="vuln_type_input_buffer" value="1"
						{$vuln_type_input_buffer_checked} > Input buffer 
					</td>
					<td width="208" align="left">
						<input type="checkbox" name="vuln_type_exception" value="1"
						{$vuln_type_exception_checked} > Except
					</td>
					<td width="161" align="left">
						<input type="checkbox" name="vuln_type_other" value="1" 
						{$vuln_type_other_checked} > Other
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="vuln_type_input" value="1" 
						{$vuln_type_input_checked} > Input
					</td>
					<td>
						<input type="checkbox" name="vuln_type_race" value="1" 
						{$vuln_type_race_checked} > Race
					</td>
					<td align="left">
						<input type="checkbox" name="vuln_type_environment" value="1"
						{$vuln_type_environment_checked} > Environment
					</td>
					<td align="left">&nbsp;</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="vuln_type_input_bound" value="1"
						{$vuln_type_input_bound_checked} > Input bound 
					</td>
					<td>
						<input type="checkbox" name="vuln_type_design" value="1" 
						{$vuln_type_design_checked} > Design
					</td>
					<td align="left">
						<input type="checkbox" name="vuln_type_config" value="1" 
						{$vuln_type_config_checked} > Config
					</td>
					<td align="left">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<!-- End Vulnerability Table -->

<br>

<!-- Header Block -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><b>Products Affected by Vulnerability</b></td>
		<td align="right" valign="bottom">&nbsp;</td>
	</tr>
</table>
<!-- End Header Block -->

<br>

<!-- Products Affected by Vulnerability Table -->
<table width="95%" align="center" border="0" cellpadding="5" cellspacing="1" class="tbframe" >
	<tr>
		<th width="5%"  align="center">Remove</th>
		<th width="20%"  align="center">Vendor</th>
		<th width="60%"  align="center">Product</th>
		<th width="15%"  align="center">Version</th>
	</tr>

		<input type="hidden"  name="remove_product" id="{$v_product[row].prod_id}">
		{section name=row loop=$selected_product}
  
	<tr>
		<td align="center" class="tdc">
			<input type="image" src="images/del.png" border="0"
				onClick="del_product({$selected_product[row].prod_id})">
		</td>
		<td align="left" class="tdc">{$selected_product[row].prod_vendor}</td>
		<td align="left" class="tdc">{$selected_product[row].prod_name}</td>
		<td align="left" class="tdc">{$selected_product[row].prod_version}</td>
	</tr>
  
		{/section}

</table>
<!-- End Products Affected by Vulnerability Table -->

<br>

<!-- Header Block -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><b>Product List</b></td>
		<td align="right" valign="bottom">&nbsp;</td>
	</tr>
</table>
<!-- End Header Block -->

<br>

<!-- Product List Table -->
<table width="95%" align="center" border="0" cellpadding="3" cellspacing="1" class="tipframe">
	<tr>
		<td colspan="2" align="left">&nbsp;Product Search: 
			<input type="text" name="p_keyword" size="35" value="{$p_keyword}"> 
			<input type="submit" name="submit_search" onClick="set_submit_value('Search');" value="Search"> 
			<input type="submit" onClick="set_submit_value('Add Products');"  value="Add Products"> 
			<input type="button" onclick="javascript:location.href='tbadm.php?tid=4&r_do=form'" value="Create">
		</td>
    </tr>
	<tr>
		<td width="63%" align="left">&nbsp;Result of Search:</td>
		<td width="37%" align="right">
			Total pages: {$p_amount}  
			<input type="submit" name="submit_prev" value="Prev Page" 
				onClick="set_submit_value('Prev Page');" {$prev_page_disabled}>
			<input type="text" name="p_page" size="3" maxlength="3" value="{$p_page}">
			<input type="submit" name="submit_next" value="Next Page" 
				onClick="set_submit_value('Next Page');" {$next_page_disabled}>
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
						<input type="checkbox" id="{$p_list[row].prod_id}"  
							value="{$p_list[row].prod_id}" name="product_id[]">
					</td>
					<td align="left" class="tdc">{$p_list[row].prod_vendor|default:"&nbsp;"}</td>
					<td align="left" class="tdc">{$p_list[row].prod_name|default:"&nbsp;"}</td>
					<td align="left" class="tdc">{$p_list[row].prod_version|default:"&nbsp;"}</td>
				</tr>

					{/section}

			</table> 
		  
		</td>
    </tr>
</table>
<!-- End Product List Table -->

</form>

{else}
<p>No right do your request.</p>
{/if}

{include file="footer.tpl"}
