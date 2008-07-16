<?php
require_once ('Spreadsheet/Excel/Writer.php'); 
$headinfo="Report run time: ".date("Y-m-d H:i:s");
$workbook  = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8); 
$worksheet =& $workbook->addWorksheet();
$format_header =& $workbook->addFormat(array('Size' => 10,
    'Align' => 'center',
    'Color' => 'white',
    'FgColor' => 'black',
    ));
$format_times =& $workbook->addFormat(array('Size' => 10,
    'Align' => 'center',
    'Color' => 'black',
    'BorderColor '=> 'blue',
    'Bottom'=>1,'Top'=>1,'Left'=>1,'Right'=>1
    ));
$format_header->setFontFamily('Times New Roman');
$format_times->setFontFamily('Times New Roman');
$rowi=0;
$worksheet->write($rowi, 0, $headinfo,$format_times);
$worksheet->mergeCells($rowi,0,$rowi,2);
$rowi++;

$worksheet->write($rowi,0,'Software Discovered Through Vulnerability Assessments',$format_header);
$worksheet->mergeCells($rowi,0,$rowi,2);
$rowi++;

$worksheet->write($rowi,0,'Vendor',$format_header);
$worksheet->write($rowi,1,'Product',$format_header);
$worksheet->write($rowi,2,'Version',$format_header);
$rowi++;

for($i=0;$i<count($this->rpdata);$i++){
    $worksheet->write($rowi+$i,0,$this->rpdata[$i]['Vendor'],$format_times);
    $worksheet->write($rowi+$i,1,$this->rpdata[$i]['Product'],$format_times);
    $worksheet->write($rowi+$i,2,$this->rpdata[$i]['Version'],$format_times);
}

$worksheet->setColumn(0,2,12);

$worksheet->setHeader("                            ".$headinfo);
$workbook->send('general_report.xls');
$workbook->close();

?>
