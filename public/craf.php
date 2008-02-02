<?PHP
session_start();
set_time_limit(0);
ini_set('memory_limit', '256M');
/*
** Internet Explorer + SSL needs to be able to write a PDF file to cache
** before launching Acrobat Reader.
**
** Disable Pragma: no-cache
*/
header('Pragma:');


require_once("ovms.ini.php"); // $PDF_FONT_FOLDER
require_once("config.php");

// db includes
require_once("dblink.php");
//
require_once("raf_lang.php");
require_once("notice_lang.php"); // $REPORT_FOOTER_WARNING
require_once("report_utils.php"); 
require_once("raf.class.php");

/*
** Check for user permission right away - will need db, Smarty for this.
*/
require_once("user.class.php");
require_once("smarty.inc.php");
$user = new User($db);
$loginstatus = $user->login();
if($loginstatus != 1) {
        // redirect to the login page
        $user->loginFailed($smarty);
        exit;
}
/*
** End permission check.
*/

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

//foreach(array_keys($rpdata) as $key) {
//echo "$key $rpdata[$key]<br/>";
//}
//die("");
if (!isset($_REQUEST['poam_id'])) die('needs poam_id');
$poam_id_array = explode(',',$_REQUEST['poam_id']);
if (count($poam_id_array)>1) {
    $files = array();
	foreach ($poam_id_array as $poam_id){
	    if (!empty($poam_id) && is_numeric($poam_id) && ($poam_id > 0)){
    	    $_POST['poam_id'] = $poam_id;
            require_once('raf.inc.php');
            $rpdata=$_SESSION['rpdata'];
            $rafObj = new Raf($db);
            $rafObj->setPoam_id($poam_id);
            $pdf = realCreatePdf($rafObj, $rpdata, $REPORT_FOOTER_WARNING);
    	    $fileName = OVMS_WEB_TEMP.'/RAF_'.$poam_id.'.pdf';
    	    $files[] = 'RAF_'.$poam_id.'.pdf';
    	    file_put_contents($fileName, $pdf->output());
	    }
	}
	$cmd = "cd ".OVMS_WEB_TEMP.";tar zcvf RAFs.tgz ".implode(' ',$files);
    `$cmd`;
    header("Content-type: application/octetstream");
    header('Content-Length: '.filesize(OVMS_WEB_TEMP."/RAFs.tgz"));
    header("Content-Disposition: attachment; filename=RAFs.tgz");
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    echo file_get_contents(OVMS_WEB_TEMP."/RAFs.tgz");
    @unlink(OVMS_WEB_TEMP."/RAFs.tgz");
    foreach ($files as $fileName) {
    	@unlink(OVMS_WEB_TEMP.'/'.$fileName);
    }
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
//    return $pdf;
    $options['Content-Disposition'] = 'RAF_'.$poam_id.'.pdf';
    $pdf->stream($options);
}

//$FONT_FOLDER = '/usr/local/apache2/htdocs/RR/cvs/fonts';


/*
** test - add footer
/
$all = $pdf->openObject();

$pdf->saveState();
$pdf->setStrokeColor(0,0,0,1);
$pdf->line(20,100,570,100);
//$pdf->line(20,822,578,822);
$xoffset = 20;
$yoffset = 90;
$fontsize = 8;
$leftover_text = $REPORT_FOOTER_WARNING;
while($leftover_text = $pdf->addTextWrap($xoffset,$yoffset,550,8,$leftover_text,'center') ) {
  $yoffset-=$fontsize;
  }
$pdf->restoreState();
$pdf->closeObject();
// note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
// or 'even'.
$pdf->addObject($all,'all');


*
** end test
*/


