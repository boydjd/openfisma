<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

<!-- General Reports Template -->

{literal}
<script language="javascript">
function dosub() {
	if (filter.type.value!=""){
	filter.submit();
	}
	else{
	alert("Please choose one report.");
	return false;
	}
}

</script>
{/literal}

<br>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Reports : General Reports</b></td>
		<td bgcolor="#DFE5ED" align="right">{$now}</td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<br>

<form name="filter" method="post" action="">
<input type="hidden" name="action" value="filter">

<table width="95%" align="center" border="0">
	<tr>
    	<td>
           	<table cellpadding="5" class="tipframe">
  				<tr>
                	<td><b>Report</b></td>
        			<td>
        				<select name="type">
          				<option value="">Please Select Report </option>
						{foreach key=key item=item from=$report_lang[3][0]}
            			<option value="{$key}" {if $key eq $grtype}selected="true"{/if}>{$item}</option>
  						{/foreach}
						</select>
       				</td>
  					<td>
	        			<input type="hidden" name="sub" value="1" />
    					<input type="hidden" name="t" value="{$t}" />
        				<input type="button" value="Generate"  onClick="javascript:dosub();">
					</td>
        		</tr>
			</table>
		</td>
  	</tr>
</table>

</form>
{if $sub}

<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="0"><img src="images/left_circle.gif" border="0"></td>
		<td width="50%" bgcolor="#DFE5ED"><b>Report: {$report_lang[3][0][$grtype]}</b></td>

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
        <INPUT TYPE="HIDDEN" NAME="t" value="3{$grtype}"/>

		<td width="50%" align="right" bgcolor="#DFE5ED">Export to: <a href="javascript:submit_export('p');" >
        <img src="images/pdf.gif" border="0"></a>
		<a href="javascript:submit_export('e');"><img src="images/xls.gif" border="0"></a> 
        </td>
		</FORM>
		<td width="0"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<br>

{include file="report3$grtype.tpl"}
{/if}
<p>&nbsp;</p>
{include file="footer.tpl"}
