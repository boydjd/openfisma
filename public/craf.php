<?PHP
set_time_limit(0);
ini_set('memory_limit', '256M');
/*
** Internet Explorer + SSL needs to be able to write a PDF file to cache
** before launching Acrobat Reader.
**
** Disable Pragma: no-cache
*/
header('Pragma:no-cache');

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");
require_once("raf_lang.php");
require_once("notice_lang.php"); // $REPORT_FOOTER_WARNING
require_once("report_utils.php"); 
require_once("raf.class.php");

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

include ( OVMS_VENDOR_PATH . '/pdf/class.ezpdf.php');
class RAFpdf extends Cezpdf {
  /*
  ** Callback function to highlight one cell of text in a table
  ** See http://www.ros.co.nz/pdf/readme.pdf and class.ezpdf.php alink()
  ** for more about callbacks.
  */
  function emphasize($state_info) {
    switch($state_info['status']) {
      case 'start':
      case 'sol':
        $this->saveState();
        $this->setColor(1,0,0);
        break;
      case 'end':
      case 'eol':
        $this->restoreState();
        break;
      }
    }

  /*
  ** Callback function to deemphasize one cell of text in a table
  ** See http://www.ros.co.nz/pdf/readme.pdf and class.ezpdf.php alink()
  ** for more about callbacks.
  */
  function deemphasize($state_info) {
    switch($state_info['status']) {
      case 'start':
      case 'sol':
        $this->saveState();
        $this->setColor(0.5,0.5,0.5);
        break;
      case 'end':
      case 'eol':
        $this->restoreState();
        break;
      }
    }

}

/*
** Retrieve report data from session
**
** Session data 'rpdata' contains the variables passed to the Smarty template:
**  'rpdata' - the report data itself
** Report data is an array of three SQL data sets:
** [0] - a single POAM statistics row
** [1] - a list of vulnerability description rows
** [2] - a list of affected server/asset rows
*/

if (!isset($_REQUEST['poam_id'])) die('needs poam_id');
$poam_id_array = explode(',',$_REQUEST['poam_id']);
if (count($poam_id_array)>1) {
    $fname = tempnam(OVMS_WEB_TEMP, "RAF");
    @unlink($fname);
    if(class_exists('ZipArchive')) {
        $fname .= '.zip';
        $flag = 'zip';
        $zip = new ZipArchive;
        $ret = $zip->open($fname, ZIPARCHIVE::CREATE);
        if(!($ret === TRUE) ) {
            echo ('Cannot create file '. $fname);
            exit();
        }
    }else{
        $flag = 'tgz';
        $files = array();
    }
  	foreach ($poam_id_array as $poam_id){
  	    if (!empty($poam_id) && is_numeric($poam_id) && ($poam_id > 0)){
      	    $_POST['poam_id'] = $poam_id;
              require_once('raf.inc.php');
              $rpdata=$_SESSION['rpdata'];
              $rafObj = new Raf($db);
              $rafObj->setPoam_id($poam_id);
              $pdf = realCreatePdf($rafObj, $rpdata, $REPORT_FOOTER_WARNING);
      	    $fileName = 'RAF_'.$poam_id.'.pdf';
              if($flag == 'zip'){
                  $zip->addFromString($fileName, $pdf->output());
              }else if($flag =='tgz'){
                  $files[] = $fileName;
                  file_put_contents(OVMS_WEB_TEMP.'/'.$fileName, $pdf->output());
              }
  	    }
  	}
    if( $flag == 'zip' ) {
        $zip->close();
    }else if( $flag == 'tgz' ) {
	    $cmd = "cd ".OVMS_WEB_TEMP.";tar zcvf RAFs.tgz ".implode(' ',$files);
        `$cmd`;
        foreach ($files as $file) {
        	@unlink(OVMS_WEB_TEMP.'/'.$file);
        }
        $fname = OVMS_WEB_TEMP.'/RAFs.tgz';
    }
    header("Content-type: application/octetstream");
    header('Content-Length: '.filesize($fname));
    header("Content-Disposition: attachment; filename=RAFs.$flag");
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    echo file_get_contents($fname);
    @unlink($fname);
    exit();
  }
