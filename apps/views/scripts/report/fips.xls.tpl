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

$worksheet->write($rowi,0,'FIPS 199 Category',$format_header);
$worksheet->write($rowi,1,'Low',$format_header);
$worksheet->write($rowi,2,'Moderate',$format_header);
$worksheet->write($rowi,3,'High',$format_header);
$rowi++;

// Totals table single row
$worksheet->write($rowi,0,'Total Systems',$format_header);
$worksheet->write($rowi,1,$this->rpdata[1]['LOW'],$format_times);
$worksheet->write($rowi,2,$this->rpdata[1]['MODERATE'],$format_times);
$worksheet->write($rowi,3,$this->rpdata[1]['HIGH'],$format_times);
$rowi++;

// Insert a space between tables
$worksheet->write($rowi,0,' ',$format_times);
$rowi++;

// System detail table
// Column headers from report_lang.

// Detail header row
$worksheet->write($rowi,0,'System Name',$format_header);
$worksheet->write($rowi,1,'System Type',$format_header);
$worksheet->write($rowi,2,'Mission Criticality',$format_header);
$worksheet->write($rowi,3,'FIPS 199 Category',$format_header);
$worksheet->write($rowi,4,'Confidentiality',$format_header);
$worksheet->write($rowi,5,'Integrity',$format_header);
$worksheet->write($rowi,6,'Availability',$format_header);
$worksheet->write($rowi,7,'Last Inventory Update',$format_header);

$rowi++;
foreach ($this->rpdata[0] as $row) {
    $worksheet->write($rowi,0,$row['name'],$format_times);
    $worksheet->write($rowi,1,$row['type'],$format_times);
    $worksheet->write($rowi,2,$row['crit'],$format_times);
    $worksheet->write($rowi,3,$row['fips'],$format_times);
    $worksheet->write($rowi,4,$row['conf'],$format_times);
    $worksheet->write($rowi,5,$row['integ'],$format_times);
    $worksheet->write($rowi,6,$row['avail'],$format_times);
    $worksheet->write($rowi,7,$row['last_update'],$format_times);
    $rowi++;
}
$worksheet->setColumn(0,0,50);
$worksheet->setColumn(1,1,20);
$worksheet->setColumn(2,2,15);
$worksheet->setColumn(3,3,12);
$worksheet->setColumn(4,4,12);
$worksheet->setColumn(5,5,12);
$worksheet->setColumn(6,6,12);
$worksheet->setColumn(7,7,20);

$worksheet->setHeader("                            ".$headinfo);
$workbook->send('general_report.xls');
$workbook->close();

?>
