<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<!-- report.tpl is decieving it is only for the FISMA Report to OMB ONLY -->

{literal}
<script language="javascript">
function selectr() {

	filter.startdate.style.background="#CCCCCC";
	filter.enddate.style.background="#CCCCCC";
	filter.sy.disabled=true;
	filter.sq.disabled=true;
	filter.startdate.disabled=true;
	filter.enddate.disabled=true;

	if(filter.dr[0].checked) filter.sy.disabled=false;
	if(filter.dr[1].checked) {
		filter.sy.disabled=false;
		filter.sq.disabled=false;
	}
	
	if(filter.dr[2].checked) 
	{
	filter.startdate.style.background="";
	filter.enddate.style.background="";
	filter.startdate.disabled=false;
	filter.enddate.disabled=false;
	}
}
function dosub() {
	if ((filter.dr[0].checked&&filter.sy.value!="")|| 
	(filter.dr[1].checked&&filter.sq.value!=""&&filter.sy.value!="") ||
	(filter.dr[2].checked&&filter.startdate.value!=""&&filter.enddate.value!="")){
	// make sure end date is after start date
        if(filter.dr[2].checked&&filter.startdate.value!="" &&
	  filter.enddate.value!="") {
	  if(!start_end_dates_ok(filter.startdate.value, filter.enddate.value)) {
	    alert("Start date must be before end date");
	    return false;
	    }
	  }
	  if (filter.system.value == '') {
	      alert("Please choose a system.");
	      return false;
	  }
	  filter.submit();
	}
	else{
	alert("Please choose one analysis date range.");
	return false;
	}
}

function start_end_dates_ok(start_dt, end_dt) {

  // make sure dates are of format mm/dd/yyyy
  if (!(start_dt.match(/^\d\d\/\d\d\/\d\d\d\d/) && end_dt.match(/^\d\d\/\d\d\/\d\d\d\d/))) {
    alert("Dates must be in format mm/dd/yyyy");
    return false;
    }

  // set up a number string of format yyyymmdd for easy comparison
  var fmt_start = start_dt.substr(6,4) + start_dt.substr(3,2) + start_dt.substr(0,2);
  var fmt_end = end_dt.substr(6,4) + end_dt.substr(3,2) + end_dt.substr(0,2);

  return (fmt_end >= fmt_start) ? true : false;
  }

</script>
{/literal}

<br>

<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>FISMA Report to OMB</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>

<br>

<form name="filter" method="post" action="report.php">

<table width="850"  align="center" border="0" cellpadding="3" cellspacing="1" class="tipframe">
	<tr>
		<td>
        	<input name="dr" type="radio" value="y" onClick="javascript:selectr();" {if $dr eq 'y'}checked{/if}> 
    		<b>Yearly</b>    
            <input name="dr" type="radio" value="q"  onClick="javascript:selectr();" {if $dr eq 'q'}checked{/if}>  
      		<b>Quarterly</b>
       	</td>
  		<td>
        	<input name="dr" type="radio" value="c"  onClick="javascript:selectr();" {if $dr eq 'c'}checked{/if}> 
    		<b>Custom</b>
       	</td>
	</tr>
	<tr>
  		<td width="50%">
        
        	<table width="100%" border="0" cellpadding="3" cellspacing="1"class="tipframe">
    			<tr>
            		<td width="47%">
                        <select name="system">
                        	{html_options options=$systems selected=$system}
                        </select>
            		</td>
      				<td  width="47%">
        				<select name="sy" id="{$sy}">
        				<option value="">Select Fiscal Year </option>
        				<option value="{$nowy-3}" {if $sy eq $nowy-3}selected{/if}>{$nowy-3}</option>
        				<option value="{$nowy-2}" {if $sy eq $nowy-2}selected{/if}>{$nowy-2}</option>
        				<option value="{$nowy-1}" {if $sy eq $nowy-1}selected{/if}>{$nowy-1}</option>
        				<option value="{$nowy}" {if $sy eq $nowy}selected{/if}>{$nowy}</option>
        				<option value="{$nowy+1}" {if $sy eq $nowy+1}selected{/if}>{$nowy+1}</option>
      					</select>
      				</td>
        			<td  width="6%">
        				<select name="sq" id="{$sq}">
        				<option value="">Select Fiscal Quarter </option>
        				<option value="1" {if $sq eq '1'}selected{/if}>1Q</option>
        				<option value="2" {if $sq eq '2'}selected{/if}>2Q</option>
        				<option value="3" {if $sq eq '3'}selected{/if}>3Q</option>
        				<option value="4" {if $sq eq '4'}selected{/if}>4Q</option>
      					</select>
 					</td>
    			</tr>
			</table>

   		</td>
		<td width="50%">
        
        	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tipframe">
    			<tr>
      				<td>Start Date</td>
      				<td>&nbsp;</td>
      				<td>From:</td>
      				<td>
                    	<input type="text" name="startdate" value="{$startdate}" size="10" maxlength="10" value="" onclick="javascript:show_calendar('filter.startdate');" readonly>
                    </td>
      				<td><a href="#" onclick="javascript:show_calendar('filter.startdate');">
                    	<img src="images/picker.gif" width=24 height=22 border=0></a>
                  	</td>
      				<td>&nbsp;</td>
      				<td>End Date:</td>
      				<td><input type="text" name="enddate" value="{$enddate}" size="10" maxlength="10" value="" onclick="javascript:show_calendar('filter.enddate');" readonly></td>
      				<td><a href="#" onclick="javascript:show_calendar('filter.enddate');">
      					<img src="images/picker.gif" width=24 height=22 border=0></a>
                 	</td>
      				<td>&nbsp;</td>
      				<td>&nbsp;</td>
    			</tr>
  			</table>
    	</td>
	</tr>
	<tr>
  		<td colspan="2">
  			<input type="hidden" name="t" value="{$t}">
  			<input type="hidden" name="sub" value="1">
    		<input type="button" value="Generate Report"  onClick="javascript:dosub();">
		</td>
	</tr>
