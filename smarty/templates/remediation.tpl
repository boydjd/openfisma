<!-- PURPOSE : provides the summary listing of items in remediation         -->

<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.js"></script>
<script language="javascript">

$(document).ready(function(){
    $(':radio[@name="radio_id"]').change(function(){
        if(this.checked){
            $(this).prevAll(':text').attr('name',$(this).val()+'_id');
        }
    });
});

function pageskip(flag) 
{
	var v_page = parseInt(document.filters.remediation_page.value);

	if(flag == true) 
	{
		v_page = v_page + 1; // next page
	}
	else if (flag == false) 
	{
		v_page = v_page - 1; // prev page
	}

	if(v_page < 1)		
		v_page = 1; // first page
	
	document.filters.remediation_page.value = v_page;

	document.filters.submit();
}

function firstpage() 
{
	document.filters.remediation_page.value = 1;

	document.filters.submit();
}

function search_page() 
{
	document.filters.v_search.value = 'Search' ;
	document.filters.v_page.remediation_page = 1 ;
	document.filters.submit();
}

function order_page(para) 
{
	//alert ("1111") ;
	
	if ( para == 11 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'remediation_id' ;		
	}
	else if ( para == 12 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'remediation_id' ;		
	}
	else if ( para == 21 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'finding_source' ;		
	}
	else if ( para == 22 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'finding_source' ;		
	}
	else if ( para == 31 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'asset_owner' ;		
	}
	else if ( para == 32 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'asset_owner' ;
	}
	else if ( para == 41 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'action_owner' ;
	}
	else if ( para == 42 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'action_owner' ;
	}
	else if ( para == 51 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'remediation_type' ;
	}
	else if ( para == 52 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'remediation_type' ;
	}
	else if ( para == 61 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'remediation_status' ;		
	}
	else if ( para == 62 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'remediation_status' ;		
	}
	else if ( para == 71 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'remediation_date_created' ;		
	}
	else if ( para == 72 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'remediation_date_created' ;		
	}
	else if ( para == 81 )
	{
		document.order_by_ID.sort_order.value = 'ASC' ;
		document.order_by_ID.sort_by.value = 'action_date_est' ;		
	}
	else if ( para == 82 )
	{
		document.order_by_ID.sort_order.value = 'DESC' ;
		document.order_by_ID.sort_by.value = 'action_date_est' ;		
	}

	document.order_by_ID.submit();
	
}

</script>
{/literal}

{if $view_right eq 1}

<br>

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Remediation Summary</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