function realCreatePdf($rafObj, $rpdata, $REPORT_FOOTER_WARNING){
    
    $pdf =& new RAFpdf();
    $pdf->selectFont(PDF_FONT_FOLDER."/Helvetica.afm");//needs modify to the real font file path
    pdfAddWarningFooter($pdf, $REPORT_FOOTER_WARNING, 8, 50, 50, 580);
    
    $pdf->ezSetMargins(50,110,50,50);
    
    /*
    ** Separate out the POAM field row into its own variable for convenience.
    */
    $poam_fields = $rpdata['rpdata'][0];
    
    
    /*
    ** This table lists everything from 'Weakness/Vulnerability Tracking #'
    ** to the 'Weakness/Vulnerability Description' title
    */
    $data = array(
    array('<b>'.$raf_lang[1][0].'</b>',$rafObj->getWeaknessVulnerabilityTrackingNO(),'<b>'.$raf_lang[1][1].'</b>',$poam_fields['dt_discv']),
    array('<b>'.$raf_lang[1][2].'</b>',$poam_fields['s_po'],'<b>'.$raf_lang[1][4].'</b>',$poam_fields['dt_created']),
    array('<b>'.$raf_lang[1][3].'</b>',$poam_fields['s_nick'],'<b>'.$raf_lang[1][6].'</b>',$poam_fields['dt_mod']),
    array('<b>'.$raf_lang[1][7].'</b>',$poam_fields['fs_nick'],'<b>'.$raf_lang[1][8].'</b>',$poam_fields['dt_closed']),
    array('<b>'.$raf_lang[1][9].'</b>',$poam_fields['is_repeat'],'',''),
    array('<b>'.$raf_lang[1][10].'</b>',$poam_fields['prev'],'',''),
    array('<b>'.$raf_lang[1][5].'</b>','','',''),
    );
    
    /*
    ** Now push any vulnerability descriptions onto the previous table.
    ** Each 'row' contains one entry of name 'vuln'
    */
    $vuln_array = $rpdata['rpdata'][1];
    foreach ($vuln_array as $vuln_description) {
      array_push($data, array($vuln_description['vuln']));
      }
    
    
    $pdf->ezTable($data,NULL
    ,'<b>'.$raf_lang[0][0].$rafObj->getPoam_id().'</b>'
    ,array('showHeadings'=>0,'shaded'=>0,'showLines'=>0,'width'=>550));
    
    //
    $pdf->ezText("");
    
    $data = array(
    array('<b>'.$raf_lang[2][0].'</b>',$poam_fields['s_a']),
    array('<b>'.$raf_lang[2][1].'</b>',$poam_fields['s_c_just']),
    array('<b>'.$raf_lang[2][2].'</b>',$poam_fields['data_sensitivity']),
    array('<b>'.$raf_lang[2][3].'</b>',$poam_fields['s_s_just']),
    array('<b>'.$raf_lang[2][4].'</b>',$poam_fields['impact']),
    
    );
    
    /*
    ** Insert some space
    */
    $pdf->ezTable($data,NULL,"",
    array('showHeadings'=>0,'shaded'=>0,'showLines'=>0,'width'=>550));
    
    
    /*
    ** Set up the table column specifications
    ** - justification, etc.
    */
    $column_specs = array(
    0 => array('justification'=>'center'),
    1 => array('justification'=>'center'),
    2 => array('justification'=>'center'),
    3 => array('justification'=>'center'),
    );
    
    /*
    ** Set up the data for the impact table
    */
    $impactdata = array(
    array("<b>Data Sensitivity</b>","<b>Supportive</b>","<b>Important</b>","<b>Critical</b>"),
    array("<b>High</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>","<c:deemphasize>High</c:deemphasize>"),
    array("<b>Moderate</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>"),
    array("<b>Low</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Low</c:deemphasize>"),
    );
    
    highlight_cell_text(&$impactdata, $rpdata['impact_idx']);
    
    $pdf->ezTable($impactdata,NULL,
    "<b>Impact Table</b>",
    array('showHeadings'=>0,'width'=>550,'cols'=>$column_specs,'protectRows'=>5));
    
    
    
    ////
    //
    $pdf->ezText("");
    
    $data = array(
    array('<b>'.$raf_lang[3][0].'</b>',$poam_fields['cm']),
    array('<b>'.$raf_lang[3][1].'</b>',$poam_fields['cm_eff']),
    array('<b>'.$raf_lang[3][2].'</b>',$poam_fields['cm_just']),
    array('<b>'.$raf_lang[3][3].'</b>',$poam_fields['t_level']),
    array('<b>'.$raf_lang[3][4].'</b>',$poam_fields['t_source']),
    array('<b>'.$raf_lang[3][5].'</b>',$poam_fields['t_just']),
    array('<b>'.$raf_lang[3][6].'</b>',$poam_fields['threat_likelihood']),
    array('<b>'.$raf_lang[3][7].'</b>',''),
    );
    
    /*
    ** Now push any asset names onto the previous table.
    ** Each 'row' contains one entry of name 'pname'
    */
    $asset_array = $rpdata['rpdata'][2];
    if(isset($asset_array)) {
      foreach ($asset_array as $asset) {
        array_push($data, array($asset['pname']));
        }
      }
    
    
    $pdf->ezTable($data,NULL
    ,""
    ,array('showHeadings'=>0,'shaded'=>0,'showLines'=>0,'width'=>550));
    
    
    /*
    ** Set up the data for the threat table
    */
    $threatdata = array(
    array("<b>Threat Source</b>","<b>Low</b>","<b>Moderate</b>","<b>High</b>"),
    array("<b>High</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>","<c:deemphasize>High</c:deemphasize>"),
    array("<b>Moderate</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>"),
    array("<b>Low</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Low</c:deemphasize>"),
    );
    
    highlight_cell_text(&$threatdata, $rpdata['threat_idx']);
    
    $pdf->ezTable($threatdata,NULL,
    "<b>Threat Likelihood Table</b>",
    array('showHeadings'=>0,'width'=>550,'cols'=>$column_specs,'protectRows'=>5));
    ////
    //
    $pdf->ezText("");
    
    $data = array(
    
    array('<b>'.$raf_lang[4][1].'</b>',$raf_lang[4][2]),
    array('<b>'.$raf_lang[4][3].'</b>',$raf_lang[4][4]),
    array('<b>'.$raf_lang[4][5].'</b>',$raf_lang[4][6]),
    
    );
    $pdf->ezTable($data,NULL
    ,'<b>'.$raf_lang[4][0].'</b>'
    ,array('showHeadings'=>0,'shaded'=>0,'showLines'=>0,'width'=>550));
    
    
    /*
    ** Set up the data for the risk table
    */
    $riskdata = array(
    array("<b>Likelihood</b>","<b>Low</b>","<b>Moderate</b>","<b>High</b>"),
    array("<b>High</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>","<c:deemphasize>High</c:deemphasize>"),
    array("<b>Moderate</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>","<c:deemphasize>Moderate</c:deemphasize>"),
    array("<b>Low</b>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Low</c:deemphasize>","<c:deemphasize>Low</c:deemphasize>"),
    );
    
    highlight_cell_text(&$riskdata, $rpdata['risk_idx']);
    
    $pdf->ezTable($riskdata,NULL,
    "<b>Risk Level Table</b>",
    array('showHeadings'=>0,'width'=>550,'cols'=>$column_specs,'protectRows'=>5));
    ////
    $pdf->ezText("");
    
    $data = array(
    array('<b>'.$raf_lang[5][0].'</b>',$poam_fields['act_sug']),
    array('<b>'.$raf_lang[5][1].'</b>',$poam_fields['act_plan']),
    
    );
    
    /*
    ** Append security footer warning message
    $pdf->ezTable($data,NULL
    ,""
    ,array('showHeadings'=>0,'shaded'=>0,'showLines'=>0,'width'=>550));
    $pdf->ezText("");
    
    $pdf->ezText($REPORT_FOOTER_WARNING,9,array('justification'=>'center'));
    
    */
    
    
    return $pdf;
}
/*
** Highlight the text of a single cell in 3x3 multidimensional array 
** with column and row headers (a 4x4 array with 3x3 data of interest).
**
** The RAF tables can be described like this, where H is a column or 
** row header cell and 0-8 are table data cells:
** H H H H
** H 0 1 2
** H 3 4 5
** H 6 7 8
**
** We want to highlight the text in cell N.
** This function takes the 2D array of headers and data and modifies the correct
** data cell data.
**
** Data cells have been presumed de-emphasized via the <c:deemphasize> tags
*/
function highlight_cell_text($table_array_4x4, $highlight_cell) {
  // Skip the column headers, but ignore the row headers for this calculation
  $highlight_row = ($highlight_cell / 3) + 1;
  // Skip the row header to determine correct column
  $highlight_col = ($highlight_cell % 3) + 1;

  $cell_data = $table_array_4x4[$highlight_row][$highlight_col];

  $DEEMPHASIS_TAG_1 = '<c:deemphasize>';
  $DEEMPHASIS_TAG_2 = '</c:deemphasize>';
  $EMPHASIS_TAG_1   = '<c:emphasize><b>';
  $EMPHASIS_TAG_2   = '</b></c:emphasize>';

  $table_array_4x4[$highlight_row][$highlight_col] = str_replace($DEEMPHASIS_TAG_1, $EMPHASIS_TAG_1, $cell_data);
  $cell_data = $table_array_4x4[$highlight_row][$highlight_col];
  $table_array_4x4[$highlight_row][$highlight_col] = str_replace($DEEMPHASIS_TAG_2, $EMPHASIS_TAG_2, $cell_data);

  }


?>
