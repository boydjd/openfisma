<?PHP

require_once("config.php");
require_once("dblink.php");
require_once("smarty.inc.php");
require_once("assetDBManager.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");
// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

@$f=$_REQUEST['f'];
@$data = $_SESSION['asset_report_data'];
@$query_string = $_SESSION['query_string'];

$dbObj = new AssetDBManager($db);
$dbObj->setLimit(false);
$filter_data = $dbObj->searchAssets($query_string,0);
$report_filter_data = array();
	
	foreach ($filter_data as $key => $valarr) {
		if (is_array($valarr))
		{	
			$report_filter_data[] = array('Asset Name' => $valarr['asset_name'],
										  'System' => $valarr['system_name'],
										  'IP Address' => $valarr['address_ip'],
										  'Port' => $valarr['address_port'],
										  'Product' => $valarr['prod_name'],
										  'Vendor' => $valarr['prod_vendor']); 	
		}
	}	
	$_SESSION['asset_report_data'] = $report_filter_data;
    $data = $report_filter_data;

switch ($f)
{
	case 'p':   
        include (OVMS_VENDOR_PATH.'/pdf/class.ezpdf.php');
    require_once("report_lang.php");
	require_once("notice_lang.php"); // footer_warning()
	require_once("report_utils.php"); // pdfAddWarningFooter()
	$headinfo="Report run time: ".date("Y-m-d H:i:s");
	header('Pragma:');
	$RPT_ORIENTATION = 'orient';
	$RPT_PAPERTYPE   = 'pagesz';
	$RPT_PGWIDTH     = 'pgwidth';
	$RPT_PGHEIGHT    = 'pgheight';
	$report_stats = array(
	'poam' => array(
	$RPT_ORIENTATION => 'landscape',
	$RPT_PAPERTYPE => 'LETTER',
	$RPT_PGWIDTH => 792,
	),

	'portrait' => array(
	$RPT_ORIENTATION => 'portrait',
	$RPT_PAPERTYPE => 'LETTER',
	$RPT_PGWIDTH => 612,
	),

	'landscape' => array(
	$RPT_ORIENTATION => 'landscape',
	$RPT_PAPERTYPE => 'LETTER',
	$RPT_PGWIDTH => 792,
	),

	);
	//$pdf =& new Cezpdf();
	$pdf = open_PDF_doc($report_stats, 'portrait');
	//$pdf->selectFont('pdf/fonts/Helvetica.afm');
	//$pdf->ezTable($data);
        $REPORT_TITLE = "Asset Summary";
	$TABLE_WIDTH  = 500;

        $pdf->ezTable($data,null,$REPORT_TITLE,array('width'=>$TABLE_WIDTH));

	$pdf->ezStream();
	exit();
	break;
	
	
	case 'x':   require_once('Spreadsheet/Excel/Writer.php'); // need fix
	$workbook = new Spreadsheet_Excel_Writer();
	$worksheet =& $workbook->addWorksheet();

	$format_header =& $workbook->addFormat(array('Size' => 12,
	'Align' => 'center',
	'Color' => 'white',
	'FgColor' => 'black',
	'Font' => 'Times New Roman'));
	$format_title =& $workbook->addFormat(array('Size' => 12,
	'Align' => 'center',
	'Color' => 'black',
	'FgColor' => 'gray',
	'Font' => 'Times New Roman'));
	$format_field =& $workbook->addFormat(array('Size' => 10,
	'Align' => 'center',
	'Color' => 'black',

	'Font' => 'Times New Roman'));
	/*
	array('Asset Name' => $valarr['asset_name'],
	'System' => $valarr['system_name'],
	'IP Address' => $valarr['address_ip'],
	'Port' => $valarr['address_port'],
	'Product' => $valarr['prod_name'],
	'Vendor' => $valarr['prod_vendor']);
	*/

	$worksheet->write(0, 0, "Assets Report",$format_header);
	$c = 0;
	$r = 1;
	$title_done = false;
	foreach ($data as $key => $rowarr) {
		foreach($rowarr as $title => $value){
			if (!$title_done)
			{
				$worksheet->write($r, $c, $title,$format_title);
			}
			$worksheet->write($r+1, $c, $value,$format_field);
			$c++;

		}
		$c = 0;
		$r++;
		$title_done = true;

	}

	$worksheet->mergeCells(0,0,0,5);


	$workbook->send('report.xls');
	$workbook->close();


	break;
}

/*
** Centralize PDF declaration so we can launch different-sized reports for
** different report layout needs.
*/
function open_PDF_doc($report_stats, $report_name) {

        require_once("ovms.ini.php"); // $PDF_FONT_FOLDER

        $paper = $report_stats[$report_name]['pagesz'];
        $orientation = $report_stats[$report_name]['orient'];
        $pdf =& new Cezpdf($paper, $orientation);

//        $FONT_FOLDER = '/usr/local/apache2/htdocs/RR/cvs/fonts';
        $pdf->selectFont(PDF_FONT_FOLDER."/Helvetica.afm");//needs modify to the real font file path
        //$pdf->selectFont("/home/httpd2/pdf/fonts/Helvetica.afm");//needs modify to the real font file path

        /*
        ** Add security warning footer
        */
        $horiz_margin = 50;
        $vert_margin = 50;
        $page_width = $report_stats[$report_name]['pgwidth'];
//$w = footer_warning();
//die("w:$w");
        $warning_size = 8;
        pdfAddWarningFooter($pdf, footer_warning(), $warning_size, $horiz_margin, $vert_margin, $page_width);

	return($pdf);
	}
	//write NIST BaseLine table
	function writeNISTBaseLine($rdata,$rtitle){
			global $pdf,$report_lang;
			$tableWidth=500;
			$colNum=10;
		
		
		$data1 = array();
		$data2 = array();
		array_push($data1,$report_lang[3][1][1][3]);
		array_push($data2,$report_lang[3][1][1][4]);
		$total_num=0;
		foreach ($rdata as $v1) {
			array_push($data1,$v1[t]);
			array_push($data2,$v1[n]);
			$total_num=$total_num+$v1[n];
		}
		array_push($data1,$report_lang[3][1][1][5]);
		array_push($data2,$total_num);
		$data1=array_chunk($data1, $colNum);
		$data2=array_chunk($data2, $colNum);
		for ($i = 0; $i < count($data1); $i++){
			if ($i==0) $report_title=$rtitle;
			else $report_title="";
				$pdf->ezText("",20);
				$data3=array($data2[$i]);
				$pdf->ezTable($data3,
				$data1[$i],
				$report_title
				,array(
				'width'=>$tableWidth*(count($data2[$i])/count($data2[0])))
				);
		}

	} 	


?>
