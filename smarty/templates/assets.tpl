<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<div align="left">

{literal}
<script language="javascript" src="javascripts/func.js"></script>

<script language="javascript">

function redirect(asset_id, action) {

	if (action == 'view') {
		document.asset.asset_id.value = asset_id; 
		document.asset.action = "asset_detail.php"; 
		document.asset.submit();
		return true;
	}
	if (action == 'edit') { 
		document.asset.asset_id.value = asset_id;
		document.asset.action = "asset_modify.php"; 
		document.asset.submit();
		return true;
	}

	return false;

}

function selectall(flag)
{
	
    var objForm = document.asset;
	
    var objLen = objForm.length;
    for (var iCount = 0; iCount < objLen; iCount++)
    {
        
            if (objForm.elements[iCount].type == "checkbox")
            {
                objForm.elements[iCount].checked = flag;
            }
      
    }
}

function do_delete()
{
	var objForm = document.asset;
    var objLen = objForm.length;
	var count=0;
    for (var iCount = 0; iCount < objLen; iCount++)
    {
        
            if (objForm.elements[iCount].type == "checkbox"  && objForm.elements[iCount].checked ==true)
            {
                count++;
            }
      
    }
	if (count<=0)
	{
		alert('You should checked at least one to delete');
		return false;
	}
	else
	{
		if (confirm('Are you sure? '))
		{
			document.asset.action.value='Delete';	
			document.asset.submit();
		}	
	}
	
}

function do_search()
{
	document.asset.action.value='Search';	
	document.asset.submit();
}

function do_order(param)
{
	switch (param)
	{
		case 11 : 
		document.asset.order.value = 'ASC' ;
		document.asset.orderbyfield.value = 'asset_name' ;	
		break;
		case 12 : 
		document.asset.order.value = 'DESC' ;
		document.asset.orderbyfield.value = 'asset_name' ;	
		break;
		
		case 21 : 
		document.asset.order.value = 'ASC' ;
		document.asset.orderbyfield.value = 'system' ;	
		break;
		case 22 : 
		document.asset.order.value = 'DESC' ;
		document.asset.orderbyfield.value = 'system' ;	
		break;
		
		case 31 : 
		document.asset.order.value = 'ASC' ;
		document.asset.orderbyfield.value = 'ip' ;	
		break;
		case 32 : 
		document.asset.order.value = 'DESC' ;
		document.asset.orderbyfield.value = 'ip' ;	
		break;
		
		
		case 41 : 
		document.asset.order.value = 'ASC' ;
		document.asset.orderbyfield.value = 'port' ;	
		break;
		case 42 : 
		document.asset.order.value = 'DESC' ;
		document.asset.orderbyfield.value = 'port' ;	
		break;
		
		
		case 51 : 
		document.asset.order.value = 'ASC' ;
		document.asset.orderbyfield.value = 'product_name' ;	
		break;
		case 52 : 
		document.asset.order.value = 'DESC' ;
		document.asset.orderbyfield.value = 'product_name' ;	
		break;
		
		
		case 61 : 
		document.asset.order.value = 'ASC' ;
		document.asset.orderbyfield.value = 'vendor' ;	
		break;
		case 62 : 
		document.asset.order.value = 'DESC' ;
		document.asset.orderbyfield.value = 'vendor' ;	
		break;
		
		
	}
	
	do_search();
}

</script>
{/literal}

</div>

{if $view_right eq 1 or $del_right eq 1 or $edit_right eq 1} 

<br>

<!-- Heading Block -->
<table class="tbline">              
<tr><td id="tbheading">Asset Search</td><td id="tbtime">{$now}</td></tr>        
</table>
<!-- End Heading Block -->

<br>

<form name="asset" method="post" action="asset.php">
<input type="hidden" name="listall" value="{$listall}">
<input type="hidden" name="action" value="">

