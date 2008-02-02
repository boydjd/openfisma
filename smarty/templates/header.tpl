<html>
<head>
	
<title>{$title} - {$name}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
{literal}
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/menu.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/cal.js"></script>
<!-- jQuery include file start -->
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.ui/jquery.dimensions.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.ui/ui.dialog.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.ui/ui.resizable.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.ui/ui.mouse.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.ui/ui.draggable.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.ui/datepicker/core/ui.datepicker.js"></script>

<link rel="stylesheet" href="javascripts/jquery/jquery.ui/themes/flora/flora.all.css" type="text/css">
<link rel="stylesheet" href="javascripts/jquery/jquery.ui/datepicker/core/ui.datepicker.css" type="text/css">
<!-- jQuery include file end -->
<link rel="stylesheet" type="text/css" href="stylesheets/main.css">
<link rel="stylesheet" type="text/css" href="stylesheets/mainmenu.css">
{/literal}
</head>

<!-- Header Section -->

<body marginheight="0" marginwidth="0" topmargin="0" leftmargin="0" rightmargin="0" onLoad="" bgcolor="#ffffff">
<table width="100%" border="0" height="60" cellpadding="0" cellspacing="0" >
<tr>
        <td width="70%" align="left">
	<a href="customer_url"><img src="{$customer_logo}" border="0"></a>
	</td>

        <td width="30%" align="right">
	<table border="0">
	<tr>
		<td nowrap><a href="pwdchange.php" class="link"><img src="images/button_change_password.png" border="0"></a></td>
		<td>&nbsp;</td>
		<td><a href="login.php?logout=1"><img src="images/button_logout.png" border="0"></a></td>
	</tr>
	<tr>
		<td align="right" colspan="3" nowrap><u><b>{$firstname} {$lastname}</b></u> is currently logged in.</td>
	</tr>
	</table>
	</td>
</tr>
</table>

<!-- Navigation Menu -->

