<?PHP
/*
use this file to generate the pdf&excel format reports
var 'f' report format. p==>pdf e ==>excel
var 't' report type. report 1,report 2...
we use third part tools to generate excel and pdf files.

pdf

http://www.ros.co.nz/pdf/
download http://www.ros.co.nz/pdf/downloads.php?f=pdfClassesAndFonts_009e.zip and unzip it.
change include_path setting in php.ini or move the file to existing path.
pay more attention on these two lines:
*	include ('pdf/class.ezpdf.php');
*	$pdf->selectFont('../pdf/fonts/Helvetica.afm');//needs modify to the real font file path

excel

we use pear package named Spreadsheet_Excel_Writer.
http://pear.php.net/package/Spreadsheet_Excel_Writer
download http://pear.php.net/get/Spreadsheet_Excel_Writer-0.9.0.tgz from this website.
install this package follow this guide http://pear.php.net/manual/en/installation.cli.php
pay more attention on this line:
*require_once 'Spreadsheet/Excel/Writer.php';

my php configure Command:
'./configure'
'--prefix=/usr/local/php5'
'--with-mysql=/usr/local/mysql'
'--with-apxs2=/usr/local/apache/bin/apxs'
'--with-zlib'
'--with-xml'
'--with-pear'
'--with-gd'
'--enable-mbstring'

FYI

*/
//ob_start();
session_start();

/*
** Check for user permission right away - will need db, Smarty for this.
*/
require_once("config.php");
require_once("dblink.php");
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

/*
** Internet Explorer + SSL needs to be able to write a PDF file to cache
** before launching Acrobat Reader.
**
** Disable Pragma: no-cache
*/
header('Pragma:');


//print_r($_POST);
//exit;

//all same stuff in 3 formats report(html,pdf,excel),such as report name,field name ..
require_once("report_lang.php");
require_once("notice_lang.php"); // footer_warning()
require_once("report_utils.php"); // pdfAddWarningFooter()
$headinfo="Report run time: ".date("Y-m-d H:i:s");

//
// Explicitly list the order in which POAM data fields are
// to be printed left-to-right. As one or more fields 
// are derived from others ('risklevel') we can't rely
// on simple integer indexing to line up the columns.
//

$poam_col_names[] = 'po';
$poam_col_names[] = 'system';
$poam_col_names[] = 'tier';
$poam_col_names[] = 'findingnum';
$poam_col_names[] = 'finding';
$poam_col_names[] = 'ptype';
$poam_col_names[] = 'pstatus';
$poam_col_names[] = 'source';
$poam_col_names[] = 'SD';
$poam_col_names[] = 'location';
$poam_col_names[] = 'risklevel';
$poam_col_names[] = 'recommendation';
$poam_col_names[] = 'correctiveaction';
$poam_col_names[] = 'EstimatedCompletionDate';
		
$num_poam_cols = count($poam_col_names);

/*
** Set up page dimensions and orientations for the various reports
** here so we can see at a glance how everything is laid out.
** letter -> 612x792, legal -> 612x1008
*/
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


//print_r($_POST);
//exit;


//obtain result data from session, we only need query DB once for one data
$rpdata=$_SESSION['rpdata'];

//@$f=$_REQUEST['f'];
//@$t=$_REQUEST['t'];
@$f=$_POST['f'];
@$t=$_POST['t'];

//print "$f<br>";
//print "$t<br>";
//exit;


