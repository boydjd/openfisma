<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}

<link type="text/css" rel="StyleSheet" href="stylesheets/sortabletable.css" />

<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/sortabletable.js"></script>

<script language="javascript">
function submit_update() 
{
	if (checkValidator())  
	{
		document.assetcreate.edit.value = 'update' ;
		document.assetcreate.submit();	
	}	
	else 
		return false;
}

function changeAddrType(obj){
	var addrtype = obj.value;
	document.assetcreate.ip.readOnly = true;
	switch (addrtype)
	{
		case '1': document.assetcreate.ip.readOnly = false;document.assetcreate.ip.focus(); break;
		case '2': document.assetcreate.ip.readOnly = false;document.assetcreate.ip.focus(); break;
	}
}

function getProductMsg(obj){
	var id = obj.value;
	
	//alert(document.getElementById('prod_vendor_19').innerHTML);
	//eval("var temp = document.getElementById(\'prod_vendor_" + id + "\').innerHTML;");
	eval("document.getElementById('vendor').innerHTML=document.getElementById('prod_vendor_"+id+"').innerHTML");
	eval("document.getElementById('product').innerHTML=document.getElementById('prod_name_"+id+"').innerHTML");
	eval("document.getElementById('version').innerHTML=document.getElementById('prod_version_"+id+"').innerHTML");
	
}

function initProductMsg(id){
	
	
	//alert(document.getElementById('prod_vendor_19').innerHTML);
	//eval("var temp = document.getElementById(\'prod_vendor_" + id + "\').innerHTML;");
	eval("document.getElementById('vendor').innerHTML=document.getElementById('prod_vendor_"+id+"').innerHTML");
	eval("document.getElementById('product').innerHTML=document.getElementById('prod_name_"+id+"').innerHTML");
	eval("document.getElementById('version').innerHTML=document.getElementById('prod_version_"+id+"').innerHTML");
	
}

function checkip(ip,flag) 
{ 
	var i;
	var segnum = 0;
	if (flag) 
		segnum = 4;   //ipv4
	else 
		segnum = 6;   //ipv6

	var scount=0; 
	
	
	var iplength = ip.length; 
	var Letters = "1234567890."; 
	for (i = 0; i < ip.length; i++) 
	{ 
	   var CheckChar = ip.charAt(i); 
	   if (Letters.indexOf(CheckChar) == -1) 
	   { 
			return false; 
	   } 
	} 

	
	
	var strs;
	addr = ip.split(".");
	scount = addr.length;
	
	  
	if(scount!=segnum) 
	{ 
	    return false; 
	} 
	
	pattern1 = /^[0-9]$/;
	pattern = /^[1-9][0-9]{1,2}$/;
	
	for (i = 0; i < scount; i++)
	{
		str = addr[i];
		if (str.length == 0) return false;
		if (str.length == 1) 
		{
			if (!pattern1.test(str)) return false;
		}	
		if (str.length == 2 || str.length ==3 )
		{
			if (pattern1.test(str)) return false;
		}
		if (str.length > 3) return false;
		if (str< 0 || str >255) 
		{
			return false; 
		} 
		
	}

	return true;
}

function checkValidator(){
	var form = document.assetcreate;
	
	var error = '';
	var p_int = /^[1-9]\d{0,4}$/;
	
	if (form.assetname.value=='') 
	{
		error = '\'Asset Name\' could not be empty\n';
	}	
	if (form.system.selectedIndex==0)
	{
		error = error + 'Select a option from \'System\' list\n';
	}	
	if (form.network.selectedIndex==0)
	{
		error = error + 'Select a option from \'Network\' list\n';	
	}
	if (form.port.value.length>0)
	{
		var port = form.port.value;
		if (!p_int.test(port))
		{
			error = error + 'The \'Port\' is a invalid port number\n';	
		}	
		else if (port<1 || port>65535)
		{
			error = error + 'The \'Port\' is out of range (1 - 65535)\n';	
		}
	}
	
	var addrtype=0;
	if (form.addrtype[0].checked) addrtype=form.addrtype[0].value;
	if (form.addrtype[1].checked) addrtype=form.addrtype[1].value;
	
	if (addrtype==1 || addrtype==2)
	{
		if (addrtype == 1 && !checkip(form.ip.value,true))
		{
			error = error + 'The \'IP Address\' is a invalid IPv4 format\n';	
		}
		if (addrtype == 2 && !checkip(form.ip.value,false))
		{
			error = error + 'The \'IP Address\' is a invalid IPv6 format\n';	
		}
			
	}
	var prod_id=0;
	if (typeof(form.prod_id)=="object")
	{
	
		if  (isNaN(form.prod_id.length))
			prod_id=form.prod_id.value;
		else;
			for (var i=0; i<form.prod_id.length; i++)
			{
				
				if (form.prod_id[i].checked)  prod_id=form.prod_id[i].value;
			}
	}
	
	if (prod_id<=0)
	{
		error = error + 'Please choose a option from product list\n';	
	}
	
	if (error.length>0)
	{
		alert(error);
		return false;
	}
	else
		return true;

}

