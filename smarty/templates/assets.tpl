<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<div align="left">

{literal}
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

function pageskip(flag) {
	var pageno = parseInt(document.asset.pageno.value);
	if(flag) {
		pageno = pageno + 1; // next page
	}
	else {
		pageno = pageno - 1; // prev page
	}

	if(pageno < 1)		
		pageno = 1; // first page
	
	document.asset.pageno.value = pageno;
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


{* SUMMARY SECTION ---------------------------------------------------------- *}

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
    	<td valign="bottom"><b>Assets:</b> Summary</td>
	    <td align="right" valign="bottom">{$now}</td>
	</tr>
</table>


{* DISPLAY THE SUMMARY TABLE *}
{if $view_right eq 1 or $del_right eq 1 or $edit_right eq 1} <br>
	<table align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">

		<tr><th colspan="3" align=left>&nbsp;Assets Summary</th></tr>	
    	{assign var="summary_data_svalue_total" value="0"}

	    {* LOOP THROUGH THE SUMMARY DATA *}
		{section name=summary loop=$summary_data}

			{if $smarty.section.summary.rownum % 3 == "1" && $smarty.section.summary.rownum >= 1}
			<tr>
			{/if}
	
			{if $summary_data[summary].sname != "Total"}
			    <td class="tdc">&nbsp;{$summary_data[summary].sname}[{$summary_data[summary].svalue}]</td>

				{if $smarty.section.summary.rownum % 3 == "0" && $smarty.section.summary.rownum >= 1}
					</tr>
				{/if}

				{else}

					{if $smarty.section.summary.rownum % 3 == "2"}
						<td class="tdc">&nbsp;</td><td class="tdc">&nbsp;</td></tr>
					{/if}

					{if $smarty.section.summary.rownum % 3 == "0"}
						<td class="tdc">&nbsp;</td></tr>
					{/if}
		
					{assign var="summary_data_svalue_total" value=$summary_data[summary].svalue}

				{/if}
	
		{/section} 

    	<tr><td colspan="3" align='left' class='tdc'>&nbsp;<b>Total Asset:</b> [{$summary_data_svalue_total}]</th></tr>	

	</table>

<br>


{* FILTERS SECTION ---------------------------------------------------------- *}

<!--input name="button" type="button" id="button" value="Create an Asset" onclick="javascript:location.href='asset.create.php'"-->
<!--img type="input" name="Add" value="Create Asset" onClick="javascript:location.href='asset.create.php'" src="images/button_create.png" style="cursor:hand;" --> <br>


<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Assets:</b> Filters</td>
  	</tr>
</table>

<br>

<form name="asset" method="post" action="asset.php">
<input type="hidden" name="listall" value="{$listall}">
{*<input type="hidden" name="action" value="">*}
  
<table border="0" cellpadding="3" cellspacing="1" class="tipframe">
	<tr>
    	<td>
		<table border="0" align="left" cellpadding="3" cellspacing="1">
        	<tr>
            	<td width="78" align="left">System:</td>
	            <td width="168">Vendor:</td>
    	        <td width="168" align="left">Product:</td>
        	    <td width="168">Version:</td>
            	<td width="168" align="left">IP Address:</td>
	            <td width="70" align="left">Port:</td>
    	        <td width="3" align="right">&nbsp;</td>
        	    <td width="81">&nbsp;</td>
			</tr>

         	<tr>
    	        <td align="left"><select name="system">

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

        	    <td><!--input type="submit" name="submit" value="Search"-->
			        <!--<img type="input" value="Search" style="cursor:hand;" src="images/button_search.png" onClick="javascript:do_search();">-->
			        <input type="image" value="Search" style="cursor:hand;" src="images/button_search.png" onClick="javascript:do_search();">
				</td>

      		</tr>

        </table>
		</td>

    </tr>

    <tr><td>&nbsp;</td></tr>

</table>

<br>

<input type="hidden" name="order" value="{$order}">
<input type="hidden" name="orderbyfield" value="{$orderbyfield}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">

	<tr>
    	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Assets:</b> List</td>
    	<td align=right> Export to: 
			<a href="asset.report.php?f=p" target="_blank"><img src="images/pdf.gif" border="0"></a>
			<a href="asset.report.php?f=x" target="_blank"><img src="images/xls.gif" border="0"></a>
	 
		</td>
    </tr>

</table>
{*</form>*}
<br>


{* SUMMARY LIST ------------------------------------------------------------- *}

{*<form name="asset" method="post" action="asset.php">*}

{if $filter_data_rownum neq 0}
<table width="100%" border="0" cellpadding="0" cellspacing="0">

	<tr>
    	<td> 

			{if $del_right eq 1}
        		<span style="cursor: hand;"><img name="all" value="Select All" onclick="selectall(true);" src="images/button_select_all.png"></span>&nbsp;
        		<img name="none" value="Select None" onclick="selectall(false);" src="images/button_select_none.png" style="cursor:hand;"> 
			{/if}

			{if $del_right eq 1} &nbsp;
		        <!--input type="submit" name="submit" value="Delete"-->
		        <input type="image" value="Delete" src="images/button_delete.png" style="cursor:hand;"  onClick="javascript:do_delete();"> 
			{/if} 

		</td>

		<td align="right"> 
		<table>

        	<tr>
            	<td>{if $pageno neq "1"}<span><input type="image" style="cursor: hand" onclick="pageskip(false);" src="images/button_prev.png" border="0"></span>{/if}</td>
        	    <td>&nbsp;Page:</td>
            	<td><input type="text" name="pageno" value="{$pageno}" size="5" maxlength="5" readonly="yes">&nbsp;</td>
	            <td>{if $pageno neq $maxpageno}<span><input type="image" style="cursor: hand" onclick="javascript: pageskip(true);" src="images/button_next.png" border="0"></span>{/if}</td>
	            <td align=right>&nbsp; Total pages: <b>{$maxpageno}</b></td>
        	</tr>

		</table>
		</td>

    </tr>

</table>

{* COLUMN HEADERS *}
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbframe">
   
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


        {if $edit_right eq 1}
        <!--edit right-->
	        <th nowrap>Edit</th>
        {/if} 

		{if ($view_right eq 1)}
	        <!--view right-->
    	    <th nowrap>View</th>
        {/if} 

	</tr>
    
    {foreach key=fname item=fobj from=$filter_data}
    <tr> 

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

&nbsp;

{* REPLICATE THE ROW PRECEDING THE SUMMARY TABLE *}
{*<form name="asset" method="post" action="asset.php">
<input type="hidden" name="listall" value="{$listall}">
<input type="hidden" name="action" value="">*}

<table width="100%" border="0" cellpadding="0" cellspacing="0">
   	<tr>
   		<td> 
		{if $del_right eq 1}
        	<!--input type="button" name="all" value="Select All" onclick="selectall(true);"-->
		    <img name="all" value="Select All" onclick="selectall(true);" src="images/button_select_all.png" style="cursor:hand;">&nbsp;
        	<!--input type="button" name="none" value="Select None" onclick="selectall(false);"-->
		    <img name="all" value="Select None" onclick="selectall(false);" src="images/button_select_none.png" style="cursor:hand;"> 
		{/if}

		{if $del_right eq 1} &nbsp;
        	<!--input type="submit" name="submit" value="Delete"-->
		    <input type="image" value="Delete" src="images/button_delete.png" style="cursor:hand;" onClick="javascript:do_delete();"> 
		{/if} 
		</td>
		
		<td align="right"></td>

   	</tr>

</table>
</form>

{else}
  <p>No Data found.</p>

{/if}
</form>

{literal} {/literal}

{else}
<p>No right do your request.</p>

{/if}

<p>&nbsp;</p>


{include file="footer.tpl"} 
