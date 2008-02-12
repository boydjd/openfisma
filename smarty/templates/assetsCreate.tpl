<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}

<link type="text/css" rel="StyleSheet" href="stylesheets/sortabletable.css" />
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/sortabletable.js"></script>
<script language="javascript">

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
	
	eval("document.getElementById('vendor').innerHTML=document.getElementById('prod_vendor_"+id+"').innerHTML");
	eval("document.getElementById('product').innerHTML=document.getElementById('prod_name_"+id+"').innerHTML");
	eval("document.getElementById('version').innerHTML=document.getElementById('prod_version_"+id+"').innerHTML");
	
}
function initProductMsg(id){
	
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
	var p_int = /^[0-9]\d{0,4}$/;
	
	if (form.assetname.value=='') 
	{
		error = "Asset Name must not be empty\n";
	}	

	if (form.system.selectedIndex==0)
	{
		error = error + "Please make a selection from the System list\n";
	}	

	if (form.network.selectedIndex==0)
	{
		error = error + "Please make a selection from the Network list\n'";	
	}

	if (form.port.value.length>=0)
	{
		var port = form.port.value;
		if (!p_int.test(port))
		{
			error = error + port + " is an invalid port number\n";	
		}	
		else if (port < 0 || port > 65535)
		{
			error = error + "Port " + port + " is out of range (1 - 65535)\n";	
		}
	}
	
	var addrtype=0;
	if (form.addrtype[0].checked) addrtype=form.addrtype[0].value;
	if (form.addrtype[1].checked) addrtype=form.addrtype[1].value;
	
	if (addrtype==1 || addrtype==2)
	{
		if (addrtype == 1 && !checkip(form.ip.value,true))
		{
			error = error + "IP Address is an invalid IPv4 format\n";	
		}
		if (addrtype == 2 && !checkip(form.ip.value,false))
		{
			error = error + "IP Address is an invalid IPv6 format\n";	
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
		error = error + 'Please make a selection from the product list\n';	
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

<!-- check user has rights to add an asset -->
{if $add_right eq 1}

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Asset Creation</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<table width="810" border="0" align="center">
	<tr>
		<form name="assetcreate" method="post" action="{$formaction}">
		<input type="hidden" name="listall" value="{$listall}">
		<input type="hidden" name="aid" value="{$aid}">
		<input type="hidden" name="edit" value="">
		<input type="hidden" name="add" value="">
		<input type="hidden" name="search" value="">

		<td>
            <input type="button" name="button" value="Create Asset" onClick="javascript:do_create();" style="cursor:pointer;"> 
        </td>
   	</tr>
	<tr>
    	<td>
			<table border="0" width="810">
				<tr>
					<td rowspan="2">
						<!-- General Information Table -->
                        <table border="0" width="100%" cellpadding="5" class="tipframe">
						<th align="left" colspan="2"> General Information</th>
                        <tr>
							<td valign="center" align="left"><b>Asset Name </b></td>
							<td valign="center" align="left">
                            	<input name="assetname" type="text" id="assetname" value="{$assetname}" size="23" maxlength="23">
                        	</td>
						</tr>
						<tr>
							<td valign="center" align="left"><b>System:</b></td>
							<td valign="center" align="left"><select name="system">
								<option value="">--Select--</option>
								{foreach from=$system_list key=sid item=sname}
								{if $sid eq $system }
								<option value="{$sid}" selected>{$sname}</option>
								{else}
								<option value="{$sid}">{$sname}</option>
								{/if}
								{/foreach}
								</select>
							</td>
						</tr>
						<tr>
							<td valign="center" align="left"><b>Network:</b></td>
							<td valign="center" align="left"><select name="network" id="network">
								<option value="">--Select--</option>
									{foreach from=$network_list key=sid item=sname}
									{if $sid eq $network }
    							<option value="{$sid}" selected>{$sname}</option>
									{else}
								<option value="{$sid}">{$sname}</option>
									{/if}
									{/foreach}
								</select>
							</td>
						</tr>
						<tr>
							<td valign="center" align="left"><b>IP Address:</b></td>
							<td valign="center" align="left"><input type="text" name="ip" value="{$ip}" maxlength="23" size="23">
							<input type="radio" name="addrtype" value="1" onClick="javascript:changeAddrType(this);" {$chked1}> IPV4
							<input type="radio" name="addrtype" value="2" onClick="javascript:changeAddrType(this);" {$chked2}> IPV6 </td>
						</tr>
						<tr>
							<td valign="center" align="left"><b>Port:</b></td>
							<td valign="center" align="left"><input type="text" name="port" value="{$port}" maxlength="5" size="5"></td>
						</tr>
					</table>
						<!-- General Information Table -->
					</td>
					<td valign="top">
						<!-- Product Search Block -->
						<table border="0" width="100%" cellpadding="5" class="tipframe">
							<th align="left" colspan="2"> Product Search</th>
                            <tr>
								<td><b>Product Name: </b> 
									<input type="text" name="product_search" value="{$product_search}" size="20">
                                    <input type="button" name="button" value="Search" onclick="javascript: do_search();" style="cursor:pointer;">
                                    <input type="button" name="button" value="Create Product" onClick="javascript:location.href='tbadm.php?tid=4&r_do=form';" style="cursor:pointer;">
								</td>
							</tr>
						</table>
                        <!-- End Product Search Block -->
					</td>
  				</tr>
                <tr>

					<td valign="bottom">
					<!-- Current Product Block -->
					<table border="0" width="100%" cellpadding="5" class="tipframe">
						<th align="left" colspan="2"> Current Selected Product</th>
                        <tr>
							<td align="right" width="80"><b>Vendor:</b></td>
							<td id=vendor align=left></td>
						</tr>
						<tr>
							<td align="right"><b>Product:</b></td>
							<td id=product align=left></td>
						</tr>
						<tr>
							<td align="right"><b>Version:</b></td>
							<td id=version align=left></td>
						</tr>
					</table>
					<!-- End Current Product Block -->
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
    	<td>
        	<table align="center" cellpadding="5" class="tipframe">
				<th align="left">Product List</th>
                <tr>
                	<td>
                    	<table border="0" width="800" cellpadding="0" cellspacing="0">
							<tr>
								<td valign="center" align="left">
                        
                        			<table border="0" width="100%" cellpadding="0" cellspacing="0">
										<tr>
											<td>
                                				<!-- Table for Pagination -->
                                    			<table width="100%" border="0">
													<tr>
														<td><b>Result of Search:</b></td>
														<td align=right>
                                            				<!-- Begin Pagination -->
                                                			<table border="0">
			                    								<tr>
            			            								<td>{if $pageno neq "1"}
                                                            			<input type="button" name="button" value="Previous" onclick="pageskip(false);" style="cursor:pointer;"> 
                                                            			{/if}
                                                        			</td>
                    			    								<td>&nbsp;Page:</td>
                        											<td>
                                										<input type="text" name="pageno" value="{$pageno}" size="5" maxlength="5" readonly="yes">&nbsp;
                                									</td>
                        											<td>
                                                        				{if $pageno neq $maxpageno}
                                                                        <input type="button" name="button" value="Next" onclick="pageskip(true);" style="cursor: pointer;">
                                                                        {/if}
                                									</td>
																	<td>&nbsp; Total:  <b>{$maxpageno}</b> pages</td>
                      											</tr>
                    										</table>
                                                            <!-- End Pagination -->
                                    					</td>
													</tr>
              									</table>
                                                <!-- End Table for Pagination -->
											</td>
										</tr>
										<tr>
											<td>
                    							<!-- Product Results Summary -->
   												<table border="0"  width=100%" class="sort-table" id="searchdata">
													<thead>
                  										<tr>	
                    										<td>Select</td>
                    										<td>Vendor</td>
                    										<td>Product</td>
                    										<td>Version</td>
                  										</tr>
                									</thead>
                
                										{section name=row loop=$prod_search_data}
 													<tr>
                  										<td class="tdc">&nbsp; {if $prod_id eq $prod_search_data[row].sid}
                    										<input type="radio" name="prod_id" value="{$prod_search_data[row].sid}" onclick="javascript:getProductMsg(this);" Checked >
                    										<script>selected_prod_in_searchdata = true; </script>
                    										{else}
                    										<input type="radio" name="prod_id" value="{$prod_search_data[row].sid}" onclick="javascript:getProductMsg(this);" >
                    										{/if} 
                                                      	</td>
                  										<td class="tdc" id="prod_vendor_{$prod_search_data[row].sid}">{$prod_search_data[row].svendor|default:"&nbsp;"}</td>
                  										<td class="tdc" id="prod_name_{$prod_search_data[row].sid}">{$prod_search_data[row].sname|default:"&nbsp;"}</td>
                  										<td class="tdc" id="prod_version_{$prod_search_data[row].sid}">{$prod_search_data[row].sversion|default:"&nbsp;"}</td>
                									</tr>
                										{/section}
              									</table>		
												<!-- Product Results Summary -->
											</td>
										</tr>
									</table>

 								</td>
 							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</td>
</table>

</form>

<script type="text/javascript">

var st2 = new SortableTable(document.getElementById("searchdata"),
	["None", "String", "CaseInsensitiveString", "String"]);

</script>
<script>if (selected_prod_in_searchdata) initProductMsg({$prod_id});</script>
<br>

{else}
<p class="errormessage">{$noright}</p>
{/if}

{include file="footer.tpl"} 