<!-- SUMMARY TABLE -->
<table align="center" cellpadding="5" class="tbframe">
	<tr align="center">
		<th>Action Owner</th>
		<th>New</th>
		<th>Open</th>
		<th>EN</th>
		<th>EO</th>
		<!--th>EP</th-->
		<th>EP (SSO)</th>
		<th>EP (S&P)</th>
		<th>ES</th>
		<th>CLOSED</th>
		<th>Total</th>
	</tr>

	<!-- SUMMARY LOOP -->
	{foreach item=system from=$summary}
	<tr>
		<td width='45%' align='left'   class='tdc'>({$system.action_owner_nickname}) {$system.action_owner_name}</td>
		<td align='center' class='tdc'>{if $system.NEW eq ""}-{else}{$system.NEW}{/if}</td>
		<td align='center' class='tdc'>{if $system.OPEN eq ""}-{else}{$system.OPEN}{/if}</td>
		<td align='center' class='tdc'>{if $system.EN eq ""}-{else}{$system.EN}{/if}</td>
		<td align='center' class='tdc'>{if $system.EO eq ""}-{else}{$system.EO}{/if}</td>
		<!--td align='center' class='tdc'>{if $system.EP eq ""}-{else}{$system.EP}{/if}</td-->
		<td align='center' class='tdc'>{if $system.EP_SSO eq ""}-{else}{$system.EP_SSO}{/if}</td>
		<td align='center' class='tdc'>{if $system.EP_SNP eq ""}-{else}{$system.EP_SNP}{/if}</td>
		<td align='center' class='tdc'>{if $system.ES eq ""}-{else}{$system.ES}{/if}</td>
		<td align='center' class='tdc'>{if $system.CLOSED eq ""}-{else}{$system.CLOSED}{/if}</td>		
		<td align='center' class='tdc'><b>{if $system.TOTAL eq ""}0{else}{$system.TOTAL}{/if}</b></td>
	</tr>
	{/foreach}

	<!-- SUMMARY TOTALS -->
	<tr>
		<td width='45%' align='center' class='tdc'><b>TOTALS</b></td>
		<td align='center' class='tdc'><b>{if $totals.NEW   eq ""}0{else}{$totals.NEW}{/if}</b></td>
		<td align='center' class='tdc'><b>{if $totals.OPEN  eq ""}0{else}{$totals.OPEN}{/if}</b></td>
		<td align='center' class='tdc'><b>{if $totals.EN    eq ""}0{else}{$totals.EN}{/if}</b></td>
		<td align='center' class='tdc'><b>{if $totals.EO    eq ""}0{else}{$totals.EO}{/if}</b></td>
		<!--td align='center' class='tdc'><b>{if $totals.EP    eq ""}0{else}{$totals.EP}{/if}</b></td-->
		<td align='center' class='tdc'><b>{if $totals.EP_SSO eq ""}0{else}{$totals.EP_SSO}{/if}</b></td>
		<td align='center' class='tdc'><b>{if $totals.EP_SNP eq ""}0{else}{$totals.EP_SNP}{/if}</b></td>
		<td align='center' class='tdc'><b>{if $totals.ES    eq ""}0{else}{$totals.ES}{/if}</b></td>
		<td align='center' class='tdc'><b>{if $totals.CLOSED    eq ""}0{else}{$totals.CLOSED}{/if}</b></td>		
		<td align='center' class='tdc'><b>{if $totals.TOTAL eq ""}0{else}{$totals.TOTAL}{/if}</b></td>
	</tr>

</table>

<br>

<!-- ---------------------------------------------------------------------- -->
<!-- FILTERS                                                                -->
<!-- ---------------------------------------------------------------------- -->

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Remediation Search</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<br>

{* REMEDIATION FORM *}
<form name="filters" method="post" action="remediation.php">
<input type='hidden' name='sort_order' value='{$sort_order}'>
<input type='hidden' name='sort_by'    value='{$sort_by}'>

