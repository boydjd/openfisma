<?php

require_once( VENDORS .DS. 'pdf' . DS . 'class.ezpdf.php');

$fields=array('Vendor'=>'Vendor','Product'=>'Product','Version'=>'Version');
$table=$this->rpdata;


define('REPORT_FOOTER_WARNING', "WARNING: This report is for internal, official use only.  This report contains sensitive computer security related information. Public disclosure of this information would risk circumvention of the law. Recipients of this report must not, under any circumstances, show or release its contents for purposes other than official action. This report must be safeguarded to prevent improper disclosure. Staff reviewing this document must hold a minimum of Public Trust Level 5C clearance.");

define('ORIENTATION', 'orient');
define('PAPERTYPE', 'pagesz');
define('PGWIDTH', 'pgwidth');
define('PGHEIGHT', 'pgheight');
define('FONTS', VENDORS . DS . 'pdf' . DS . 'fonts');

$page_config = array( ORIENTATION => 'landscape', PAPERTYPE => 'LETTER', PGWIDTH => 792, PGHEIGHT=>612);
$pdf =& new Cezpdf($page_config[PAPERTYPE], $page_config[ORIENTATION]);
$pdf->selectFont(FONTS . DS . "Helvetica.afm");
$top_margin=50;
$bottom_margin=100;
$left_margin=50;
$right_margin=50;

$page_config[PGWIDTH];
$page_config[PGHEIGHT];
$content_width=$page_config[PGWIDTH]-$left_margin-$right_margin;
$content_height=$page_config[PGHEIGHT]-$top_margin-$bottom_margin;

$pdf->ezSetMargins($top_margin, $bottom_margin, $left_margin, $right_margin);

//$all = $pdf->openObject();
//$pdf->saveState();

$pdf->addTextWrap($left_margin,$content_height+110,$content_width,8,'Report run time:'.Zend_Date::now()->toString('Y-m-d H:i:s'),'right');
$pdf->line($left_margin,$content_height+105,$left_margin+$content_width,$content_height+105);

$footer=REPORT_FOOTER_WARNING;
$line_height=8;
$yPos=$bottom_margin-$line_height;
while(!empty($footer)){
$yPos-=$line_height;
$footer=$pdf->addTextWrap($left_margin,$yPos,$content_width,$line_height,$footer,'left');
}

//$pdf->restoreState();
//$pdf->closeObject();

//$pdf->addObject($all,'all');

$title='Software Discovered Through Vulnerability Assessments';

$pdf->ezTable($table, $fields,$title,
    array('fontSize'=>8,'width'=>$content_width,'titleFontSize'=>16));

echo $pdf->ezOutput();
?>