<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}

<script language="javascript">
function pageskip(flag) 
{
	var v_page = parseInt(document.vulnerabilities.v_page.value);

	if(flag) {
		v_page = v_page + 1; // next page
	}
	else {
		v_page = v_page - 1; // prev page
	}

	if(v_page < 1)		
		v_page = 1; // first page
	
	document.vulnerabilities.v_page.value = v_page;

	document.vulnerabilities.submit();
}

function search_page() 
{
	document.vulnerabilities.v_search.value = 'Search' ;
	document.vulnerabilities.v_page.value = 1 ;
	document.vulnerabilities.submit();
}

function order_page(para) 
{
	if ( para == 11 )
		document.vulnerabilities.v_order.value = 'order by vuln_seq DESC' ;
	else if ( para == 12 )
		document.vulnerabilities.v_order.value = 'order by vuln_seq ASC' ;
	else if ( para == 21 )
		document.vulnerabilities.v_order.value = 'order by vuln_type DESC' ;
	else if ( para == 22 )
		document.vulnerabilities.v_order.value = 'order by vuln_type ASC' ;
	else if ( para == 31 )
		document.vulnerabilities.v_order.value = 'order by vuln_date_published DESC' ;
	else if ( para == 32 )
		document.vulnerabilities.v_order.value = 'order by vuln_date_published ASC' ;
	else if ( para == 41 )
		document.vulnerabilities.v_order.value = 'order by vuln_severity DESC' ;
	else if ( para == 42 )
		document.vulnerabilities.v_order.value = 'order by vuln_severity ASC' ;

	document.vulnerabilities.submit();
}
</script>
{/literal}


{if $view_right eq 1 or $del_right eq 1 or $edit_right eq 1}

<br>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><b>Vulnerabilities Summary</td>
		<td align="right" valign="bottom">{$now}</td>
	</tr>
</table>
<br>

<!-- Vulnerability Summary Table -->
<table width="70%" align="center" border="0" cellpadding="5" cellspacing="0" class="tbframe">
  <tr align="center">
	<th>Vulnerability Definitions</th>
	<th>Count</th>
  </tr>

		{section name=row loop=$v_data}  

  <tr>
		{ if  $v_data[row].v_type == 'APP' }

		<td width="60%" class='tdc'>
			<b>{$v_data[row].v_type} Definition: </b> AppDetective Vulnerabilities
		</td>
		<td width="40%" align='center' class='tdc'>
			{$v_data[row].v_total} 
		</td>

		{/if} 
		{ if  $v_data[row].v_type == 'CVE' }

		<td width="60%" class='tdc'>
			<b>{$v_data[row].v_type} Definition: </b> Common Vulnerabilities and Exposures Vulnerabilities
		</td>
		<td width="40%" align='center' class='tdc'>
			{$v_data[row].v_total} 
		</td>
		
			{/if} 
			{ if  $v_data[row].v_type == 'EVD' }
		
		<td width="60%" class='tdc'>
			<b>{$v_data[row].v_type} Definition: </b> Common Vulnerabilities and Exposures Vulnerabilities
		</td>
		<td width="40%" align='center' class='tdc'>  
			{$v_data[row].v_total}   
		</td>
		
			{/if} 
			{ if  $v_data[row].v_type == 'MAN' }
		
		<td width="60%" class='tdc'>
			<b>{$v_data[row].v_type} Definition: </b> Enterprise Vulnerability Definition Vulnerabilities
		</td>
		<td width="40%" align='center' class='tdc'>
			{$v_data[row].v_total}   
		</td>
		
			{/if} 
			{ if  $v_data[row].v_type == 'NES' }
  
		<td width="60%" class='tdc'>
			<b>{$v_data[row].v_type} Definition: </b>  Nessus Vulnerabilities
		</td>
		<td width="40%" align='center' class='tdc'>  
			{$v_data[row].v_total} 
		</td>
		
			{/if} 
			{ if  $v_data[row].v_type == 'SHA' }

		<td width="60%" class='tdc'>
			<b>{$v_data[row].v_type} Definition: </b>  Security Shadow Scanner Vulnerabilities
		</td>
		<td width="40%" align='center' class='tdc'>  
			{$v_data[row].v_total} 
		</td>

			{/if} 
	</tr>

	{/section}
    
	<tr>
        <td align='right' class='tdc'><b>TOTAL VULNERABILITIES: </b></td>
        <td align='center' class='tdc'><b>{$tot_define}</b></td>
	</tr>
</table>
<!-- END Vulnerability Summary Table -->

<br>

<!-- Header Break -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><b>Vulnerability Search</b></td>
	</tr>
</table>
<!-- End Header Break -->

<br>

<form name="vulnerabilities" method="post" action="vulnerabilities.php">
<input type="hidden" name="listall" value="{$listall}">

