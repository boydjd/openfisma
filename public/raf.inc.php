<?PHP
/*******************************************************************************
* File    : raf.php
* Purpose :
* Author  :
* Date    :
*******************************************************************************/

header("Cache-Control: no-cache, must-revalidate");

session_start();
session_register('rpdata');
// Smarty specific includes
require_once("config.php");
require_once("smarty.inc.php");

// db includes
require_once("dblink.php");
//
require_once("raf_lang.php");
require_once("notice_lang.php");  // $REPORT_FOOTER_WARNING
require_once("report_utils.php");
require_once("RiskAssessment.class.php");
require_once("raf.class.php");
require_once("user.class.php");
require_once("page_utils.php");


/*
** Check for user permission right away
*/
$user = new User($db);

/*
$loginstatus = $user->login();
if($loginstatus != 1) {
        // redirect to the login page
        $user->loginFailed($smarty);
        exit;
}
*/
verify_login($user, $smarty);


//
if(isset($_POST['poam_id'])) {
	$poam_id = intval($_POST['poam_id']);
}elseif (isset($_GET['poam_id'])){
    $poam_id = intval($_GET['poam_id']);
}else{
	die("needs poam_id");
}
//
$rafObj = new Raf($db);
$rafObj->setPoam_id($poam_id);
$screen_name = "RAF";
//$smarty->debugging = true;

$smarty->assign('title', 'Risk Analysis Form(RAF)');
$smarty->assign('name', '');
$smarty->assign('raf_lang', $raf_lang);
$smarty->assign('warn_footer', $REPORT_FOOTER_WARNING);

// grab today's date
$today = date("Ymd", time());


/*******************************************************************************
* USER RIGHTS
*******************************************************************************/

//require_once("user.class.php");
//$smarty->assign("username", $user->getUsername());

/*
** Report data is an array of three SQL data sets:
** [0] - a single POAM statistics row
** [1] - a list of vulnerability description rows
** [2] - a list of affected server/asset rows
*/
$rpdata = array();


$smarty->assign('poam_id', $rafObj->getPoam_id());
$smarty->assign('WVTNO', $rafObj->getWeaknessVulnerabilityTrackingNO());

/*
** Get POAM display data fields
*/
$poam_field_row = $rafObj->getPOAMFields();
$test_val = $poam_field_row['s_c'];

/*
** Set up risk assessment object using POAM stats
*/
$riskObj = new RiskAssessment($poam_field_row['s_c'],
			      $poam_field_row['s_a'],
			      $poam_field_row['s_i'],
			      $poam_field_row['s_a'],
			      $poam_field_row['t_level'],
			      $poam_field_row['cm_eff']);

/*
** Determine data sensitivity, add it to result row
*/
$data_sensitivity = $riskObj->get_data_sensitivity();
$poam_field_row['data_sensitivity'] = $data_sensitivity;

/*
** Determine impact, add to result row
*/
$impact = $riskObj->get_impact();
$poam_field_row['impact'] = $impact;

/*
** Determine threat likelihood, add to result row
*/
$threat_likelihood = $riskObj->get_threat_likelihood();
$poam_field_row['threat_likelihood'] = $threat_likelihood;

/*
** Format text fields
*/
massage_POAM_fields($poam_field_row);

/*
** Add POAM row to report data set
*/
array_push($rpdata, $poam_field_row);


/*
** Retrieve list of any vulnerability descriptions,
** add that to report data
*/
array_push($rpdata, $rafObj->getVulnDescriptions());

/*
** Retrieve list of any asset names,
** add that to report data
*/
array_push($rpdata, $rafObj->getAssetNames());

//echo "asset names: "; print_r($rafObj->getAssetNames()); die;

