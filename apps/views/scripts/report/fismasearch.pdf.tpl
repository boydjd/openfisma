<?php
require_once( 'pdf/class.ezpdf.php');

$fields=array('status'=>'POA&M Status Information','Agency Wide','System','Total','Brief Explanation');
$table=array(
array('status'=>'A. Total number of weaknesses identified at the start of the reporting period',$this->AAW ,$this->AS,$this->AAW+$this->AS,''),
array('status'=>'B. Number of weaknesses for which corrective action was completed on time(including testing) by the end of the reporting period',$this->BAW ,$this->BS,$this->BAW+$this->BS,''),
array('status'=>'C. Number of weaknesses for which corrective action is ongoing and is on track to complete as originally scheduled ',$this->CAW ,$this->CS,$this->CAW+$this->CS,''),
array('status'=>'D. Number of weaknesses for which corrective action has been delayed including a brief explanation for the delay ',$this->DAW ,$this->DS,$this->DAW+$this->DS,''),
array('status'=>'E. Number of weaknesses discovered following the last POA&M update and a brief Explanation of how they were identified (e.g., agency review, IG evaluation, etc.)',$this->EAW ,$this->ES,$this->EAW+$this->ES,''),
array('status'=>'Total number of weaknesses remaining at the end of the reporting period ',$this->FAW ,$this->FS,$this->FAW+$this->FS,'')
);

define('REPORT_FOOTER_WARNING', "WARNING: This report is for internal, official use only.  This report contains sensitive computer security related information. Public disclosure of this information would risk circumvention of the law. Recipients of this report must not, under any circumstances, show or release its contents for purposes other than official action. This report must be safeguarded to prevent improper disclosure. Staff reviewing this document must hold a minimum of Public Trust Level 5C clearance.");

define('ORIENTATION', 'orient');
define('PAPERTYPE', 'pagesz');
define('PGWIDTH', 'pgwidth');
define('PGHEIGHT', 'pgheight');
define('FONTS', LIBS . '/pdf/fonts');

$page_config = array( ORIENTATION => 'landscape', PAPERTYPE => 'LETTER', PGWIDTH => 792, PGHEIGHT=>612);
$pdf =& new Cezpdf($page_config[PAPERTYPE], $page_config[ORIENTATION]);
$pdf->selectFont(FONTS . "/Helvetica.afm");
$top_margin=50;
$bottom_margin=100;
$left_margin=50;
$right_margin=50;

$page_config[PGWIDTH];
$page_config[PGHEIGHT];
$content_width=$page_config[PGWIDTH]-$left_margin-$right_margin;
$content_height=$page_config[PGHEIGHT]-$top_margin-$bottom_margin=100;

$pdf->ezSetMargins($top_margin, $bottom_margin, $left_margin, $right_margin);

$pdf->addTextWrap($left_margin,$content_height+110,$content_width,8,'Report run time:'.Zend_Date::now()->toString('Y-m-d H:i:s'),'right');
$pdf->line($left_margin,$content_height+105,$left_margin+$content_width,$content_height+105);
$pdf->addTextWrap($left_margin,$bottom_margin,$content_width,8,'Report run time:'.Zend_Date::now()->toString('Y-m-d H:i:s'),'left');

$pdf->ezText('FISMA Report to OMB: POA&M Status Report',16,array('justification'=>'centre'));
$title='';
if($this->system)
{
    $title.=' [System]: '.$this->system;
}else {
        $title.=' [System]: All';
}
if($this->startdate)
{
    $title.=' [Start date]: '.$this->startdate;
}
if($this->enddate)
{
    $title.=' [End date]: '.$this->enddate;
}

$pdf->ezTable($table, $fields,$title,
    array('fontSize'=>8,'maxWidth'=>$content_width,'titleFontSize'=>12));

header('Pragma: private');
header('Cache-control: private');
echo $pdf->ezOutput();
?>