<!-- Vulnerability Search Table -->
<table width="95%" align="center" border="0" cellpadding="3" cellspacing="1" class="tipframe">
	<tr>
		<td colspan="2" align="left">
			Keyword Search: <input type="text" name="v_keyword" size="50" value="{$v_keyword}">
		</td>
	</tr>
	<tr>
	  <td colspan="2" align="left">
  
		{section name=row loop=$v_data}  
	
		<input 	name="my_v_type[]"  
				type="checkbox" 
				value="{$v_data[row].v_type}" 
						{$v_data[row].v_checked}>{$v_data[row].v_type} Definition   
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;    
	
		{/section}
	  
		</td>
    </tr>
	<tr >
		<td width="435" align="left" >
	
			<table border="0" cellpadding="3" cellspacing="1">
				<tr>
					<td>Date Posted</td>
					<td>&nbsp;</td>
					<td>From:</td>
					<td><input 	type="text" 
								name="v_startdate" 
								size="12" 
								maxlength="10" 	
								value="{$v_startdate}"><br>mm/dd/yyyy
					</td>
					<td>
						<span onclick="javascript:show_calendar('vulnerabilities.v_startdate');">
						<img src="images/picker.gif" width=24 height=22 border=0></span>
					</td>
					<td>&nbsp;</td>
					<td>To:</td>
					<td>
						<input 	type="text" 
								name="v_enddate" 
								size="12" 
								maxlength="10" 
								value="{$v_enddate}"><br>mm/dd/yyyy
					</td>
					<td><span onclick="javascript:show_calendar('vulnerabilities.v_enddate');">
						<img src="images/picker.gif" width=24 height=22 border=0></span>
					</td>
				</tr>
			</table>
		</td>
		<td width="753">
			<input type="submit" value="Search" onClick="search_page()" >
			<input type="hidden"  name="v_search" value="">
		</td>
	</tr>
</table>
<!-- End Vulnerability Search Table -->

<br>

<!-- Header Block -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
	<tr>
		<td valign="bottom"><b>Vulnerability List </b></td>
	</tr>
</table>
<!-- End Header Block -->

<br>
	
<!-- Pagination -->
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="78%" align="right" valign="bottom">
			Total pages: {$total_pages}  
		</td>
		<td width="15%" align="right" valign="bottom">	
			{if $v_page ne 1}
			<input type="button" value="Previous" onclick="pageskip(false);">
			{/if}
		</td>
		<td width="3%" align="right" valign="bottom">
			<input type="text" name="v_page" size="3" maxlength="3" value="{$v_page}">
		</td>
		<td width="4%" align="right" valign="bottom">
			{if $v_page ne $total_pages }
			<input type="button" value="Next" onclick="pageskip(true);">
			{/if}
		</td>
	</tr>
</table>
<!-- End Pagination -->

<br>

<!-- Vulnerability Results Table -->
<table width="95%" align="center" border="1" cellpadding="5" cellspacing="0" class="tbframe">
	<tr align="center">
		<th width="10%">
			ID
			<input type="hidden"  name="v_order" value=" ">
			<input type="image"   src="images/down_arrow.gif" border="0" onClick="order_page(11)" >
			<input type="image"   src="images/up_arrow.gif" border="0" onClick="order_page(12)" >
		</th>
		<th width="10%">
			Name
			<input type="image"   src="images/down_arrow.gif" border="0" onClick="order_page(21)" >
			<input type="image"   src="images/up_arrow.gif" border="0" onClick="order_page(22)" >
		</th>
		<th width="60%">
			Description
		</th>
		<th width="10%">
			Date
			<input type="image"   src="images/down_arrow.gif" border="0" onClick="order_page(31)" >
			<input type="image"   src="images/up_arrow.gif" border="0" onClick="order_page(32)" >
		</th>
		<th width="10%">
			Severity
			<input type="image"   src="images/down_arrow.gif" border="0" onClick="order_page(41)" >
			<input type="image"   src="images/up_arrow.gif" border="0" onClick="order_page(42)" >
		</th>
		<th width="10%">Edit</th>
		<th width="10%">View</th>
	</tr>

</form>

	{section name=row loop=$v_table}

	<tr>
		<td align="center">{$v_table[row].v_seq}</td>
		<td align="center">{$v_table[row].v_type}-{$v_table[row].v_seq}</td>
		<td align="left">{$v_table[row].v_desc}</td>
		<td align="center">{$v_table[row].v_date}</td>
		<td align="center">{$v_table[row].v_severity}</td>
		<td align="center">
			<form method="post" action="vulnerabilities_edit.php">
			<INPUT  type="hidden" name="vn" value="{$v_table[row].v_seq}" >
			<INPUT  type="hidden" name="pass_search_para" value="{$pass_search_para}" >
			<INPUT  type="hidden" name="pass_page_no" value="{$pass_page_no}" >
			<INPUT TYPE="image" SRC="images/edit.png" BORDER="0" ALT="Edit Vulnerability">
			</form>
		</td>
		<td align="center">
			<form method="post" action="vulnerabilities_detail.php">
			<INPUT  type="hidden" name="vn" value="{$v_table[row].v_seq}" >
			<INPUT TYPE="image" SRC="images/view.gif" BORDER="0" ALT="View Vulnerability">
			</form>
		</td>
	</tr>

	{/section}

</table>
<!-- End Vulnerability Results Table -->


{else}
<p>No right do your request.</p>
{/if}

{include file="footer.tpl"}