/*
** Map LOW/MODERATE/HIGH axes to specific display table cell indices.
** Cells in the 3x3 table are specified like this:
**
**   L M H
** H 0 1 2
** M 3 4 5
** L 6 7 8
**
** This is consistent across the three RAF tables, so all that's needed
** is to apply the axis variables (criticality, sensitivity, etc.)
** for each particular table against this one 2-dimensional lookup.
*/
$cellidx_lookup['HIGH']['LOW']          = 0;
$cellidx_lookup['HIGH']['MODERATE']     = 1;
$cellidx_lookup['HIGH']['HIGH']         = 2;
$cellidx_lookup['MODERATE']['LOW']      = 3;
$cellidx_lookup['MODERATE']['MODERATE'] = 4;
$cellidx_lookup['MODERATE']['HIGH']     = 5;
$cellidx_lookup['LOW']['LOW']           = 6;
$cellidx_lookup['LOW']['MODERATE']      = 7;
$cellidx_lookup['LOW']['HIGH']          = 8;

/*
** Determine Impact table highlight cell from criticality and
** data sensitivity values.
** Note that availability is used as mission criticality.
** $impact_lookup[data_sensitivity][criticality] = cell_index
*/
$criticality = $poam_field_row['s_a'];
$impact_index = $cellidx_lookup[$data_sensitivity][$criticality]; //get impact index
$cell_colors = cell_background_colors(9, $impact_index);
//

/*
** Determine threat likelihood table highlight cell from countermeasure
** effectiveness and threat source.
** $cellidx_lookup[threat_level][effectiveness] = cell_index
*/
$effectiveness = $poam_field_row['cm_eff'];
$threat_level  = $poam_field_row['t_level'];

$threat_index = $cellidx_lookup[$threat_level][$effectiveness]; //get threat index
$cell_colors_tl = cell_background_colors(9, $threat_index);
//

/*
** Determine overall risk level table highlight cell from impact and
** threat likelihood.
** $cellidx_lookup[threat_likelihood][impact] = cell_index
*/
$risk_index = $cellidx_lookup[$threat_likelihood][$impact]; //get threat index
$cell_colors_rl = cell_background_colors(9, $risk_index);
//
////
//// Assign colors to template, display impact table
////
$smarty->assign('cell_colors', $cell_colors);
$smarty->assign('cell_colors_tl', $cell_colors_tl);
$smarty->assign('cell_colors_rl', $cell_colors_rl);
//$smarty->display('raf_impact_table.tpl');

//
// For good measure add the indices themselves - will need them for PDF
// generation
//
$smarty->assign('impact_idx', $impact_index);
$smarty->assign('threat_idx', $threat_index);
$smarty->assign('risk_idx',   $risk_index);


$smarty->assign('rpdata', $rpdata);


$smarty->assign('now', get_page_datetime());
$_SESSION['rpdata']   = $smarty->get_template_vars();

//foreach (array_keys($_SESSION['rpdata']) as $key) {
//  echo "$key<br/>";
//  }

//$smarty->display('raf.tpl');


function massage_POAM_fields(&$poam_row) {
  $BLANK_DATA = 'n/a';

  /*
  ** Mark blank fields with 'n/a'
  */
  foreach (array_keys($poam_row) as $key) {
    $val = $poam_row[$key];
    if (strlen($val) < 1) {
      $poam_row[$key] = $BLANK_DATA;
      }
    }

  /*
  ** Chop any time info off of date fields
  */
  $DATE_LENGTH = strlen("YYYY-MM-DD");
  $date_fields = array('dt_created', 'dt_mod', 'dt_closed', 'dt_discv');
  foreach ($date_fields as $field) {
    if (strlen($poam_row[$field]) > $DATE_LENGTH) {
      $poam_row[$field] = substr($poam_row[$field], 0, $DATE_LENGTH);
      }

    /*
    ** Further, substitute 'n/a' for 0000-00-00
    */
    $BLANK_DATE = '0000-00-00';
    if ($poam_row[$field] == $BLANK_DATE) {
      $poam_row[$field] = $BLANK_DATA;
      }
    }

  /*
  ** Convert boolean values to useful strings.
  */
  $TRUE_TEXT = 'yes';
  $FALSE_TEXT = 'no';

  $boolean_fields = array('is_repeat');
  foreach ($boolean_fields as $field) {
    $poam_row[$field] = ($poam_row[$field]) ? $TRUE_TEXT : $FALSE_TEXT;
    }
  }

?>