<!-- Asset Search -->  
<table width="98%" border="0" align="center" class="tipframe">
	<tr>
    	<td>
		<!-- Asset Search Table -->
        <table border="0" align="left" cellpadding="3" cellspacing="1">
        	<tr>
            	<td align="left"><b>System:<b></td>
	            <td align="left"><b>Vendor:<b></td>
    	        <td align="left"><b>Product:<b></td>
        	    <td align="left"><b>Version:<b></td>
            	<td align="left"><b>IP Address:<b></td>
	            <td align="left"><b>Port:<b></td>
    	        <td align="right">&nbsp;</td>
        	    <td align="left">&nbsp;</td>
			</tr>

         	<tr>
    	        <td align="left">
                	<select name="system">
	                <option value="">--Any--</option>
         			{foreach from=$system_list key=sid item=sname}
					{if $sid eq $system}      
 	             	<option value="{$sid}" selected>{$sname}</option>
					{else}			          
            	 	<option value="{$sid}">{$sname}</option>
                	{/if}
					{/foreach}
              		</select>
				</td>
	            <td align="left"><input name="vendor" type="text" id="vendor" value="{$vendor}"></td>
    	        <td align="left"><input type="text" name="product" value="{$product}"></td>
        	    <td align="left"><input name="version" type="text" id="version" value="{$version}"></td>
            	<td align="left"><input type="text" name="ip" value="{$ip}" maxlength="23"></td>
	            <td align="left"><input type="text" name="port" value="{$port}" size="10"></td>
    	        <td align="right">&nbsp;</td>
        	    <td>
                	<input name="button" type="submit" id="button" value="Search" onClick="javascript:do_search();" style="cursor:hand;">
				</td>
      		</tr>
        </table>
		<!-- End Asset Search Table -->
		</td>
    </tr>
</table>
<!-- End Asset Search -->

<br>

<input type="hidden" name="order" value="{$order}">
<input type="hidden" name="orderbyfield" value="{$orderbyfield}">

<!-- Heading Block -->
<table class="tbline">              
	<tr>
    	<td id="tbheading">Search Results</td>
    	<td align=right>Export Results to: 
			<a href="asset.report.php?f=p" target="_blank"><img src="images/pdf.gif" border="0"></a>
			<a href="asset.report.php?f=x" target="_blank"><img src="images/xls.gif" border="0"></a>
		</td>
    </tr>
</table>
<!-- End Heading Block -->

<br>

{if $filter_data_rownum neq 0}

<!-- Button and Pagination Row -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td> 
			{if $del_right eq 1}
            <input name="button" type="button" id="button" value="Select All" onClick="selectall(true);" style="cursor:hand;">
            <input name="button" type="button" id="button" value="Select None" onClick="selectall(false);" style="cursor:hand;">
            <input name="button" type="button" id="button" value="Delete" onClick="javascript:do_delete();" style="cursor:hand;">
			{/if} 
			{if $add_right eq 1} 
			<input name="button" type="button" id="button" value="Create an Asset" onclick="javascript:location.href='asset.create.php'">
			{/if} 
		</td>
		<td align="right"> 
			<!-- Pagination -->
			<table>
				<tr>
            		<td>
		            	<input type="hidden" name="pageno" value="{$pageno}">
		            	<input type="hidden" name="totalpage" value="{$maxpageno}">
                    	{if $pageno neq "1"}
            			<input name="button" type="button" id="button" value="Previous" onClick="pageskip('asset','prev');" style="cursor:hand;">
                        {/if}
               		</td>
        	    	<td>&nbsp;Page:</td>
            		<td><input type="text" name="pageno" value="{$pageno}" size="5" maxlength="5" readonly="yes">&nbsp;</td>
	            	<td>
                    	{if $pageno neq $maxpageno}
		            	<input name="button" type="button" id="button" value="Next" onClick="pageskip('asset','next');" style="cursor:hand;">
                        {/if}
					</td>
	            	<td align=right>&nbsp; Total pages: <b>{$maxpageno}</b></td>
        		</tr>
			</table>
			<!-- End Pagination -->
		</td>
    </tr>
</table>
<!-- End Button and Pagination Row -->

