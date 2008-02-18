<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

session_register('rpdata');//register a session var for save report data.
require_once("config.php");
require_once("dblink.php");
require_once("smarty.inc.php");
require_once("report_lang.php");
require_once("report.class.php");
require_once("RiskAssessment.class.php");
require_once("user.class.php");
require_once("page_utils.php");
require_once("assetDBManager.php");

// set the screen name used for security functions
$screen_name = 'report';

// set the page name
$smarty->assign('pageName', 'Reports');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

$rpObj = new Report($db, $user);
//$smarty->compile_check = true;
//$smarty->debugging = true;
$smarty->assign('report_lang', $report_lang);

/*
** Declare constants for ease of maintenance.
*/
$REPORT_TYPE_FISMA   = 1; // FISMA report
$REPORT_TYPE_POAM    = 2; // POAM list
$REPORT_TYPE_GENERAL = 3; // General reports
$REPORT_TYPE_RAF     = 4; // General reports

$REPORT_GEN_BLSCR  = 1;   // NIST Baseline Security Controls Report
$REPORT_GEN_FIPS   = 2;   // FIPS 199 Category Breakdown
$REPORT_GEN_PRODS  = 3;   // Products with Open Vulnerabilities
$REPORT_GEN_SWDISC = 4;   // Software Discovered Through Vulnerability Assessments
$REPORT_GEN_TOTAL  = 5;   // Total # Systems with Open Vulnerabilities

$EMPTY_FIELD_INDICATOR = 'n/a';  // Placeholder for undefined data
$LEVEL_DEFAULT_LC      = 'none'; // Lowercase value of level enum default

/*
** If no report is requested, default to the general report selection screen
*/
$t = $REPORT_TYPE_GENERAL;
//if(isset($_REQUEST['t'])) { // Get report type if passed in
//	$t = intval($_REQUEST['t']);
if(isset($_POST['t'])) { // Get report type if passed in
	$t = intval($_POST['t']);
}

/*
** Collect general report subtype if passed in
*/
$grtype = isset($_POST['type'])?$_POST['type']:'';

/*
** Check if this is in reponse to a form submission
** (date range, filter or general report request).
*/
$sub = isset($_POST['sub'])?$_POST['sub']:'';


/*
** Check user permissions.
** Report is determined by a combination of report type and subtype.
**  FISMA: 1
**  POAM: 2
**  General report selection: 3
**  General reports: 31, 32, etc.
** Concatenate type and subtype, use this as index to lookup report function name.
** At time of writing there is no distinguishing between general reports as
** far as rights are concerned.
*/
$function_for = array(
 "$REPORT_TYPE_FISMA"                     => 'fisma_generate',
 "$REPORT_TYPE_POAM"                      => 'poam_generate',
 "$REPORT_TYPE_RAF"                       => 'raf_generate',
 "$REPORT_TYPE_GENERAL"                   => 'general_generate',
 "$REPORT_TYPE_GENERAL$REPORT_GEN_BLSCR"  => 'general_generate',
 "$REPORT_TYPE_GENERAL$REPORT_GEN_FIPS"   => 'general_generate',
 "$REPORT_TYPE_GENERAL$REPORT_GEN_PRODS"  => 'general_generate',
 "$REPORT_TYPE_GENERAL$REPORT_GEN_SWDISC" => 'general_generate',
 "$REPORT_TYPE_GENERAL$REPORT_GEN_TOTAL"  => 'general_generate',
);

$full_type = $t.$grtype;
if(!array_key_exists($full_type, $function_for)){
  $smarty->assign('err_msg', "Unknown report request ('$t', '$grtype')");
  $smarty->display('report_err.tpl');
  return;
  }

$function = $function_for[$full_type];

$generate_right = $user->checkRightByFunction($screen_name, $function);

/*if(!$generate_right) {
  $smarty->assign('err_msg', "Insufficient user privilege to generate this report.");
  $smarty->display('report_err.tpl');
  return;
  }
*/

/*
** Generate report or display generation request page
*/

$smarty->assign('sub', $sub);
$smarty->assign('t', $t);
$smarty->assign('nowy', date ("Y", time()));//now year,for select options..
//


