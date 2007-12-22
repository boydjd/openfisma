{include file="header.tpl" title="OVMS" name="Add Product for  Vulnerability"}
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
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Vulnerability: Associate Product with Vulnerability</b></td>
	<td align="right" valign="bottom">{$now}</td>
</tr>
</table>
{if  $add_right eq 1}


<br>
<form name="vuln_prod" method="post" action="">
<input type="hidden" name="current_vuln_id" value="{$current_vuln_id}">

  Current Products for Vulnerability : {$current_vuln_id}
    <table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbframe" >
            <tr>
              <th width="5%"  align="center">Remove</th>
              <th width="20%"  align="center">Vendor</th>
              <th width="60%"  align="center">Product</th>
              <th width="15%"  align="center">Version</th>
            </tr>
  {section name=row loop=$vp_list}
  <tr>
    <td  align="center"  class="tdc">
	<input  type="image" src="images/del.png"  id="{$vp_list[row].prod_id}"   value="{$vp_list[row].prod_id}"  name="remove_product">   
	
	 </td>
    <td  align="left" class="tdc">{$vp_list[row].prod_vendor} </td>
    <td  align="left" class="tdc">{$vp_list[row].prod_name} </td>
    <td  align="left" class="tdc">{$vp_list[row].prod_version}</td>
  </tr>
  {/section}
          </table>
  <br>
Product
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
	<tr>
	  <td align="left">Product Search: 
		  <input type="text" name="p_keyword" size="35" value="{$para}"> 
		  <input type="submit" name="submit" value="Search"> 
		  <input type="submit" name="submit" value="All Products">
		  <br>  <br>
		  Result of Search:
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
	    <p  align="center"><input type="submit" name="submit" value="Add products">
</p>
		  
	  </td>
    </tr>
</table>






</form>

{else}
<p>No right do your request.</p>
{/if}
<p>&nbsp;</p>

{include file="footer.tpl"}
