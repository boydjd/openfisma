<?php
$cols = array (
    'system_name'=>'System',
    'id'=>"ID#",
    'finding_data'=>"Description",
    'type'=>"Type",
    'status'=>"Status",
    'source_name'=>"Source",
//    'asset_id'=>"Asset",
    'network_name'=>"Location",
    'threat_level'=>"Risk Level",
    'action_suggested'=>"Recommendation",
    'action_planned'=>"Corrective Action",
    'action_est_date' => "ECD",
    'threat_source' => "Threat source",
    'cmeasure' => "Countermeasure",
    'blscr_id' => "Control",
    'threats' => "Threats",
    'cmeasure_effectiveness' => "Countermeasure");

foreach($this->poam_list as &$row)
{
    $row['system_name']=empty($row['system_id'])? 'N/A':$this->system_list[$row['system_id']];
    $row['source_name']=empty($row['source_id'])? 'N/A':$this->source_list[$row['source_id']];
    $row['network_name']=empty($row['network_id'])? 'N/A':$this->network_list[$row['network_id']];
    $row['blscr_id']= NULL == $row['blscr_id']? 'N':'Y';
    $row['threats']= 'NONE' == $row['threat_level']? 'N':'Y';
    $row['cmeasure_effectiveness']= 'NONE' == $row['cmeasure_effectiveness']? 'N':'Y';
}

define('REPORT_FOOTER_WARNING', "WARNING: This report is for internal, official use only.  This report contains sensitive computer security related information. Public disclosure of this information would risk circumvention of the law. Recipients of this report must not, under any circumstances, show or release its contents for purposes other than official action. This report must be safeguarded to prevent improper disclosure. Staff reviewing this document must hold a minimum of Public Trust Level 5C clearance.");

define('ORIENTATION', 'orient');
define('PAPERTYPE', 'pagesz');
define('PGWIDTH', 'pgwidth');
define('PGHEIGHT', 'pgheight');
define('FONTS', VENDORS . DS . 'pdf' . DS . 'fonts');

$poam_config = array( ORIENTATION => 'landscape', PAPERTYPE => 'LETTER', PGWIDTH => 792);

require_once( VENDORS .DS. 'pdf' . DS . 'class.ezpdf.php');

$pdf =& new Cezpdf($poam_config[PAPERTYPE], $poam_config[ORIENTATION]);
$pdf->selectFont(FONTS . DS . "Helvetica.afm");//needs modify to the real font file path
$horiz_margin = 50;
$bottom_margin = 100;
$top_margin = 50;
$page_width = $poam_config[PGWIDTH];
$warning_size = 8;

$left_top = array('x'=>$horiz_margin,'y'=>'585');
$right_bottom = array('x'=>$page_width-$horiz_margin,'y'=>$bottom_margin);
$content_width = $right_bottom['x'] - $left_top['x'];

$all = $pdf->openObject();
$pdf->saveState();

$head_height = 5;

$pdf->addTextWrap($left_top['x'],$left_top['y'],$content_width,8,'Report run time:'.
                                Zend_Date::now()->toString('Y-m-d H:i:s'),
                                'right');
$tmp_y = $left_top['y'] - $head_height;
$pdf->line($left_top['x'],$tmp_y,$right_bottom['x'],$tmp_y);
$pdf->setStrokeColor(0,0,0,1);

$y = $right_bottom['y'];
$pdf->line($left_top['x'],$y,$right_bottom['x'],$y);

// Add footer
$text = REPORT_FOOTER_WARNING;
$x = $left_top['x'];
$line_height = 8;
$y -= $line_height;
while(!empty($text)){
    $y -= $line_height;
    $text = $pdf->addTextWrap($x,$y,$content_width,$line_height,$text,'left');  
}

$pdf->restoreState();
$pdf->closeObject();

$pdf->addObject($all,'all');
$pdf->ezSetMargins($top_margin,$bottom_margin,$horiz_margin,$horiz_margin);

//Add title
$title = '[System] : ';
if( isset($this->criteria['system_id']) ) {
    $title .= $this->system_list[$this->criteria['system_id']];
}else{
    $title .= 'All systems';
}

if( isset($this->criteria['source_id']) ) {
    $title .= " [Source] : {$this->source_list[$this->criteria['source_id']]}";
}

$pdf->ezText('Plans of Actions And Milestones Report Administration',16,array('justification'=>'center'));
$pdf->ezTable($this->poam_list,$cols,$title,
    array('fontSize'=>8,'maxWidth'=>690, 'titleFontSize'=>'12' ));

header('Pragma: private');
header('Cache-control: private');
echo $pdf->ezOutput();
?>