if ($t==$REPORT_TYPE_FISMA){//report No. 1
	if ($sub){ //if submited
		$dr = $_POST['dr'];
		switch ($dr) {
			case "y"://if user select whole year
			$sy = $_POST['sy'];
			//$startdate="01/01/".$sy;
			//$enddate="12/31/".$sy;
			$startdate="${sy}-01-01";
			$enddate="${sy}-12-31";
			break;
			case "q"://if user select whole quarter
			$sq = $_POST['sq'];
			$sy = $_POST['sy'];
				switch ($sq) {
					case "1"://Q1
					//$startdate="01/01/".$sy;
					//$enddate="03/31/".$sy;
					$startdate="${sy}-01-01";
					$enddate="${sy}-03-31";
					break;
					case "2"://Q2
					$startdate="${sy}-04-01";
					$enddate="${sy}-06-30";
					break;
					case "3"://Q3
					$startdate="${sy}-07-01";
					$enddate="${sy}-09-30";
					break;
					case "4"://Q4
					$startdate="${sy}-10-01";
					$enddate="${sy}-12-31";
					break;
				}
			break;
			case "c"://if user select date range
			$startdate=$_POST['startdate'];
			$enddate=$_POST['enddate'];

			break;
		}
		$smarty->assign('startdate', $startdate);
		$smarty->assign('enddate', $enddate);
		//cal data

		$rpObj->setStartdate($startdate);//set start date
		$rpObj->setEnddate($enddate);//set end date

		// Retrieve FSA system and group IDs
		$fsa_system_id = $rpObj->getFSASysID();
		$fsa_sysgroup_id = $rpObj->getFSASysGroupID();

		$smarty->assign('AAW', $rpObj->getAAgencyWide($fsa_system_id));
		$smarty->assign('AS', $rpObj->getASystem($fsa_system_id, $fsa_sysgroup_id));
		$smarty->assign('BAW', $rpObj->getBAgencyWide($fsa_system_id));
		$smarty->assign('BS', $rpObj->getBSystem($fsa_system_id, $fsa_sysgroup_id));
		$smarty->assign('CAW', $rpObj->getCAgencyWide($fsa_system_id));
		$smarty->assign('CS', $rpObj->getCSystem($fsa_system_id, $fsa_sysgroup_id));
		$smarty->assign('DAW', $rpObj->getDAgencyWide($fsa_system_id));
		$smarty->assign('DS', $rpObj->getDSystem($fsa_system_id, $fsa_sysgroup_id));
		$smarty->assign('EAW', $rpObj->getEAgencyWide($fsa_system_id));
		$smarty->assign('ES', $rpObj->getESystem($fsa_system_id, $fsa_sysgroup_id));
		$smarty->assign('FAW', $rpObj->getFAgencyWide($fsa_system_id));
		$smarty->assign('FS', $rpObj->getFSystem($fsa_system_id, $fsa_sysgroup_id));
		//
		//

		//save report data to rpdata for other format report
		$_SESSION['rpdata']   = $smarty->get_template_vars();

		session_register('timeRange');//register a session var for title.
		$_SESSION['timeRange']=array($startdate,$enddate);

		//print_r($smarty->get_template_vars());
		//echo $startdate."<br>".$enddate;
	}

	$smarty->display('report.tpl');
}
if ($t==$REPORT_TYPE_POAM){

	if ($sub){ //if submitted
	 //get post vars,type and status is array
	 $system = isset($_POST['system'])?$_POST['system']:'';
	 $source = isset($_POST['source'])?$_POST['source']:'';
	 $sy     = isset($_POST['sy'])?$_POST['sy']:'';
	 $type   = isset($_POST['poam_type'])?$_POST['poam_type']:'';
	 $status = isset($_POST['status'])?$_POST['status']:'';

//print "<pre>";
//print_r($_POST);
//print "</pre>";


	 $rpObj->setSystem($system);
	 $rpObj->setSource($source);
	 $rpObj->setSy($sy);
	 $rpObj->setType($type);
	 $rpObj->setStatus($status);

	 // check to see if this is a case of a single-POAM report
	 // (for closure packet)
	 if(isset($_POST['poam_id'])) {
	   $rpObj->setPoamID($_POST['poam_id']);
	   }

	 // retrieve list of poams
	 $poams = $rpObj->getPOAMReport();



	 // For each poam, assess risk from
	 // confidentiality, integrity, availablity.
	 // Set 'risklevel' value in each record for use by template
	if($poams) {
	 foreach ($poams as &$poam_record) {
	   //
	   // Collect risk assessment component values from row fields
	   //
	   $conf   = $poam_record['confidentiality'];
	   $avail  = $poam_record['availability'];
	   $integ  = $poam_record['integrity'];
	   $crit   = $avail; // mission criticality is the same as availability
	   $threat = $poam_record['threatlevel'];
	   $effect = $poam_record['effectiveness'];
	   //
	   // Create assessment object, get full risk level.
	   // BUT ONLY IF the poam has the requisite threat_level and
	   // cmeasure_effectiveness fields set to something other than
	   // the default value 'None'
	   //

	   if ((strtolower($threat) == $LEVEL_DEFAULT_LC)
	       || (strtolower($effect) == $LEVEL_DEFAULT_LC)) {
	     $poam_record['risklevel'] = $EMPTY_FIELD_INDICATOR;
	     }
	   else {
	     $assess_obj = new RiskAssessment($conf, $avail, $integ, $crit, $threat, $effect);
	     $poam_record['risklevel'] = $assess_obj->get_overall_risk();
	     }

	   // Replace each blank field with placeholder text
	   foreach (array_keys($poam_record) as $column_name) {
	     if (strlen($poam_record[$column_name]) < 1) {
	       $poam_record[$column_name] = $EMPTY_FIELD_INDICATOR;
	       }
	     }

	   }
	 }

	 // Pass modified set of poam records to template for display.
	 $smarty->assign('rpdata', $poams);
	 $_SESSION['rpdata']   = $smarty->get_template_vars('rpdata');
	 session_register('POAMT');//register a session var for title.
	 $_SESSION['POAMT']=array($sy,$system,$source);


		//echo $sy;
//		echo $type;
//		print_r($HTTP_POST_VARS);
	}

	//
	// Retrieve lists of distinct systems and finding sources
	// from the database and use these to populate the dropdown
	// boxes for the filter.
	//
	$system_list = $rpObj->getSystems();
	$smarty->assign('systems', $system_list);
	$source_list = $rpObj->getSources();
	$smarty->assign('sources', $source_list);

	$smarty->display('report2.tpl');
}
else if ($t==$REPORT_TYPE_GENERAL){
	if ($sub){ //if submited
	//get post vars type
	$smarty->assign('grtype', $grtype);

	switch($grtype) {
	  case ($REPORT_GEN_FIPS): {
	    /*
	    ** FIPS is singled out to derive risk values.
	    */

            $systems = $rpObj->getReport32();

	    // Derive FIPS 199 category for each system
	    // This corresponds to the risk assessment data sensitivity value.
	    // Keep running count of each LOW/MODERATE/HIGH bin.
	    // mar-13-2006: Track indeterminate as well
	    //  - These are the result of POAMs with undefined threat_level,
	    //    countermeasure_effectiveness
	    $fips_totals = array();
	    $fips_totals['LOW']      = 0;
	    $fips_totals['MODERATE'] = 0;
	    $fips_totals['HIGH']     = 0;
	    $fips_totals[$EMPTY_FIELD_INDICATOR] = 0;

	    foreach ($systems as &$system) {
	      if (strtolower($system['conf']) != $LEVEL_DEFAULT_LC) {
	        $risk_obj = new RiskAssessment($system['conf'],  $system['avail'],  $system['integ'], NULL, NULL, NULL);
	        $fips199 = $risk_obj->get_data_sensitivity();
		}
	      else {
		$fips199 = $EMPTY_FIELD_INDICATOR;
		}

	      $system['fips'] = $fips199;

	      // Increment count for this fips category value
	      $fips_totals[$fips199] += 1;

	      // Mission criticality is the same as system availability
	      $system['crit'] = $system['avail'];
	      }


	    // Pass data to templates as array of tables.
	    // Table 0: full system list
	    // Table 1: derived FIPS totals
	    $fips_data = array();
	    $fips_data[] = $systems;
	    $fips_data[] = $fips_totals;

            $smarty->assign('rpdata', $fips_data);
            break;
	    }
          default: {
	    /*
	    ** All other reports are generated with getReport3X() calls
	    */
      	    eval("\$smarty->assign('rpdata', \$rpObj->getReport3$grtype());");
      	    	$colnum=10;//set data col num for Total # of Systems /w Open Vulnerabilities
							$smarty->assign('colnum', $colnum);
							$smarty->assign('colwidth',floor(100/($colnum+1)));
            }
 	  }

	$_SESSION['rpdata']   = $smarty->get_template_vars('rpdata');

	}
	//echo $type;
	$smarty->display('report3.tpl');
}
else if ($t==$REPORT_TYPE_RAF){
    $dbObj = new AssetDBManager($db);
	$system_list  = $dbObj->getSystemList();
	$smarty->assign('system_list', $system_list);
	$system_id = $_POST['system_id'];
	if(!empty($system_id) && is_numeric($system_id) && $system_id>0){
	    $sql = "SELECT poam_id FROM `POAMS` P
	               LEFT JOIN `FINDINGS` F ON F.finding_id = P.finding_id
	               LEFT JOIN `SYSTEM_ASSETS` SA ON SA.asset_id = F.asset_id
	               WHERE P.poam_threat_level != 'NONE' 
	                   AND P.poam_cmeasure_effectiveness != 'NONE' 
	                   AND SA.system_id=".$system_id;
	    $db->sql_query($sql);
	    $poam_ids = $db->sql_fetchrowset();
	    $num_poam_ids = $db->sql_numrows();
        $smarty->assign('poam_ids', $poam_ids);
        $smarty->assign('num_poam_ids', $num_poam_ids);
        $smarty->assign('system_id', $system_id);
	}
    $smarty->display('report4.tpl');
}

?>
