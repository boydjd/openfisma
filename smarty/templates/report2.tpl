<!--
-- POAM Report template
--
-- Input:
--  rpdata - result set from POAM SQL query
--  systems - list of available systems
--  list of available finding sources
--
-->

<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 
<br>

<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Reports : POA&amp;M Reports</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>

<br>

<form name="filter" method="post" action="">
<input type="hidden" name="action" value="filter">

<table width="95%" align="center" border="0" cellpadding="5" cellspacing="1" class="tipframe">
	<tr>
		<td><b>System</b></td>
  		<td><b>Source</b></td>
  		<td><b>Fiscal Year</b></td>
  		<td><b>Type</b></td>
  		<td><b>Status</b></td>
		<td width="10%" rowspan="2"><input type="submit" name="search" value="Generate">
    		<input type="hidden" name="t" value="{$t}" />
    		<input type="hidden" name="sub" value="1" />
  		</td>
	</tr>
	<tr>
		<td>
			<select name="system">
        	<option value="">Select System </option>
			{section name=row loop=$systems}
        	<option value="{$systems[row].name}">{$systems[row].name}</option>
        	{/section}
			</select>
		</td>
		<td>
    		<select name="source">
        	<option value="">Select Source </option>
			{section name=row loop=$sources}
          	<option value="{$sources[row].name}">{$sources[row].name}</option>
        	{/section}
    		</select>
  		</td>
		<td>
    		<select name="sy">
        	<option value="">Select Fiscal Year </option>
        	<option value="{$nowy-3}">{$nowy-3}</option>
        	<option value="{$nowy-2}">{$nowy-2}</option>
        	<option value="{$nowy-1}">{$nowy-1}</option>
        	<option value="{$nowy}">{$nowy}</option>
        	<option value="{$nowy+1}">{$nowy+1}</option>
    		</select>
  		</td>
		<td>
    		<input name="poam_type[]" type="checkbox" id="poam_type[]" value="CAP"> CAP
    		<input name="poam_type[]" type="checkbox" id="poam_type[]" value="FP"> FP
    		<input name="poam_type[]" type="checkbox" id="poam_type[]" value="AR"> AR
		</td>
		<td>
			<input name="status[]" type="checkbox" id="status[]" value="open"> Open
    		<input name="status[]" type="checkbox" id="status[]" value="closed"> Closed
  		</td>
	</tr>
</table>

</form>
{if $sub}

<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="0"><img src="images/left_circle.gif" border="0"></td>
		<td width="50%" bgcolor="#DFE5ED"><b>{$report_lang[2][0]}</b></td>

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
        	<INPUT TYPE="HIDDEN" NAME="t" value="2"/>
		<td width="50%" align="right" bgcolor="#DFE5ED">Export to: 
        	<a href="javascript:submit_export('p');" ><img src="images/pdf.gif" border="0"></a> 
            <a href="javascript:submit_export('e');"><img src="images/xls.gif" border="0"></a> 
       	</td>
			</FORM>
		<td width="0"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>

<br>

<input type="hidden" name="action" value="manage">

<table width="95%" align="center" border="0" cellpadding=5" cellspacing="0" class="tbframe">
    <tr align="center">
    	<!--<th>{$report_lang[2][1]}-->
        <th class="tdc">System
        <!-- <th>{$report_lang[2][3]} -->
        <th class="tdc">ID#
        <!-- <th>{$report_lang[2][5]} -->
        <th class="tdc">Type
        <th class="tdc">Status
        <th class="tdc">Source
        <th class="tdc">Server/Database
        <th class="tdc">Location
        <th class="tdc">Risk Level
        <th class="tdc">Recommendation
        <th class="tdc">Corrective Action
        <th class="tdc">ECD
    </tr>

	{section name=row loop=$rpdata}

    <tr>
        <!-- <td class="tdc">{$rpdata[row].po}</td> -->
        <td class="tdc" align="center">{$rpdata[row].system}</td>
        <!-- <td class="tdc">{$rpdata[row].tier}</td> -->
        <td class="tdc" align="center">{$rpdata[row].findingnum}</td>
        <!-- <td class="tdc">{$rpdata[row].finding}</td> -->
        <td class="tdc" align="center">{$rpdata[row].ptype}</td>
        <td class="tdc" align="center">{$rpdata[row].pstatus}</td>
        <td class="tdc" align="center">{$rpdata[row].source}</td>
        <td class="tdc">{$rpdata[row].SD}</td>
        <td class="tdc" align="center">{$rpdata[row].location}</td>
        <td class="tdc" align="center">{$rpdata[row].risklevel}</td>
        <td class="tdc">{$rpdata[row].recommendation}</td>
        <td class="tdc">{$rpdata[row].correctiveaction}</td>
        <td class="tdc" align="center">{$rpdata[row].EstimatedCompletionDate}</td>
    </tr>

	{/section}

</table>

{/if}

{include file="footer.tpl"}