function do_create()
{
	if (checkValidator())  
	{
		document.assetcreate.add.value='add';	
		document.assetcreate.search.value='';
		document.assetcreate.submit();
	}	
	else return false;
	
}

function do_update()
{
	if (checkValidator())  
	{
		document.assetcreate.edit.value='update';	
		document.assetcreate.search.value='';
		document.assetcreate.submit();
	}	
	else 
		return false;
	
}

function do_search()
{
	document.assetcreate.edit.value='';	
	document.assetcreate.search.value='Search';
	document.assetcreate.submit();
}

function pageskip(flag) {
	var pageno = parseInt(document.assetcreate.pageno.value);

	if(flag) {
		pageno = pageno + 1; // next page
	}
	else {
		pageno = pageno - 1; // prev page
	}

	if(pageno < 1)		
		pageno = 1; // first page
	
	document.assetcreate.pageno.value = pageno;
	document.assetcreate.edit.value='';	
	document.assetcreate.search.value='Search';
	document.assetcreate.submit();
}

//function check
var selected_prod_in_searchdata = false;

</script>
{/literal}

<br>

{if $edit_right eq 1}

<!-- Heading Block -->
<table class="tbline">              
	<tr>
		<td id="tbheading">Asset Detail</td>
		<td id="tbtime">{$now}</td>
	</tr>        
</table>
<!-- End Heading Block -->