else {
    $_POST['poam_id'] = $poam_id_array[0];
    require_once('raf.inc.php');
    $rpdata=$_SESSION['rpdata'];
    if(isset($rpdata['poam_id'])) {//which report,defalt is report 1.
    	$poam_id = intval($rpdata['poam_id']);
    }else{
    	die("needs poam_id");
    }
    $rafObj = new Raf($db);
    $rafObj->setPoam_id($poam_id);
    $pdf = realCreatePdf($rafObj, $rpdata, $REPORT_FOOTER_WARNING);
    $options['Content-Disposition'] = 'RAF_'.$poam_id.'.pdf';
    $pdf->stream($options);
}

function realCreatePdf($rafObj, $rpdata, $REPORT_FOOTER_WARNING){
    global $raf_lang;
    $pdf =& new RAFpdf();
    $pdf->selectFont(PDF_FONT_FOLDER."/Helvetica.afm");
    //pdfAddWarningFooter($pdf, $REPORT_FOOTER_WARNING, 8, 50, 50, 580); // TODO fix this
    
    $pdf->ezSetMargins(50,50,50,50);
    $headerOptions = array('showHeadings'=>0,
                           'width'=>500);
    $labelOptions = array('showHeadings'=>0,
                          'shaded'=>0,
                          'showLines'=>0,
                          'width'=>500);
    $tableOptions = array('showHeadings'=>0,
                          'shaded'=>0,
                          'showLines'=>2,
                          'width'=>400
                          );
          
    // Separate out the POAM field row into its own variable for convenience.
    $poam_fields = $rpdata['rpdata'][0];
    
    // RAF Title
    $pdf->ezText('Risk Analysis Form (RAF)',
                 20,
                 array('justification'=>'center')
                );

    // Vertical blank space
    $pdf->ezText('', 12, array('justification'=>'center'));
                    
    // Vulnerability/Weakness Section
    $data = array(array('<b>Vulnerability/Weakness</b>'));
    $pdf->ezTable($data,
                  null,
                  null,
                  $headerOptions
                 );
    $pdf->ezText('', 12, array('justification'=>'center'));

    $data = array(
      array("<b>Weakness Tracking #:</b>", $rafObj->getWeaknessVulnerabilityTrackingNO(), "<b>Date Opened:</b>", $poam_fields['dt_created']),
      array("<b>Principle Office:</b>","FSA","<b>System Acronym:</b>", $poam_fields['s_nick']),
      array("<b>Finding Source:</b>", $poam_fields['source_name'],"<b>Repeat finding?:</b>", $poam_fields['is_repeat']),
      array("<b>POA&M Type:</b>", $poam_fields['poam_type'],"<b>POA&M Status:</b>", $poam_fields['poam_status']),
      array("<b>Assets Affected:</b>", $poam_fields['asset_name'],"",""),
      array("<b>Finding:</b>", $poam_fields['finding_data'],"",""), // TODO this won't flow correctly if the finding data is a long block of text
    );

    $pdf->ezTable($data,
                  null,
                  null,
                  $labelOptions
                 );

    $pdf->ezText('', 12, array('justification'=>'center'));
    
    // System Impact section
    $data = array(array('<b>System Impact</b>'));
    $pdf->ezTable($data,
                  null,
                  null,
                  $headerOptions
                 );
    $pdf->ezText('', 12, array('justification'=>'center'));
    
    // the spaces are a quick hack to align the labels correctly
    $data = array(
      array("<b>                                                 IMPACT LEVEL TABLE</b>"),
      array("<b>                                                                            MISSION CRITICALITY</b>")
    );
    $pdf->ezTable($data,
                  null,
                  null,
                  $tableOptions
                 );
                                                         
    $data = array(
      array("<b>DATA SENSITIVITY</b>","<b>SUPPORTIVE</b>","<b>IMPORTANT</b>","<b>CRITICAL</b>"),
      array("<b>HIGH</b>",     "low", "moderate", "high"),
      array("<b>MODERATE</b>", "low", "moderate", "moderate"),
      array("<b>LOW</b>",      "low", "low",      "low"),
    );    
    $pdf->ezTable($data,
                  null,
                  null,
                  $tableOptions
                 );
    
    $pdf->ezText('', 12, array('justification'=>'center'));

    $data = array(
      array('<b>Mission Criticality:</b>', $poam_fields['s_a'],'',''),
      array('<b>Criticality Justification:</b>', $poam_fields['s_c_just'],'',''),
      array('<b>Data Sensitivity:</b>', $poam_fields['data_sensitivity'],'',''),
      array('<b>Sensitivity Justification:</b>', $poam_fields['s_s_just'],'',''),
      array('<b>Overall Impact Level:</b>', $poam_fields['impact'],'','')
    );

    $pdf->ezTable($data,
                  null,
                  null,
                  $labelOptions
                 );

    $pdf->ezText('', 12, array('justification'=>'center'));

    // Threats And Countermeasures section
    $data = array(array('<b>Threat(s) and Countermeasure(s)</b>'));
    $pdf->ezTable($data,
                  null,
                  null,
                  $headerOptions
                 );
    $pdf->ezText('', 12, array('justification'=>'center'));
    
    $data = array(
      array("<b>                                               THREAT LIKELIHOOD TABLE</b>"),
      array("<b>                                                                              COUNTERMEASURE</b>")
    );
    $pdf->ezTable($data,
                  null,
                  null,
                  $tableOptions
                 );
                                                         
    $data = array(
      array("<b>THREAT SOURCE</b>","<b>LOW</b>","<b>MODERATE</b>","<b>HIGH</b>"),
      array("<b>HIGH</b>",     "high",     "moderate", "low"),
      array("<b>MODERATE</b>", "moderate", "moderate", "low"),
      array("<b>LOW</b>",      "low",      "low",      "low"),
    );    
    $pdf->ezTable($data,
                  null,
                  null,
                  $tableOptions
                 );
    
    $pdf->ezText('', 12, array('justification'=>'center'));

    $data = array(
      array('<b>Specific Countermeasures:</b>', $poam_fields['cm'],'',''),
      array('<b>Countermeasure Effectiveness:</b>', $poam_fields['cm_eff'],'',''),
      array('<b>Effectiveness Justification:</b>', $poam_fields['cm_just'],'',''),
      array('<b>Threat Source(s):</b>', $poam_fields['t_source'],'',''),
      array('<b>Threat Impact:</b>', $poam_fields['t_level'],'',''),
      array('<b>Impact Level Justification:</b>', $poam_fields['t_just'],'',''),
      array('<b>Overall Threat Likelihood:</b>', $poam_fields['threat_likelihood'],'','')           
    );

    $pdf->ezTable($data,
                  null,
                  null,
                  $labelOptions
                 );

    $pdf->ezText('', 12, array('justification'=>'center'));

    // Risk Level section
    $data = array(array('<b>Risk Level</b>'));
    $pdf->ezTable($data,
                  null,
                  null,
                  $headerOptions
                 );
    $pdf->ezText('', 12, array('justification'=>'center'));
    
    $data = array(
      array("<b>                                                  RISK LEVEL TABLE</b>"),
      array("<b>                                                                              IMPACT</b>")
    );
    $pdf->ezTable($data,
                  null,
                  null,
                  $tableOptions
                 );
                                                         
    $data = array(
      array("<b>LIKELIHOOD</b>","<b>LOW</b>","<b>MODERATE</b>","<b>HIGH</b>"),
      array("<b>HIGH</b>",     "low",  "moderate", "high"),
      array("<b>MODERATE</b>", "low",  "moderate", "moderate"),
      array("<b>LOW</b>",      "low",  "low",      "low"),
    );    
    $pdf->ezTable($data,
                  null,
                  null,
                  $tableOptions
                 );
    
    $pdf->ezText('', 12, array('justification'=>'center'));

    $riskObj = new RiskAssessment($poam_fields['s_c'],
                      			      $poam_fields['s_a'],
                      			      $poam_fields['s_i'],
                      			      $poam_fields['s_a'],
                      			      $poam_fields['t_level'],
                      			      $poam_fields['cm_eff']);
    $overallRisk = $riskObj->get_overall_risk();

    $data = array(
      array('<b>High:</b>', 'Strong need for corrective action'),
      array('<b>Moderate:</b>', 'Need for corrective action within a reasonable time period.'),
      array('<b>Low:</b>', 'Authorizing official may correct or accept the risk'),
      array('<b>Overall Risk Level:</b>', $overallRisk)       
    );

    $pdf->ezTable($data,
                  null,
                  null,
                  $labelOptions
                 );

    $pdf->ezText('', 12, array('justification'=>'center'));

    // Mitigration Strategy section
    $data = array(array('<b>Mitigation Strategy</b>'));
    $pdf->ezTable($data,
                  null,
                  null,
                  $headerOptions
                 );
    $pdf->ezText('', 12, array('justification'=>'center'));
    
    $data = array(
      array('<b>Recommendation(s):</b>', $poam_fields['act_sug']),
      array('<b>Course of Action:</b>', $poam_fields['act_plan']),
      array('<b>Est. Completion Date:</b>', $poam_fields['poam_action_date_est']),    
    );

    $pdf->ezTable($data,
                  null,
                  null,
                  $labelOptions
                 );

    $pdf->ezText('', 12, array('justification'=>'center'));

    // Accepted Risk section (conditional on poam type)
    if ($poam_fields['poam_type_code'] == 'AR') {
      $data = array(array('<b>AR - (Recommend accepting this low risk)</b>'));
      $pdf->ezTable($data,
                    null,
                    null,
                    $headerOptions
                   );
      $pdf->ezText('', 12, array('justification'=>'center'));
      
      $pdf->ezText('<b>Vulnerability:</b>', 12, null);
      $pdf->ezText($poam_fields['finding_data'], 12, null);
      $pdf->ezText('<b>Business Case Justification for accepted low risk:</b>', 12, null);
      $pdf->ezText($poam_fields['act_plan'], 12, null);
      $pdf->ezText('<b>Mitigating Controls:</b>', 12, null);
      $pdf->ezText($poam_fields['cm'], 12, null);                              
        
      $pdf->ezText('', 12, array('justification'=>'center'));
    }
    
    // Endorsement of Risk Level Analysis section
    $data = array(array('<b>Endorsement of Risk Level Analysis</b>'));
    $pdf->ezTable($data,
                  null,
                  null,
                  $headerOptions
                 );
    $pdf->ezText('', 12, array('justification'=>'center'));
    
    $data = array(
      array('Concur __ ', 'Non-Concur __ ','_____________________________________________','___/___/______'),
      array('',           '',              'Business Owner/Representative',                'Date')
    );

    $pdf->ezTable($data,
                  null,
                  null,
                  $labelOptions
                 );

    $pdf->ezText('', 12, array('justification'=>'center'));
    
    $warning = 'WARNING: This report is for internal, official use only.  
                This report contains sensitive computer security related information. 
                Public disclosure of this information would risk circumvention of the law. 
                Recipients of this report must not, under any circumstances, show or release 
                its contents for purposes other than official action. This report must be 
                safeguarded to prevent improper disclosure. Staff reviewing this document must 
                hold a minimum of Public Trust Level 5C clearance.';
    $warning = preg_replace('/\s+/',' ',$warning);
    $pdf->ezText($warning, 9, array('justification'=>'left'));
        
    return $pdf;
}

?>