switch ($f) {

	case "p"://pdf format
	
		include ( VENDER_TOOL_PATH . '/pdf/class.ezpdf.php');

		switch ($t) {

			/*
			** FISMA report - PDF
			*/
			case "1"://report 1

				$pdf = open_PDF_doc($report_stats, 'portrait');

				$cols = array($report_lang[1][1],$report_lang[1][2],$report_lang[1][3],$report_lang[1][4],$report_lang[1][5]);

				//Print_R($rpdata);
				$data = array(
					array($report_lang[1][6],$rpdata['AAW'],$rpdata['AS'],$rpdata['AAW']+$rpdata['AS']),
					array($report_lang[1][7],$rpdata['BAW'],$rpdata['BS'],$rpdata['BAW']+$rpdata['BS']),
					array($report_lang[1][8],$rpdata['CAW'],$rpdata['CS'],$rpdata['CAW']+$rpdata['CS']),
					array($report_lang[1][9],$rpdata['DAW'],$rpdata['DS'],$rpdata['DAW']+$rpdata['DS']),
					array($report_lang[1][10],$rpdata['EAW'],$rpdata['ES'],$rpdata['EAW']+$rpdata['ES']),
					array($report_lang[1][11],$rpdata['FAW'],$rpdata['FS'],$rpdata['FAW']+$rpdata['FS']),
					);
	
				//get var of timeRange
				//0 -> startdate
				//1 -> enddate
				$timeRange=$_SESSION['timeRange'];

				$pdf->ezText($report_lang[1][0],20,
					array('justification'=>'center')
					);

				$pdf->ezText($timeRange[0]."-".$timeRange[1],12,array('justification'=>'center'));

				$pdf->ezTable($data,$cols," ",array('width'=>550));
				break;

			/*
			** POAM report - PDF
			*/
			case "2"://report 2

				$column_widths = array(25,38,25,42,100,28,34,40,42,42,58,80,100,60);
				$pdf = open_PDF_doc($report_stats, 'landscape');

				$cols = array($report_lang[2][1],$report_lang[2][2],$report_lang[2][3],$report_lang[2][4],$report_lang[2][5]
					,$report_lang[2][6],$report_lang[2][7],$report_lang[2][8],$report_lang[2][9],$report_lang[2][10],$report_lang[2][11]
					,$report_lang[2][12],$report_lang[2][13],$report_lang[2][14]);

				//Print_R($rpdata);
				$data = array();

				for($i=0;$i<count($rpdata);$i++){

					$data_tmp=array();
					//array_push($data,$rpdata[$i]);

					//for ($j=1;$j<count($rpdata[$i])+1;$j++){
					for ($j=0;$j<$num_poam_cols;$j++){

//						array_push($data_tmp,$rpdata[$i][$j]);
						$column_name = $poam_col_names[$j];
						//echo "$column_name $rptdata[$i][$column_name]\n";
						array_push($data_tmp,$rpdata[$i][$column_name]);

					} // for

					array_push($data,$data_tmp);
					unset ($data_tmp);

				} // for

				// prepare the executable column width array
				$table_col_widths = array();

				for ($i = 0; $i < count($column_widths); $i++) {

					$column_spec = array('width'=>$column_widths[$i]);
					$table_col_widths[$i]=$column_spec;

				} // for

				//get var of year,system and source
				//0 -> year
				//1 -> system
				//2 -> source
				$POAMT=$_SESSION['POAMT'];
		  
				if (strlen($POAMT[0]) > 0) { $poam_hdr = "POA&M report for fiscal year of ".$POAMT[0]; }
				else { $poam_hdr = "POA&M Report"; }

				$pdf->ezText($poam_hdr,20,
					array('justification'=>'center')
					);

				//if system  and source is empty,mean all system and source
				if (strlen($POAMT[1]) < 1) { $systemTitle = "This POA&M report is generated for all systems"; }
				else { $systemTitle = "This POA&M report is generated for system " . $POAMT[1]; }

				if (strlen($POAMT[2]) < 1) { $sourceTitle = "based on the reporting of all sources"; }
				else { $sourceTitle = "based on the reporting of source " . $POAMT[2]; }

				$secTitle = "$systemTitle $sourceTitle";  
		  
				$pdf->ezText($secTitle,12,array('justification'=>'center'));
		  

				//$pdf->ezTable($data,$cols,$report_lang[2][0],
				$pdf->ezTable($data,$cols," ",
					array('fontSize'=>8,'maxWidth'=>690,'cols'=>$table_col_widths)
					);
				break;
		
			/*
			** Baseline Security Control report - PDF
			*/
			case "31"://report 31
	
				//$pdf = open_PDF_doc($report_stats, 'portrait');
				$pdf = open_PDF_doc($report_stats, 'landscape');
		
				// report title + separation
				$blanks = array();
				array_push($blanks, array());
		                $pdf->ezTable($blanks,'',$report_lang[3][0][1],array('showLines'=>0));

				//$pdf->ezSetMargins(0,50,100,100); // now done in setPDFFooters
				//adjust table position
				//$yoffset=550;

				writeNISTBaseLine($rpdata[0],$report_lang[3][1][1][0]);
				writeNISTBaseLine($rpdata[1],$report_lang[3][1][1][1]);
				writeNISTBaseLine($rpdata[2],$report_lang[3][1][1][2]);
	
				break;

				////
			/*
			** FIPS 199 Categorization Breakdown - PDF
			*/
			case "32":
	
				$column_widths = array(100,100,70,70,70,70,70,70);
				$pdf = open_PDF_doc($report_stats, 'landscape');

				//small table
				$col=array($report_lang[3][1][2][0],$report_lang[3][1][2][1],
					$report_lang[3][1][2][2],$report_lang[3][1][2][3]);
					$data=array(
						array($report_lang[3][1][2][4],$rpdata[1]['LOW'],
							$rpdata[1]['MODERATE'],$rpdata[1]['HIGH']),						
					);

				$pdf->ezTable($data,$col,$report_lang[3][0][2]);
				$pdf->ezText("");
				$pdf->ezText("",20);

				//add pie chart
				$tmp_purl=parse_url($_SERVER['HTTP_REFERER']);
				$tmpUrl=$tmp_purl[scheme]."://".$_SERVER["SERVER_NAME"].dirname($_SERVER["SCRIPT_NAME"]).
				//$tmpUrl="http://61.129.112.18/httpd2/ovms" . 
					"/piechart.php?data[]=".$rpdata[1]['LOW']."&data[]=".$rpdata[1]['MODERATE']."&data[]=".$rpdata[1]['HIGH']."";
//die("$tmpUrl");

				// commented out until we get https to work or temp image file		
				//$pdf->ezImage($tmpUrl,0,NULL,"none");
	
				$data=array();
				$col=array();

				//////////
				//big table 
		
				for ($i = 0; $i < 8; $i++) { array_push($col,$report_lang[3][1][2][5+$i]); }

				//array_push($data,$arr_tmp);
				for($i=0;$i<count($rpdata[0]);$i++){

					array_push($data,array(
						$rpdata[0][$i]['name'],
						$rpdata[0][$i]['type'],
						$rpdata[0][$i]['crit'],
						$rpdata[0][$i]['fips'],
						$rpdata[0][$i]['conf'],
						$rpdata[0][$i]['integ'],
						$rpdata[0][$i]['avail'],
						$rpdata[0][$i]['last_upd']));
				} // for
		
				// prepare the executable column width array
				$table_col_widths = array();

				for ($i = 0; $i < count($column_widths); $i++) {

					$column_spec = array('width'=>$column_widths[$i]);
					$table_col_widths[$i]=$column_spec;

				}

				$pdf->ezTable($data,$col,'',
					array('fontSize'=>8,'maxWidth'=>690,'cols'=>$table_col_widths)
					);

				break;


			/*
			** Products with Open Vulnerabilities - PDF
			*/
			case "33": 

				$pdf = open_PDF_doc($report_stats, 'portrait');

				$col=array();
				$data=array();
		
				for ($i = 0; $i < 4; $i++) { array_push($col,$report_lang[3][1][3][$i]); }

				//array_push($data,$arr_tmp);
				for($i=0;$i<count($rpdata);$i++){

					array_push($data,array(
						$rpdata[$i]['Vendor'],
						$rpdata[$i]['Product'],
						$rpdata[$i]['Version'],
						$rpdata[$i]['NumoOV']));
				} // for
		
		
				$pdf->ezTable($data,$col,$report_lang[3][0][3],array('xPos'=>'center'
					,'width'=>550));		
	
				break;

			/*
			** Software Discovered Through Vulnerability Assessments
			*/
			case "34":

				$pdf = open_PDF_doc($report_stats, 'portrait');

				$col=array();
				$data=array();
		
				for ($i = 0; $i < 3; $i++) { array_push($col,$report_lang[3][1][4][$i]); }

				//array_push($data,$arr_tmp);
				for($i=0;$i<count($rpdata);$i++){

					array_push($data,array(
						$rpdata[$i]['Vendor'],
						$rpdata[$i]['Product'],
						$rpdata[$i]['Version']));

				} // for	
		
				$pdf->ezTable($data,$col,$report_lang[3][0][4],array('xPos'=>'center'
					,'width'=>550));		

				break;

			/*
			** Total # of System /w Open Vulnerabilities - PDF
			*/
			case "35":

				$pdf = open_PDF_doc($report_stats, 'portrait');

				//array(array($rpdata[0][0]['totalnumber']))
				$data = array(
					array($rpdata[0])
					);

				$pdf->ezTable($data,
					array($report_lang[3][1][5][0]),
					$report_lang[3][0][5],array('xPos'=>'center'
					,'width'=>200));

				$pdf->ezText("");

				$data = array();
				$total_num=0;

				foreach ($rpdata[1] as $v1) {
					array_push($data,array($v1['nick'],$v1['num']));
					$total_num=$total_num+$v1['num'];
				}

				array_push($data,array($report_lang[3][1][5][3],$total_num));
	
				$pdf->ezTable($data,
					array($report_lang[3][1][5][1],$report_lang[3][1][5][2]),
					'',array('xPos'=>'center'
					,'width'=>200));
		
				break;

		} // switch ($t)
		
		
		$pdf->ezStream();
		exit();
		break;


	case "e"://excel format

		require_once ('/usr/share/pear/Spreadsheet/Excel/Writer.php'); // need fix 

		$workbook  = new Spreadsheet_Excel_Writer();
		$workbook->setVersion(8); // fixes 255 char truncation issue

		$worksheet =& $workbook->addWorksheet();

		// 
		// excel font setting size, color
		// 
		$format_header =& $workbook->addFormat(array('Size' => 10,
			'Align' => 'center',
			'Color' => 'white',
			'FgColor' => 'black',
			));

		$format_times =& $workbook->addFormat(array('Size' => 10,
			'Align' => 'center',
			'Color' => 'black',
			'BorderColor '=> 'blue',
			'Bottom'=>1,'Top'=>1,'Left'=>1,'Right'=>1,
			//'FgColor' => 'white',
			));

		// 
		// font style
		//
		$format_header->setFontFamily('Times New Roman');
		$format_times->setFontFamily('Times New Roman');
	
		//
		//	$format_courier =& $workbook->addFormat();
		//	$format_courier->setFontFamily('Courier');
		$rowi=0;
		$worksheet->write($rowi, 0, $headinfo,$format_times);
		$worksheet->mergeCells($rowi,0,$rowi,2);
		$rowi++;

		switch ($t) {

			/*
			** FISMA report - Excel
			*/
			case "1"://report 1

				$worksheet->write($rowi, 0, $report_lang[1][0],$format_header);
				$worksheet->mergeCells($rowi,0,$rowi,4);
				$rowi++;
				$worksheet->write($rowi, 0,$report_lang[1][1] , $format_times);
				$worksheet->write($rowi, 1,$report_lang[1][2] , $format_times);
				$worksheet->write($rowi, 2,$report_lang[1][3] , $format_times);
				$worksheet->write($rowi, 3,$report_lang[1][4] , $format_times);
				$worksheet->write($rowi, 4,$report_lang[1][5] , $format_times);
				$rowi++;
				$worksheet->write($rowi, 0,$report_lang[1][6] , $format_times);
				$worksheet->write($rowi, 1,$rpdata['AAW'] , $format_times);
				$worksheet->write($rowi, 2,$rpdata['AS'] , $format_times);
				$worksheet->write($rowi, 3, $rpdata['AAW']+$rpdata['AS'], $format_times);
				$worksheet->write($rowi, 4, "", $format_times);
				$rowi++;
				$worksheet->write($rowi, 0,$report_lang[1][7] , $format_times);
				$worksheet->write($rowi, 1,$rpdata['BAW'], $format_times);
				$worksheet->write($rowi, 2,$rpdata['BS'] , $format_times);
				$worksheet->write($rowi, 3,$rpdata['BAW']+$rpdata['BS'] , $format_times);
				$worksheet->write($rowi, 4, "", $format_times);
				$rowi++;
				$worksheet->write($rowi, 0,$report_lang[1][8] , $format_times);
				$worksheet->write($rowi, 1,$rpdata['CAW'] , $format_times);
				$worksheet->write($rowi, 2,$rpdata['CS'] , $format_times);
				$worksheet->write($rowi, 3,$rpdata['CAW']+$rpdata['CS']  , $format_times);
				$worksheet->write($rowi, 4, "", $format_times);
				$rowi++;
				$worksheet->write($rowi, 0,$report_lang[1][9] , $format_times);
				$worksheet->write($rowi, 1, $rpdata['DAW'], $format_times);
				$worksheet->write($rowi, 2,$rpdata['DS'] , $format_times);
				$worksheet->write($rowi, 3, $rpdata['DAW']+$rpdata['DS'] , $format_times);
				$worksheet->write($rowi, 4, "" , $format_times);
				$rowi++;
				$worksheet->write($rowi, 0,$report_lang[1][10] , $format_times);
				$worksheet->write($rowi, 1,$rpdata['EAW'] , $format_times);
				$worksheet->write($rowi, 2,$rpdata['ES'] , $format_times);
				$worksheet->write($rowi, 3,$rpdata['EAW']+$rpdata['ES']  , $format_times);
				$worksheet->write($rowi, 4, "" , $format_times);
				$rowi++;
				$worksheet->write($rowi, 0,$report_lang[1][11] , $format_times);
				$worksheet->write($rowi, 1,$rpdata['FAW'] , $format_times);
				$worksheet->write($rowi, 2, $rpdata['FS'], $format_times);
				$worksheet->write($rowi, 3, $rpdata['FAW']+$rpdata['FS'] , $format_times);
				$worksheet->write($rowi, 4, "" , $format_times);
		
				$worksheet->setColumn(0,0,55);
				$worksheet->setColumn(0,1,12);
				$worksheet->setColumn(0,2,7);
				$worksheet->setColumn(0,3,5);
				$worksheet->setColumn(0,4,15);

				break;

			/*
			** POAM report - Excel
			*/
			case "2"://report 2

				$worksheet->write($rowi, 0, $report_lang[2][0],$format_header);
				$worksheet->mergeCells($rowi,0,$rowi,13);
				$rowi++;

				for ($i = 0; $i < 14; $i++) { $worksheet->write($rowi,$i,$report_lang[2][$i+1],$format_times); }

				$rowi++;
				for($i=0;$i<count($rpdata);$i++){

//					for ($j=1;$j<count($rpdata[$i])+1;$j++){
					for ($j=0;$j<$num_poam_cols;$j++){

						$col_name = $poam_col_names[$j];
						//echo "$col_name<br/>";
						$field_val = $rpdata[$i][$col_name];
						//$worksheet->write(2+$i,$j-1,$rpdata[$i][$j],$format_times);
						$worksheet->write($rowi,$j,$field_val,$format_times);


					} // for $j
	
					$rowi++;

				} // for $i
		
				break;


			/*
			** Baseline Security Control report - Excel
			*/
			case "31"://report 31
//a
				$worksheet->write($rowi,0,$report_lang[3][1][1][0],$format_header);
				$worksheet->mergeCells($rowi,0,$rowi,1);
//b
				$worksheet->write($rowi,3, $report_lang[3][1][1][1],$format_header);
				$worksheet->mergeCells($rowi,3,$rowi,4);
//c
				$worksheet->write($rowi,6, $report_lang[3][1][1][2],$format_header);
				$worksheet->mergeCells($rowi,6,$rowi,7);
				$rowi++;
//a		
				$worksheet->write($rowi,0,$report_lang[3][1][1][3],$format_times);
				$worksheet->write($rowi,1,$report_lang[3][1][1][4],$format_times);
//b		
				$worksheet->write($rowi,3,$report_lang[3][1][1][3],$format_times);
				$worksheet->write($rowi,4,$report_lang[3][1][1][4],$format_times);
//c
				$worksheet->write($rowi,6,$report_lang[3][1][1][3],$format_times);
				$worksheet->write($rowi,7,$report_lang[3][1][1][4],$format_times);
				$rowi++;
				$rowi_sta=$rowi;
//a		
				$total_num=0;

				foreach ($rpdata[0] as $v1) {

					$worksheet->write($rowi,0,$v1[t],$format_times);
					$worksheet->write($rowi,1,$v1[n],$format_times);
					$total_num=$total_num+$v1[n];
					$rowi++;

				}

				$worksheet->write($rowi,0,$report_lang[3][1][1][5],$format_times);
				$worksheet->write($rowi,1,$total_num,$format_times);
//b
				$rowi=$rowi_sta;
				$total_num=0;

				foreach ($rpdata[1] as $v1) {

					$worksheet->write($rowi,3,$v1[t],$format_times);
					$worksheet->write($rowi,4,$v1[n],$format_times);
					$total_num=$total_num+$v1[n];
					$rowi++;

				}

				$worksheet->write($rowi,3,$report_lang[3][1][1][5],$format_times);
				$worksheet->write($rowi,4,$total_num,$format_times);
//c
				$rowi=$rowi_sta;
				$total_num=0;

				foreach ($rpdata[2] as $v1) {

					$worksheet->write($rowi,6,$v1[t],$format_times);
					$worksheet->write($rowi,7,$v1[n],$format_times);
					$total_num=$total_num+$v1[n];
					$rowi++;

				}

				$worksheet->write($rowi,6,$report_lang[3][1][1][5],$format_times);
				$worksheet->write($rowi,7,$total_num,$format_times);

//format ...

				$worksheet->setColumn(0,0,13);
				$worksheet->setColumn(0,1,17);
				$worksheet->setColumn(1,2,2);
				$worksheet->setColumn(1,3,13);
				$worksheet->setColumn(1,4,17);
				$worksheet->setColumn(1,5,2);
				$worksheet->setColumn(1,6,13);
				$worksheet->setColumn(1,7,17);

				break;

			/*
			** FIPS 199 Report - Excel
			*/
			case 32:

				// FIPS totals table
				// Column headers 0, 1, 2, 3, and row header 4 come from report_lang.
				// The three data fields come from rpdata[1].

				// Totals table header
				$worksheet->write($rowi,0,$report_lang[3][1][2][0],$format_header);
				$worksheet->write($rowi,1,$report_lang[3][1][2][1],$format_header);
				$worksheet->write($rowi,2,$report_lang[3][1][2][2],$format_header);
				$worksheet->write($rowi,3,$report_lang[3][1][2][3],$format_header);
				$rowi++;

				// Totals table single row
				$worksheet->write($rowi,0,$report_lang[3][1][2][4],$format_header);
				$worksheet->write($rowi,1,$rpdata[1]['LOW'],$format_times);
				$worksheet->write($rowi,2,$rpdata[1]['MODERATE'],$format_times);
				$worksheet->write($rowi,3,$rpdata[1]['HIGH'],$format_times);
				$rowi++;

				// Insert a space between tables
				$worksheet->write($rowi,0,' ',$format_times);
				$rowi++;

				// System detail table
				// Column headers from report_lang.
		
				// Detail header row
				$worksheet->write($rowi,0,$report_lang[3][1][2][5],$format_header);
				$worksheet->write($rowi,1,$report_lang[3][1][2][6],$format_header);
				$worksheet->write($rowi,2,$report_lang[3][1][2][7],$format_header);
				$worksheet->write($rowi,3,$report_lang[3][1][2][8],$format_header);
				$worksheet->write($rowi,4,$report_lang[3][1][2][9],$format_header);
				$worksheet->write($rowi,5,$report_lang[3][1][2][10],$format_header);
				$worksheet->write($rowi,6,$report_lang[3][1][2][11],$format_header);
				$worksheet->write($rowi,7,$report_lang[3][1][2][12],$format_header);

				$rowi++;
	
				foreach ($rpdata[0] as $row) {

					$worksheet->write($rowi,0,$row['name'],$format_times);
					$worksheet->write($rowi,1,$row['type'],$format_times);
					$worksheet->write($rowi,2,$row['crit'],$format_times);
					$worksheet->write($rowi,3,$row['fips'],$format_times);
					$worksheet->write($rowi,4,$row['conf'],$format_times);
					$worksheet->write($rowi,5,$row['integ'],$format_times);
					$worksheet->write($rowi,6,$row['avail'],$format_times);
					$worksheet->write($rowi,7,$row['last_upd'],$format_times);
					$rowi++;

				}

		  		$worksheet->setColumn(0,0,14);
  				$worksheet->setColumn(3,3,12);
  				$worksheet->setColumn(7,7,19);

				break;

			/*
			**  Products with Open Vulnerabilities - Excel
			*/
			case "33":

				//$rowi=0;
				$worksheet->write($rowi,0,$report_lang[3][0][3],$format_header);
				$worksheet->mergeCells($rowi,0,$rowi,3);
				$rowi++;

				for ($i = 0; $i < 4; $i++) { $worksheet->write($rowi,$i,$report_lang[3][1][3][0+$i],$format_header); }

				$rowi++;

				for($i=0;$i<count($rpdata);$i++){

					$worksheet->write($rowi+$i,0,$rpdata[$i]['Vendor'],$format_times);
					$worksheet->write($rowi+$i,1,$rpdata[$i]['Product'],$format_times);
					$worksheet->write($rowi+$i,2,$rpdata[$i]['Version'],$format_times);
					$worksheet->write($rowi+$i,3,$rpdata[$i]['NumoOV'],$format_times);
				}


				$worksheet->setColumn(3,3,21);

				break;

			/*
			** Software Discovered Through Vulnerability Assessments - Excel
			*/
			case "34":

				//$rowi=0;
				$worksheet->write($rowi,0,$report_lang[3][0][4],$format_header);
				$worksheet->mergeCells($rowi,0,$rowi,2);
				$rowi++;

				for ($i = 0; $i < 3; $i++) { $worksheet->write($rowi,$i,$report_lang[3][1][4][0+$i],$format_header); }

				$rowi++;

				for($i=0;$i<count($rpdata);$i++){

					$worksheet->write($rowi+$i,0,$rpdata[$i]['Vendor'],$format_times);
					$worksheet->write($rowi+$i,1,$rpdata[$i]['Product'],$format_times);
					$worksheet->write($rowi+$i,2,$rpdata[$i]['Version'],$format_times);

				}

				$worksheet->setColumn(0,2,12);		
				break;	

			/*
			** Total # of System /w Open Vulnerabilities - Excel
			*/	
			case "35":

				//$rowi=0;
				$worksheet->write($rowi,0,$report_lang[3][1][5][0],$format_header);
				$worksheet->mergeCells($rowi,0,$rowi,1);
				$rowi++;
				$worksheet->write($rowi,0,$rpdata[0],$format_times);
				$worksheet->mergeCells($rowi,0,$rowi,1);
				$rowi++;

				for ($i = 0; $i < 2; $i++) { $worksheet->write($rowi,$i,$report_lang[3][1][5][1+$i],$format_header); }

				$rowi++;
				$total_num=0;

				for($i=0;$i<count($rpdata[1]);$i++){

					$worksheet->write($rowi,0,$rpdata[1][$i]['nick'],$format_times);
					$worksheet->write($rowi,1,$rpdata[1][$i]['num'],$format_times);
					$total_num=$total_num+$rpdata[1][$i]['num'];
					$rowi++;

				}
				//$rowi=$rowi+count($rpdata[1]);
	
				$worksheet->write($rowi,0,$report_lang[3][1][5][3],$format_times);
				$worksheet->write($rowi,1,$total_num,$format_times);
				$worksheet->setColumn(0,1,15);

				break;

		} // switch $t


		$worksheet->setHeader("                            ".$headinfo);
		$workbook->send('report.xls');
		$workbook->close();

		break;

} // switch $f

/*
** Centralize PDF declaration so we can launch different-sized reports for
** different report layout needs.
*/
function open_PDF_doc($report_stats, $report_name) {

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

} // open_PDF_doc()


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
			$report_title,
			array('width'=>$tableWidth*(count($data2[$i])/count($data2[0]))));

	} // for

} // writeNISTBaseLine()

?>