<br>

	<form name="assetcreate"  method="post" action="{$formaction}">
	<input type="hidden" name="listall" value="{$listall}">
	<input type="hidden" name="asset_id" value="{$asset_id}">
	<input type="hidden" name="edit" value="">
	<input type="hidden" name="add" value="">
	<input type="hidden" name="search" value="">
	<input type="hidden" name="current_prod_id" value="{$current_prod_id}">


	{* ASSET DETAIL TABLE *}
	<table width=100% cellpadding='3' cellspacing='1' border='0' class='tipframe'>

		<th align='left' colspan='2'>Asset, Product and Address Information</th>

		{* ASSET AND PRODUCT DETAIL ROW *}
	    <tr width='100%'>

			{* ASSET INFORMATION *}
		    <td width='50%' valign=top>
   		    <table width="100%" border="0" cellpadding="3" cellspacing="1" class='tipframe'>

				<th align='left' colspan='2'>Asset Information</th>

	        	<tr>
   	    		   	<td valign="center" align="left"><b>Name </b></td>
    	        	<td valign="center" align="left"><input name="assetname" type="text" id="assetname" value="{$assetname}" size="23" maxlength="23"></td>
   		    	</tr>

				<tr>

		            <td valign="center" align="left"><b>System:</b></td>
    		
			        <td valign="center" align="left"><select name="system">
       			        <option value="">--Select--</option>

				        {foreach from=$system_list key=sid item=sname}

							{if $sid eq $system }<option value="{$sid}" selected>{$sname}</option>
				        	{else}<option value="{$sid}">{$sname}</option>
		    		        {/if}

						{/foreach}
		
				      	</select>

					</td>

				</tr>

				<tr>
					<td></td>
					<td></td>
				</tr>

	        </table>		
			</td>

			{* PRODUCT INFORMATION *}
			<td width='50%' align=right valign=top>
	        <table width="100%" border="0" cellpadding='3' cellspacing='1' class='tipframe'>

				<th align='left' colspan='2'>Product Information <i>(select existing product from search or create new)</i></th>

   	    		  	<tr>
    		   	    	<td width=80><b>Vendor:</b> </td>
   		        		<td  id=vendor align=left>  {$current_prod_info.prod_vendor}  </td>
		          	</tr>

   	    		  	<tr>
       		    	<td><b>Product:</b></td>
   		        		<td id=product align=left> {$current_prod_info.prod_name} </td>
		          	</tr>

   	      			<tr>
       			    	<td><b>Version:</b></td>
    	   	    		<td id=version align=left> {$current_prod_info.prod_version} </td>
		         	</tr>

   		    </table>
			</td>

		</tr>

		{* ADDRESS INFORMATION ROW *}
		<tr>

			{* ADDRESS INFORMATION *}
			<td colspan='2'>
			<table width='100%' border='0' cellpadding='3' cellspacing='1' class='tipframe'>

				<th align='left' colspan='2'>Address Information</th>

				{* ADDRESS INFORMATION TABLE *}
				<tr>

					<td>
					<table width='100%' border='0' cellpadding='3' cellspacing='1' class='tipframe'>

		        		<tr>

							<td width='50%' valign="center" align="left"><b>Network: </b>

								<select name="network" id="network">
    			        		    <option value="">--Select--</option>

							        {foreach from=$network_list key=sid item=sname}

										{if $sid eq $network} <option value="{$sid}" selected>{$sname}</option> 
						    	    	{else} <option value="{$sid}">{$sname}</option>
										{/if}
	
									{/foreach}

								</select>

							</td>

		        	    	<td width='50%' valign="center" align="left"><b>IP Address: </b>
								<input type="text" name="ip" value="{$ip}" maxlength="23" size="23">
       		    	  			<input type="radio" name="addrtype" value="1" onClick="javascript:changeAddrType(this);" {$chked1}> IPV4
    	    			    	<input type="radio" name="addrtype" value="2" onClick="javascript:changeAddrType(this);" {$chked2}> IPV6 
							</td>

		    	      	</tr>

			          	<tr>
							<td width='50%'>&nbsp;</td>
        		    		<td width='50%' valign="center" align="left"><b>Port: </b><input type="text" name="port" value="{$port}" maxlength="5" size="5"></td>
       			  		</tr>

					</table>
					</td>

				</tr>

			</table>
			</td>

		</tr>

		{*
		<tr>
			<td colspan='2'>

				{if $action eq "edit"}
       				<!--input type="button" name="Edit" value="Update Asset" onClick="javascript:do_update()"-->
			        <!-- <img type="input" name="Edit" value="Update Asset" onClick="javascript:do_update();" src="images/button_update.png" style="cursor:hand;" > -->
					  <input type="image"  src="images/button_update.png"  onClick="submit_update()">
					
				{/if}

			</td>
		</tr>
		*}

	</table>

	<br>


	{* PRODUCT SEARCH SECTION ----------------------------------------------- *}
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
		<tr>
			<td valign="bottom"><b>Product:</b> Search Filters</td>
	  	</tr>
	</table>

	<br>

	{* PRODUCT SEARCH *}
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class='tipframe'>

	    <tr>
	        <td align='left'>
				<b>Search Products for: </b><input type="text" name="product_search" value="{$product_search}" size="40">
       		  	<!--input type="button" name="Search" value="Search" onclick="javascript: do_search();"-->
   		  		<img type="input" src="images/button_search.png" onclick="javascript: do_search();" style="cursor:hand;" valign='bottom'> &nbsp;&nbsp;&nbsp; 
			</td>

	    </tr>

	</table>

	<br>

	{* PRODUCT SEARCH RESULTS SECTION --------------------------------------- *}
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">

		<tr>
			<td align='left'  valign='bottom'><b>Product:</b> Search Results</td>
			<td align='right' valign='bottom'>
				<!-- <input type='hidden' name="pageno" value="{$pageno}"> -->
				Page <b>{$pageno}</b> of <b>{$maxpageno}</b>
			</td>
		</tr>

	</table>

	<br>

	{* DISPLAY OPTIONS BAR *}
	<table width='100%' border='0' cellpadding='3' cellspacing='1'>

		<tr>
			<td width="7%" align'left'><img onClick="javascript:location.href='tbadm.php?tid=4&r_do=form';" src="images/button_create.png" style="cursor:hand;" ></td>
			<td width="84%" align='right'  valign="top">&nbsp;
				{if $pageno neq "1"}<span style="cursor: hand" onclick="pageskip(false);"><img src="images/button_prev.png" border="0"></span>{/if}      			
			</td>
		    <td width="2%" align='right'  valign="top"><input type="text" name="pageno" value="{$pageno}" size="3"></td>
		    <td width="7%" align='left'  valign="top">{if $pageno neq $maxpageno}<span style="cursor: hand" onclick="pageskip(true);"><img src="images/button_next.png" border="0"></span>{/if}</td>
		</tr>

	</table>

	{* SEARCH RESULTS LIST *}
	<table width='100%' border='0' cellpadding='3' cellspacing='1' class='tipframe'>

       	<th></th>
       	<th>Vendor</th>
   	  	<th>Product</th>
		<th>Version</th>

		{* RESULTS LIST *}
        {section name=row loop=$prod_search_data}

			<tr>
 				<td class="tdc">&nbsp;

				{if $prod_id eq $prod_search_data[row].sid}
	  				<input type="radio" name="prod_id" value="{$prod_search_data[row].sid}" onclick="javascript:getProductMsg(this);" Checked >
    	   			<script>selected_prod_in_searchdata = true; </script>
   	        	{else}
    		        <input type="radio" name="prod_id" value="{$prod_search_data[row].sid}" onclick="javascript:getProductMsg(this);" >
		    	{/if}

			</td>
	
        	 	<td class="tdc" id="prod_vendor_{$prod_search_data[row].sid}">{$prod_search_data[row].svendor}</td>
    	     	<td class="tdc" id="prod_name_{$prod_search_data[row].sid}">{$prod_search_data[row].sname}</td>
	  	     	<td class="tdc" id="prod_version_{$prod_search_data[row].sid}">{$prod_search_data[row].sversion}</td>

        	</tr>

  		{/section}

	</table>


	{* DUPLICATE BAR ABOVE LIST AND INCLUDE THE UPDATE BUTTON*}
	<table width='100%' border='0' cellpadding='3' cellspacing='1'>

		<tr>
			<td align'left'>

				<img onClick="javascript:location.href='tbadm.php?tid=4&r_do=form';" src="images/button_create.png" style="cursor:hand;" >
        
				{if $action eq "create"}
	    	    	<!--input type="button" name="Add" value="Create Asset" onClick="javascript:do_create()"-->
    			    <img type="input" name="Add" value="Create Asset" onClick="javascript:do_create();" src="images/button_create.png" style="cursor:hand;" > 
				{/if} 

				{if $action eq "edit"}
        			<!--input type="button" name="Edit" value="Update Asset" onClick="javascript:do_update()"-->
			        <!-- <img type="input" name="Edit" value="Update Asset" onClick="javascript:do_update();" src="images/button_update.png" style="cursor:hand;" > -->
		            
					<!-- <input type="image"  src="images/button_update.png"  alt="Update"  title="Update" onClick="do_update()"> -->
					
					  <input type="image"  src="images/button_update.png"  onClick="submit_update()">
				{/if} 

			</td>

			<td align='right'>&nbsp;
				{if $pageno neq "1"}<span style="cursor: hand" onclick="pageskip(false);"><img src="images/button_prev.png" border="0"></span>{/if}
	   	   		{if $pageno neq $maxpageno}<span style="cursor: hand" onclick="pageskip(true);"><img src="images/button_next.png" border="0"></span>{/if}
			</td>
		</tr>

	</table>

	</form>

	<script type="text/javascript">

	var st2 = new SortableTable(document.getElementById("searchdata"),
		["None", "String", "CaseInsensitiveString", "String"]);

	</script>

	<script>if (selected_prod_in_searchdata) initProductMsg({$prod_id});</script>

{else}
<p class="errormessage">{$noright}</p>
{/if}

{include file="footer.tpl"}