<!-- Begin Filter Table -->
<table align="center" border="0" cellpadding="3" cellspacing="1" width="95%" class="tipframe">
	<tr> {* NON-DATE FILTERS ROW *}
		<td><b>Finding Source: </b><br>
			<select name='filter_source'>
				<option value='any'>--- Any Source ---</option>
				{section name=row loop=$finding_sources}
				<option {if $filter_source eq $finding_sources[row].source_id}selected{/if} value='{$finding_sources[row].source_id}'>
				({$finding_sources[row].source_nickname}) {$finding_sources[row].source_name}
				</option>
				{/section}
			</select>
		</td>
		<td>
		<b>ID: </b><i>(You may select multiple IDs by using a comma separated list - x,y,z)</i><br>
		<input type="text" size="70" name="remediation_ids" value="{$remediation_ids}">
		</td>
	</tr>
	<tr>
		<td ><b> Mitigation Strategy:</b><br>
			<select name='filter_type'>
				<option {if $filter_type eq 'any' }selected{/if} value='any'>--- Any Type ---</option>
				<option {if $filter_type eq 'NONE'}selected{/if} value='NONE'>(NONE) Unclassified</option>
				<option {if $filter_type eq 'CAP' }selected{/if} value='CAP'>(CAP) Corrective Action Plan</option>
				<option {if $filter_type eq 'AR'  }selected{/if} value='AR'>(AR) Accepted Risk</option>
				<option {if $filter_type eq 'FP'  }selected{/if} value='FP'>(FP) False Positive</option>
			</select> 
		</td>
		<td width="318" valign="top"><b> Finding Status:</b><br>
			<select name='filter_status'>
				<option {if $filter_status eq 'any'       }selected{/if} value='any'       >--- Any Status ---</option>
				<option {if $filter_status eq 'NEW'       }selected{/if} value='NEW'       >(NEW) Awaiting Mitigation Type and Approval</option>
				<option {if $filter_status eq 'OPEN'      }selected{/if} value='OPEN'      >(OPEN) Awaiting Mitigation Approval</option>
				<option {if $filter_status eq 'EN'        }selected{/if} value='EN'        >(EN) Evidence Needed</option>
				<option {if $filter_status eq 'EO'        }selected{/if} value='EO'        >(EO) Evidence Overdue</option>
				<option {if $filter_status eq 'EP'        }selected{/if} value='EP'        >(EP) Evidence Provided</option>
				<option {if $filter_status eq 'EP-SSO'    }selected{/if} value='EP-SSO'    >(EP-SSO) Evidence Provided to SSO</option>
				<option {if $filter_status eq 'EP-SNP'    }selected{/if} value='EP-SNP'    >(EP-S&P) Evidence Provided to S&P</option>
				<option {if $filter_status eq 'ES'        }selected{/if} value='ES'        >(ES) Evidence Submitted to IV&V</option>
				<!--option {if $filter_status eq 'REJ-SSO'}selected{/if} value='REJ-SSO'   >(REJ-SSO) Evidence Rejected by SSO (in testing)</option-->
				<!--option {if $filter_status eq 'REJ-SNP'}selected{/if} value='REJ-SNP'   >(REJ-SNP) Evidence Rejected by S&P (in testing)</option-->
				<!--option {if $filter_status eq 'REJ-IVV'}selected{/if} value='REJ-IVV'   >(REJ-IVV) Evidence Rejected by IV&V (in testing)</option-->
				<option {if $filter_status eq 'CLOSED'    }selected{/if} value='CLOSED'    >(CLOSED) Officially Closed</option>
				<option {if $filter_status eq 'NOT-CLOSED'}selected{/if} value='NOT-CLOSED'>(NOT-CLOSED) Not Closed</option>
				<option {if $filter_status eq 'NOUP-30'}selected{/if} value='NOUP-30'   >(NOUP-30) 30+ Days Since Last Update</option>
				<option {if $filter_status eq 'NOUP-60'}selected{/if} value='NOUP-60'   >(NOUP-60) 60+ Days Since Last Update</option>
				<option {if $filter_status eq 'NOUP-90'}selected{/if} value='NOUP-90'   >(NOUP-90) 90+ Days Since Last Update</option>
			</select>
		</td>
	</tr>
	<tr>
		<td ><b>Asset Owners: </b> <br/>
		  	<select name='filter_asset_owners'>
				<option {if $filter_asset_owners eq 'any'}selected{/if} value='any'>--- Any Asset Owner ---</option>
				{section name=row loop=$asset_owners}
				<option {if $filter_asset_owners eq $asset_owners[row].system_id}selected{/if} value='{$asset_owners[row].system_id}'> 
				({$asset_owners[row].system_nickname}) {$asset_owners[row].system_name} </option>
				{/section}
			</select>
		</td>
		<td ><b>Action Owners: </b><br>
			<select name='filter_action_owners'>
				<option {if $filter_action_owners eq 'any'}selected{/if} value='any'>--- Any Action Owner ---</option>
				{section name=row loop=$action_owners}
				<option {if $filter_action_owners eq $action_owners[row].system_id}selected{/if} value='{$action_owners[row].system_id}'>
				({$action_owners[row].system_nickname}) {$action_owners[row].system_name}
				</option>
				{/section}
			</select>
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<table border="0" cellpadding="3" cellspacing="1" width="98%">
				<tr>
					<td colspan="5"><b>Estimated Completion Date:</b></td>
					<td>&nbsp;</td>
					<td colspan="5"><b>Date Created: </b></td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td width="84"> From:</td>
					<td width="133"><input type="text" name="filter_startdate" size="12" maxlength="10" value="{$filter_startdate}">mm/dd/yyyy</td>
          			<td width="33"><span onClick="javascript:show_calendar('filters.filter_startdate');"><img src="images/picker.gif" width=24 height=22 border=0></span></td>
					<td width="27">To:</td>
					<td width="115"><input type="text" name="filter_enddate" size="12" maxlength="10" value="{$filter_enddate}"> mm/dd/yyyy</td>
					<td width="56"><span onClick="javascript:show_calendar('filters.filter_enddate');"><img src="images/picker.gif" width=24 height=22 border=0></span></td>
					<td width="47">From:</td>
					<td width="96"><input type="text" name="filter_startcreatedate" size="12" maxlength="10" value="{$filter_startcreatedate}"> mm/dd/yyyy</td>
					<td width="32"><span onClick="javascript:show_calendar('filters.filter_startcreatedate');"><img src="images/picker.gif" width=24 height=22 border=0></span></td>
					<td width="27">To:</td>
					<td width="115"><input type="text" name="filter_endcreatedate" size="12" maxlength="10" value="{$filter_endcreatedate}">mm/dd/yyyy</td>
					<td width="109"><span onClick="javascript:show_calendar('filters.filter_endcreatedate');"><img src="images/picker.gif" width=24 height=22 border=0></span></td>
				</tr>
			</table>
		</td>
	</tr>
    <tr>
		<td align="left"><input type='submit' value='Search' onClick="firstpage();"></td>
	</tr>
