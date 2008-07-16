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

$worksheet->write($rowi,0,'Total # Systems With Open Vulnerabilities',$format_header);
$worksheet->mergeCells($rowi,0,$rowi,1);

$rowi++;
$worksheet->write($rowi,0,$this->rpdata[0],$format_times);
$worksheet->mergeCells($rowi,0,$rowi,1);
$rowi++;

$worksheet->write($rowi,0,'Systems',$format_header);
$worksheet->write($rowi,1,'Open Vulnerabilities',$format_header);
$rowi++;
$total_num=0;

for($i=0;$i<count($this->rpdata[1]);$i++){
    $worksheet->write($rowi,0,$this->rpdata[1][$i]['nick'],$format_times);
    $worksheet->write($rowi,1,$this->rpdata[1][$i]['num'],$format_times);
    $total_num=$total_num+$this->rpdata[1][$i]['num'];
    $rowi++;
}

$worksheet->write($rowi,0,'Total',$format_times);
$worksheet->write($rowi,1,$total_num,$format_times);
$worksheet->setColumn(0,1,15);

$worksheet->setHeader("                            ".$headinfo);
$workbook->send('general_report.xls');
$workbook->close();

?>
