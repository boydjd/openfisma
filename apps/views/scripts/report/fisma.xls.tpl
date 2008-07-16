<?php
require_once ('Spreadsheet/Excel/Writer.php'); 
$workbook  = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8); 
$worksheet =& $workbook->addWorksheet();
$format_header =& $workbook->addFormat(array('Size' => 10,
                                             'Align' => 'center',
                                             'Color' => 'white',
                                             'FgColor' => 'black'));

$format_header->setFontFamily('Times New Roman');
$format_times =& $workbook->addFormat(array('Size' => 10,
    'Align' => 'center',
    'Color' => 'black',
    'BorderColor '=> 'blue',
    'Bottom'=>1,'Top'=>1,'Left'=>1,'Right'=>1
    ));

$format_times->setFontFamily('Times New Roman');

$rowi=0;
$headinfo="Report run time: ".date("Y-m-d H:i:s");
$worksheet->mergeCells($rowi,0,$rowi,2);
$worksheet->write($rowi++, 0, $headinfo,$format_times);

$worksheet->mergeCells($rowi,0,$rowi,4);
$worksheet->write($rowi++, 0, "FISMA Report to OMB: POA&M Status Repor",$format_header);

$worksheet->write($rowi, 0,"POA&M Status Information" , $format_times);
$worksheet->write($rowi, 1,"Agency Wide", $format_times);
$worksheet->write($rowi, 2,"System" , $format_times);
$worksheet->write($rowi, 3,"Total", $format_times);
$worksheet->write($rowi++, 4,"Brief Explanatio", $format_times);

$worksheet->write($rowi, 0,"A. Total number of weaknesses identified at the start of the reporting perio" , $format_times);
$worksheet->write($rowi, 1,$this->summary['AAW'], $format_times);
$worksheet->write($rowi, 2,$this->summary['AS'] , $format_times);
$worksheet->write($rowi, 3,$this->summary['AAW']+$this->summary['AS'], $format_times);
$worksheet->write($rowi++, 4, "", $format_times);

$worksheet->write($rowi, 0,"B. Number of weaknesses for which corrective action was completed on time(including testing) by the end of the reporting period" , $format_times);
$worksheet->write($rowi, 1,$this->summary['BAW'], $format_times);
$worksheet->write($rowi, 2,$this->summary['BS'], $format_times);
$worksheet->write($rowi, 3,$this->summary['BAW']+$this->summary['BS'], $format_times);
$worksheet->write($rowi++, 4, "", $format_times);

$worksheet->write($rowi, 0, "C. Number of weaknesses for which corrective action is ongoing and is on track to complete as originally scheduled", $format_times);
$worksheet->write($rowi, 1,$this->summary['CAW'] , $format_times);
$worksheet->write($rowi, 2,$this->summary['CS'] , $format_times);
$worksheet->write($rowi, 3,$this->summary['CAW']+$this->summary['CS'] , $format_times);
$worksheet->write($rowi++, 4, "", $format_times);

$worksheet->write($rowi, 0, "D. Number of weaknesses for which corrective action has been delayed including a brief explanation for the delay", $format_times);
$worksheet->write($rowi, 1, $this->summary['DAW'], $format_times);
$worksheet->write($rowi, 2,$this->summary['DS'] , $format_times);
$worksheet->write($rowi, 3,$this->summary['DAW']+$this->summary['DS'], $format_times);
$worksheet->write($rowi++, 4, "" , $format_times);

$worksheet->write($rowi, 0,"E. Number of weaknesses discovered following the last POA&M update and a brief Explanation of how they were identified (e.g., agency review, IG evaluation, etc.)", $format_times);
$worksheet->write($rowi, 1,$this->summary['EAW'], $format_times);
$worksheet->write($rowi, 2,$this->summary['ES'], $format_times);
$worksheet->write($rowi, 3,$this->summary['EAW']+$this->summary['ES'], $format_times);
$worksheet->write($rowi++, 4, "" , $format_times);

$worksheet->write($rowi, 0, "Total number of weaknesses remaining at the end of the reporting period", $format_times);
$worksheet->write($rowi, 1,$this->summary['FAW'] , $format_times);
$worksheet->write($rowi, 2,$this->summary['FS'], $format_times);
$worksheet->write($rowi, 3,$this->summary['FAW']+$this->summary['FS'], $format_times);
$worksheet->write($rowi++, 4, "" , $format_times);

$worksheet->setColumn(0,0,55);
$worksheet->setColumn(0,1,12);
$worksheet->setColumn(0,2,7);
$worksheet->setColumn(0,3,5);
$worksheet->setColumn(0,4,15);

$worksheet->setHeader("                            ".$headinfo);
$workbook->send('fisma_report.xls');
$workbook->close();

?>