<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" id="wrappertable">
<tr>
	<td valign="top" bgcolor="#ffffff">
	<table cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#44637A">
	<tr>
		<td>
		<table border="0">
		<tr align="center" height="21">
			<td><img src="images/menu_line.gif" border="0"></td>
			{if $dashboard_menu eq 1}
			<td width="100" onMouseOver="ShowMenu(this, 'dashboard'); swapColor(this, 'on');" onMouseOut="HideMenu('dashboard'); swapColor(this, 'off');"><a href="dashboard.php" id="mds" class="t">Dashboard</a></td>
			<td><img src="images/menu_line.gif" border="0"></td>
			{/if}
			{if $finding_menu eq 1}
			<td width="100" onMouseOver="ShowMenu(this, 'finding'); swapColor(this, 'on');" onMouseOut="HideMenu('finding'); swapColor(this, 'off');"><span id="mfd" class="t">Findings</span></td>
			<td><img src="images/menu_line.gif" border="0"></td>
			{/if}
			{if $asset_menu eq 1}
			<td width="100" onMouseOver="ShowMenu(this, 'asset'); swapColor(this, 'on');" onMouseOut="HideMenu('asset'); swapColor(this, 'off');"><a href="asset.php" id="mas" class="t">Assets</a></td>
			<td><img src="images/menu_line.gif" border="0"></td>
			{/if}
			{if $remediation_menu eq 1}
			<td width="100" onMouseOver="ShowMenu(this, 'remediation'); swapColor(this, 'on');" onMouseOut="HideMenu('remediation'); swapColor(this, 'off');"><a href="remediation.php" id="mrm" class="t">Remediation</a></td>
			<td><img src="images/menu_line.gif" border="0"></td>
			{/if}
			{if $report_menu eq 1}
			<td width="100" onMouseOver="ShowMenu(this, 'report'); swapColor(this, 'on');" onMouseOut="HideMenu('report'); swapColor(this, 'off');"><a href="report.php" id="mrp" class="t">Reports</a></td>
			<td><img src="images/menu_line.gif" border="0"></td>
			{/if}
			{if $admin_menu eq 1}
			<td width="100" onMouseOver="ShowMenu(this, 'administration'); swapColor(this, 'on');" onMouseOut="HideMenu('administration'); swapColor(this, 'off');"><span id="mad" class="t">Administration</span></td>
			<td><img src="images/menu_line.gif" border="0"></td>
			{/if}
			{if $vulner_menu eq 1}
			<td width="100" onMouseOver="ShowMenu(this, 'vulnerabilities'); swapColor(this, 'on');" onMouseOut="HideMenu('vulnerabilities'); swapColor(this, 'off');"><span id="mvu" class="t">Vulnerabilities</span></td>
			<td><img src="images/menu_line.gif" border="0"></td>
			{/if}
		</tr>
		</table>
		</td>
	</tr>
	</table>

	{if $dashboard_menu eq 1}
	<div class=menu id="dashboard" onMouseOver="HoldMenu();" onMouseOut="HideMenu('dashboard');">
	<table cellpadding="0" cellspacing="0" border="0">
	</table>
	</div>
	{/if}

	{if $finding_menu eq 1}
	<div class=menu id="finding" onMouseOver="HoldMenu();" onMouseOut="HideMenu('finding');">
	<table cellpadding="0" cellspacing="0" border="0">

	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="finding.php" class="n navicon" id="fs"> Findings Summary</a></td>
	</tr>

	{if $finding_add eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="findingdetail.php" class="n navicon" id="cnf"> New Finding</a></td>
	</tr>
	{/if}
	{if $finding_upload eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="finding_upload.php" class="n navicon" id="usr"> Upload Scan Results</a></td>
	</tr>
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="finding_injection.php" class="n navicon" id="usr"> Spreadsheet Upload</a></td>
	</tr>
	{/if}
	</table>
	</div>
	{/if}

	{if $asset_menu eq 1}
	<div class=menu id="asset" onMouseOver="HoldMenu();" onMouseOut="HideMenu('asset');">
	<table cellpadding="0" cellspacing="0" border="0">
	{if $asset_summary eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="asset.php" class="n navicon" id="ad"> Asset Dashboard</a></td>
	</tr>
	{/if}
	{if $asset_new eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="asset.create.php" class="n navicon" id="ca"> Create an Asset</a></td>
	</tr>
	{/if}
	</table>
	</div>
	{/if}

	{if $remediation_menu eq 1}
	<div class=menu id="remediation" onMouseOver="HoldMenu();" onMouseOut="HideMenu('remediation');;">
	<table cellpadding="0" cellspacing="0" border="0">
	</table>
	</div>
	{/if}

	{if $report_menu eq 1}
	<div class=menu id="report" onMouseOver="HoldMenu();" onMouseOut="HideMenu('report');">

	{*
	** POST admin operation.
	** Use javascript to pass 't' value from anchor text via POST.
	** The new reportform FORM element provides the POST action.
	*}
	{literal}
	  <SCRIPT LANGUAGE="JavaScript">
	  function submit_b(t_val) {
	    document.reportform.t.value = t_val;
	    document.reportform.submit();
	    }
	  </SCRIPT>
	{/literal}

	<FORM ACTION="report.php" METHOD="POST" NAME="reportform"> 
	<INPUT TYPE="HIDDEN" NAME="t"/>
	<table cellpadding="0" cellspacing="0" border="0">
	{if $report_poam_generate eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_b(2)" class="n navicon" id="rp1"> POA&M Report</a></td>
	</tr>
	{/if}
	{if $report_fisma_generate eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_b(1)" class="n navicon" id="rp2"> FISMA POA&M Report </a></td>
	</tr>
	{/if}
	{if $report_general_generate eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_b(3)" class="n navicon" id="rp3"> General Reports </a></td>
	</tr>
	{/if}
	{if $report_general_generate eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_b(4)" class="n navicon" id="rp4"> Generate System RAFs </a></td>
	</tr>
	{/if}
	</table>
	</FORM>
	</div>
	{/if}

	{if $admin_menu eq 1}
	<div class=menu id="administration" onMouseOver="HoldMenu();" onMouseOut="HideMenu('administration');">

	{*
	** POST admin operation.
	** Use javascript to pass 'tid' value from anchor text via POST.
	** The new adminform FORM element provides the POST action.
	*}
	{literal}
	  <SCRIPT LANGUAGE="JavaScript">
	  function submit_a(tid_val) {
	    document.adminform.tid.value = tid_val;
	    document.adminform.submit();
	    }
	  </SCRIPT>
	{/literal}

	<FORM ACTION="tbadm.php" METHOD="POST" NAME="adminform"> 
	<INPUT TYPE="HIDDEN" NAME="tid"/>
	<table cellpadding="0" cellspacing="0" border="0">
	{if $admin_user_view eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_a(1);" class="n navicon" id="user"> Users</a></td>
	</tr>
	{/if}
	{if $admin_role_view eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_a(2)" class="n navicon" id="role"> Roles</a></td>
	</tr>
	{/if}
	{if $admin_system_view eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_a(3)" class="n navicon" id="sys"> Systems</a></td>
	</tr>
	{/if}
	{if $admin_products_view eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_a(4)" class="n navicon" id="prod"> Products</a></td>
	</tr>
	{/if}
	{if $admin_group_view eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_a(5)" class="n navicon" id="sg"> System Group</a></td>
	</tr>
	{/if}
	{if $admin_function_view eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="javascript:submit_a(6)" class="n navicon" id="func"> Functions</a></td>
	</tr>
	{/if}
	</table>
	</FORM>
	</div>
	{/if}

	{if $vulner_menu eq 1}
	<div class=menu id="vulnerabilities" onMouseOver="HoldMenu();" onMouseOut="HideMenu('vulnerabilities');">
	<table cellpadding="0" cellspacing="0" border="0">
	{if $vulner_summary eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="vulnerabilities.php" class="n navicon" id="vsum">Summary</a></td>
	</tr>
	{/if}
	{if $vulner_add eq 1}
	<tr>
		<td class=menurow onMouseover="swapColor(this, 'on');" onMouseout="swapColor(this, 'off');"><a href="vulnerabilities_create.php" class="n navicon" id="vnew"> New Vulnerability</a></td>
	</tr>
	{/if}
	</table>
	</div>
	{/if}
	
	</td>
</tr>
</table>

<!-- End Navigation Menu -->

<table width="100%" height="10" border="0" ></table>

<!-- <table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="2%">&nbsp;</td>
	<td width="96%"> -->