</table>
<!-- Begin Filter Table -->

<br>

<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Remediation Search Results</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->

<!-- Remediation Summary Table -->
<table width="95" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
		
			<!-- Pagination -->
			<table width="100%" align="left" border="0" cellpadding="5" cellspacing="0">
				<tr>
					<td align="left">Number of Results to Display <input type="text" name="row_no" size="3" maxlength="3" value="{$row_no}"></td>
					<td>
						{if $remediation_page ne 1}<input type="button" value="Previous" onclick="pageskip(false);">{/if}
					</td>
					<td>Page:</td>
					<td>
						<input type="text" name="remediation_page" size="3" maxlength="3" value="{$remediation_page}"></td>
					<td>
						{if $remediation_page ne $total_pages}<input type="button" value="Next" onclick="pageskip(true);">{/if}
					</td>
					<td>Total pages: <b>{$total_pages}</b></td>
				</tr>
			</table>
			<!-- End Pagination -->
			</form>
		
		</td>
	
	</tr>
	<tr>
		<td>
	
		<table width="100%" border="1" cellpadding="5" cellspacing="0" class="tbframe">
				<form  name="order_by_ID" action='remediation.php' method='POST'>
			<th nowrap>
				<input type='hidden' name='remediation_id'          value='{$list[row].poam_id}'>
				<input type='hidden' name='remediation_ids'         value='{$remediation_ids}'>
				<input type='hidden' name='filter_source'           value='{$filter_source}'>
				<input type='hidden' name='filter_system'           value='{$filter_system}'>
				<input type='hidden' name='filter_status'           value='{$filter_status}'>
				<input type='hidden' name='filter_type'             value='{$filter_type}'>
				<input type='hidden' name='filter_startdate'        value='{$filter_startdate}'>
				<input type='hidden' name='filter_enddate'          value='{$filter_enddate}'>
				<input type='hidden' name='filter_startcreatedate'  value='{$filter_startcreatedate}'>
				<input type='hidden' name='filter_endcreatedate'    value='{$filter_endcreatedate}'>
				<input type='hidden' name='sort_by'        value='remediation_id'> 
				<input type='hidden' name='sort_order' > 
			ID 
				<input type='image'  src='images/up_arrow.gif'   onClick="order_page(11)"> 
				<input type='image'  src='images/down_arrow.gif' onClick="order_page(12)">			
			</th>

			<th nowrap>Source 
				<input type='image'  src='images/up_arrow.gif'   onClick="order_page(21)"> 
				<input type='image'  src='images/down_arrow.gif' onClick="order_page(22)">			
			</th>

			<th nowrap>System 
				<input type='image'  src='images/up_arrow.gif'   onClick="order_page(41)"> 
				<input type='image'  src='images/down_arrow.gif' onClick="order_page(42)">			
			</th>

			<th nowrap>Type 
				<input type='image'  src='images/up_arrow.gif'   onClick="order_page(51)"> 
				<input type='image'  src='images/down_arrow.gif' onClick="order_page(52)">			
			</th>

			<th nowrap>Status 
				<input type='image'  src='images/up_arrow.gif'   onClick="order_page(61)"> 
				<input type='image'  src='images/down_arrow.gif' onClick="order_page(62)">
			</th>

			<th nowrap>Finding
				<input type='image'  src='images/up_arrow.gif'   onClick="order_page(71)">
				<input type='image'  src='images/down_arrow.gif' onClick="order_page(72)">
			</th>

			<th nowrap>ECD 
				<input type='image'  src='images/up_arrow.gif'   onClick="order_page(81)"> 
				<input type='image'  src='images/down_arrow.gif' onClick="order_page(82)">			
			</th>
				</form>

			<th nowrap>View</th>

			</tr>

			<!-- REMEDIATION ROWS -->	
			{section name=row loop=$list}
			<tr>

				<td align='center' class='tdc'>{$list[row].nice_poam_id}</td>
				<td align='center' class='tdc' nowrap>{$list[row].source_nickname}</td>
				<td align='center' class='tdc'>{$list[row].action_owner_nickname}</td>
				<td align='center' class='tdc' nowrap>{$list[row].poam_type}</td>
				<td align='center' class='tdc' nowrap>{$list[row].poam_status}</td>
				<td align='left'   class='tdc'>{$list[row].finding_data|truncate:120:"..."}</td>
				<td align='center' class='tdc' nowrap>{$list[row].poam_action_date_est}</td> 

					<!-- view button -->
					<form action='remediation_detail.php' method='POST'>

					<!-- filter values -->
					<input type="hidden" name="remediation_ids"        value='{$remediation_ids}'>
					<input type='hidden' name='filter_source'          value='{$filter_source}'>
					<input type='hidden' name='filter_system'          value='{$filter_system}'>
					<input type='hidden' name='filter_status'          value='{$filter_status}'>
					<input type='hidden' name='filter_type'            value='{$filter_type}'>
	
					<input type='hidden' name='filter_startdate'       value='{$filter_startdate}'>
					<input type='hidden' name='filter_enddate'         value='{$filter_enddate}'>
					<input type='hidden' name='filter_startcreatedate' value='{$filter_startcreatedate}'>
					<input type='hidden' name='filter_endcreatedate'   value='{$filter_endcreatedate}'>

					<input type='hidden' name='filter_asset_owners'    value='{$filter_asset_owners}'>
					<input type='hidden' name='filter_action_owners'   value='{$filter_action_owners}'>
	
					<input type='hidden' name='sort_order'             value='{$sort_order}'>
					<input type='hidden' name='sort_by'                value='{$sort_by}'>

					<input type='hidden' name='remediation_id' value='{$list[row].poam_id}'>
				
				<td align="center" valign='middle' class='tdc'><input type='image'  src='images/view.gif'></td>
					</form>
			</tr>
				{/section}
		</table>
	
		</td>
	</tr>
</table>

{else}

<div class="noright">{$noright}</div>

{/if}

{include file="footer.tpl"}
