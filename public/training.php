<?PHP
header("Cache-Control: no-cache, must-revalidate");

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("ovms.ini.php");
require_once("user.class.php");

$screen_name = "training";

session_start();

$smarty->assign('title', 'OVMS');
$smarty->assign('name', $screen_name);


$user = new User($db);



$loginstatus = $user->login();

if ($loginstatus != '1') {

	$user->loginFailed($smarty);
	exit;

}

displayLoginInfor($smarty, $user);

$smarty->display('header.tpl');

?>

<body>

<br>

<b>Training Modules</b>

<hr>


<!-- OUTER TABLE -->
<table border='0' class='tbframe' width='100%'>

<tr valign='TOP'>

<!-- INNER TABLE LEFT -->
<td width='246'>

<table class='tbframe' border='0' cellpadding='0' cellspacing='0' width='100%'>

<th align='left' colspan='2'>Findings</th>

  <tr>
	<td>Scan Results Upload</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='findings---scan_upload'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Finding Conversion</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='findings---conversion_to_remediation'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

</table>

<br>

<table class='tbframe' border='0' cellpadding='0' cellspacing='0' width='100%'>

  <th align='left' colspan='2'>Remediation</th>

  <tr>
	<td>Recommendation Entry</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---recommendation_entry'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Type Classification</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---mitigation_type_classification'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Threat and Countermeasure Entry</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---threat_and_countermeasure_entry'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Mitigation Strategy Entry</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---mitigation_strategy_entry'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Mitigation Strategy Approval</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---sso_mitigation_approval'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>RAF Generation</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='reports---raf'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Evidence Submission</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---evidence_submission'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Evidence Approval - SSO</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---sso_evidence_approval'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Evidence Approval - S&P Team</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---sp_evidence_approval'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Evidence Approval - IV&V</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='remediation---ivv_evidence_approval'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

</table>

<br>

<table class='tbframe' border='0' cellpadding='0' cellspacing='0' width='100%'>

  <th align='left' colspan='2'>Reports</th>

  <tr>
	<td>NIST 800-53 Baseline Controls</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='reports---blscr'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>FIPS 199 System Classifications</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='reports---fips199'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>FISMA Report</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='reports---fisma'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Products with Open Vulnerabilities</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='reports---products_with_vulns'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Software with Open Vulnerabilities</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='reports---software_vulns'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

  <tr>
	<td>Systems with Open Vulnerabilities</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='reports---systems_with_vulns'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

</table>

<br>

<table class='tbframe' border='0' cellspacing='0' cellpadding='0' width='100%'>

  <th align='left' colspan='2'>Vulnerabilities</th>

  <tr>
	<td>Searching Vulnerabilities</td>
	<form target='training.php' method='POST'>
	<input type='hidden' name='form_target' value='vulnerabilities---search'>

	<td align='right'><input type='image' src='images/button_view.png'></td>
	</form>
  </tr>

</table>

</td>
<!-- INNER TABLE LEFT -->


<!-- INNER TABLE RIGHT -->
<td>

<?PHP

// movie parameters
$height = 500;
$width  = 700;

// open the table
print "<table border='0' cellspacing='0' cellpadding='0' width='100%' class='tbframe'>\n";
print "<th>Video</th>\n";
print "<tr>\n";
print "<td>\n";
print "<br>\n";

// table content
if (isset($_POST['form_target'])) {
    $source = "training/".$_POST['form_target'].".swf";
	print "<center>\n";
	print "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0' width='$width' height='$height'>\n";
	print "<param name='movie' value='$source'>\n";
	print "<param name='quality' value='high'>\n";
	print "<param name='bgcolor' value='#ffffff'>\n";
	print "<embed src='$source' quality='high' bgcolor='#ffffff' width='$width' height='$height' type='application/x-shockwave-flash' pluginspage='https://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash'>\n";
	print "</object>\n";
	print "</center>\n";

}

else {

	print "<center>Please select content from the left.</center>";


}

// close the table
print "<br>\n";
print "</td>\n";
print "</tr>\n";
print "</table>\n";

?>


</td>
<!-- INNER TABLE RIGHT -->


<!-- OUTER TABLE -->
</tr>
</table>

<br>

<?PHP $smarty->display('footer.tpl'); ?>

</body>
</html>