<!-- Asset Search Results Table -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
	<tr align="center">
		<th nowrap></th>
        <th nowrap>Asset Name
			<input type='image'  src='images/up_arrow.gif'   onClick="do_order(11)"> 
			<input type='image'  src='images/down_arrow.gif' onClick="do_order(12)">
		</th>
        <th nowrap>System
			<input type='image'  src='images/up_arrow.gif'   onClick="do_order(21)"> 
			<input type='image'  src='images/down_arrow.gif' onClick="do_order(22)">
		</th>
		<th nowrap>IP Address
			<input type='image'  src='images/up_arrow.gif'   onClick="do_order(31)"> 
			<input type='image'  src='images/down_arrow.gif' onClick="do_order(32)">
		</th>
        <th nowrap>Port
			<input type='image'  src='images/up_arrow.gif'   onClick="do_order(41)"> 
			<input type='image'  src='images/down_arrow.gif' onClick="do_order(42)">
		</th>
        <th nowrap>Product Name
			<input type='image'  src='images/up_arrow.gif'   onClick="do_order(51)"> 
			<input type='image'  src='images/down_arrow.gif' onClick="do_order(52)">
		</th>
        <th nowrap>Vendor
			<input type='image'  src='images/up_arrow.gif'   onClick="do_order(61)"> 
			<input type='image'  src='images/down_arrow.gif' onClick="do_order(62)">
		</th>
		<!-- If user has edit rights show Edit column -->
        {if $edit_right eq 1}
		<th nowrap>Edit</th>
        {/if} 
		<!-- If user has view rights show View column -->
		{if ($view_right eq 1)}
		<th nowrap>View</th>
        {/if} 
	</tr>
		{foreach key=fname item=fobj from=$filter_data}
    <tr> 
    	<!-- If user has delete rights show the checkbox -->
		{if $del_right eq 1}
		<td align="center" class="tdc"><input type="checkbox" name="aid_{$fobj.asset_id}" value="aid.{$fobj.asset_id} "></td>
		{/if}
		{if $del_right neq 1}
		<td align="center" class="tdc">&nbsp;</td>
		{/if}
		<td class="tdc">&nbsp;{$fobj.asset_name}</td>
      	<td class="tdc">&nbsp;{$fobj.system_name}</td>
      	<td class="tdc">&nbsp;{$fobj.address_ip}</td>
      	<td class="tdc">&nbsp;{$fobj.address_port}</td>
      	<td class="tdc">&nbsp;{$fobj.prod_name}</td>
      	<td class="tdc">&nbsp;{$fobj.prod_vendor}</td>
      	{if $edit_right eq 1}
		<input type='hidden' name='asset_id' value='{$fobj.asset_id}'>
		<td class='tdc' align='center'><img src='images/edit.png' name='edit' onClick="redirect({$fobj.asset_id}, 'edit')" style='cursor:hand;'></td>
      	{/if} 
		{if ($view_right eq 1)}
		<input type='hidden' name='asset_id' value='{$fobj.asset_id}'>
		<td class='tdc' align='center'><img src='images/view.gif' name='view' onClick="redirect({$fobj.asset_id}, 'view')" style='cursor:hand;'></td>
		{/if} 
	</tr>
    {/foreach}
</table>
<!-- End Asset Search Results Table -->

<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
   	<tr>
   		<td> 
			{if $del_right eq 1}
            	<input name="button" type="button" id="button" value="Select All" 	onClick="selectall(true);" 			style="cursor:hand;">
                <input name="button" type="button" id="button" value="Select None" 	onClick="selectall(false);" 		style="cursor:hand;">
                <input name="button" type="button" id="button" value="Delete" 		onClick="javascript:do_delete();" 	style="cursor:hand;">
			{/if} 
            {if $add_right eq 1} 
				<input name="button" type="button" id="button" value="Create an Asset" onclick="javascript:location.href='asset.create.php'" style="cursor:hand;">
			{/if}
		</td>
	</tr>
</table>
</form>

{else}
  <p>No Data found.</p>

{/if}
</form>

{literal} {/literal}

{else}
<p class="errormessage">{$noright}</p>
{/if}

{include file="footer.tpl"} 