</table>
</form>

{if $sub}
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="0"><img src="images/left_circle.gif" border="0"></td>
		<td width="50%" bgcolor="#DFE5ED"><b>FISMA Report to OMB:</b> {$startdate} through {$enddate}</td>

        {*
        ** Set up FORM + Javascript to POST data based on image selected.
        ** This hides passed variables from the URL string.
        *}
        {literal}
          <SCRIPT LANGUAGE="JavaScript">
          function submit_export(f_val) {

            // open new window if the request is for a pdf
            if(f_val == 'p') {
              document.exportform.target = "_blank";
              }
            else {
              document.exportform.target = "_self";
              }

            document.exportform.f.value = f_val;
            document.exportform.submit();
            }
          </SCRIPT>
        {/literal}

        <FORM ACTION="creport.php" METHOD="POST" NAME="exportform">
        <INPUT TYPE="HIDDEN" NAME="f"/>
        <INPUT TYPE="HIDDEN" NAME="t" value="1"/>

		<td width="50%" align="right" bgcolor="#DFE5ED">Export to: <a href="javascript:submit_export('p');" ><img src="images/pdf.gif" border="0"></a>
		<a href="javascript:submit_export('e');"><img src="images/xls.gif" border="0"></a> </td>
		</FORM>
		<td width="0"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>

<br>

<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
	<tr align="center">
		<td colspan="5"  bgcolor="#DFE5ED"><b>{$report_lang[1][0]}</b></td>
	</tr>
  	<tr align="center">
		<td colspan="5"  bgcolor="#DFE5ED">
        
        	<table width="100%" border="0" cellspacing="0" cellpadding="4">
                <tr align="center">
                    <th width="50%">{$report_lang[1][1]}
                    <th width="15%">{$report_lang[1][2]}
                    <th width="15%">{$report_lang[1][3]}
                    <th width="15%">{$report_lang[1][4]}
                    <th width="15%" nowrap>{$report_lang[1][5]}</tr>
                <tr>
                    <td width="50%" class="tdc">{$report_lang[1][6]}</td>
                    <td width="15%" class="tdc">{$AAW}</td>
                    <td width="15%" class="tdc">{$AS}</td>
                    <td width="15%" class="tdc">{$AAW+$AS}</td>
                    <td width="15%" bgcolor="#DFDFDF" class="tdc">&nbsp;</td>
                </tr>
                <tr>
                    <td width="50%" class="tdc">{$report_lang[1][7]}</td>
                    <td width="15%" class="tdc">{$BAW}</td>
                    <td width="15%" class="tdc">{$BS}</td>
                    <td width="15%" class="tdc">{$BAW+$BS}</td>
                    <td width="15%" bgcolor="#DFDFDF" class="tdc">&nbsp;</td>
                </tr>
                <tr>
                    <td width="50%" class="tdc">{$report_lang[1][8]}</td>
                    <td width="15%" class="tdc">{$CAW}</td>
                    <td width="15%" class="tdc">{$CS}</td>
                    <td width="15%" class="tdc">{$CAW+$CS}</td>
                    <td width="15%" bgcolor="#DFDFDF" class="tdc">&nbsp;</td>
                </tr>
                <tr>
                    <td width="50%" class="tdc">{$report_lang[1][9]}</td>
                    <td width="15%" class="tdc">{$DAW}</td>
                    <td width="15%" class="tdc">{$DS}</td>
                    <td width="15%" class="tdc">{$DAW+$DS}</td>
                    <td width="15%" class="tdc">&nbsp;</td>
                </tr>
                <tr>
                    <td width="50%" class="tdc">{$report_lang[1][10]}</td>
                    <td width="15%" class="tdc">{$EAW}</td>
                    <td width="15%" class="tdc">{$ES}</td>
                    <td width="15%" class="tdc">{$EAW+$ES}</td>
                    <td width="15%" class="tdc">&nbsp;</td>
                </tr>
                <tr>
                    <td width="50%" class="tdc" nowrap>{$report_lang[1][11]}</td>
                    <td width="15%" class="tdc">{$FAW}</td>
                    <td width="15%" class="tdc">{$FS}</td>
                    <td width="15%" class="tdc">{$FAW+$FS}</td>
                    <td width="15%" bgcolor="#DFDFDF" class="tdc">&nbsp;</td>
                </tr>
            </table>
		</td>
	</tr>
</table>

{/if}
{literal}
<script language="javascript">
selectr();
</script>
{/literal}

{include file="footer.tpl"}
