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

$worksheet->write($rowi,0,'Management',$format_header);
$worksheet->mergeCells($rowi,0,$rowi,1);
$worksheet->write($rowi,3,'Operational',$format_header);
$worksheet->mergeCells($rowi,3,$rowi,4);
$worksheet->write($rowi,6,'Technical',$format_header);
$worksheet->mergeCells($rowi,6,$rowi,7);

$rowi++;
$worksheet->write($rowi,0,'BLSR Category',$format_times);
$worksheet->write($rowi,1,'Total Vulnerabilities',$format_times);
$worksheet->write($rowi,3,'BLSR Category',$format_times);
$worksheet->write($rowi,4,'Total Vulnerabilities',$format_times);
$worksheet->write($rowi,6,'BLSR Category',$format_times);
$worksheet->write($rowi,7,'Total Vulnerabilities',$format_times);
$rowi++;
$rowi_sta = $rowi;
$total_num=0;

foreach ($this->rpdata[0] as $row) {
    $worksheet->write($rowi,0,$row['blscr'],$format_times);
    $worksheet->write($rowi,1,$row['num'],$format_times);
    $total_num=$total_num+$row['num'];
    $rowi++;
}

$worksheet->write($rowi,0,'Total',$format_times);
$worksheet->write($rowi,1,$total_num,$format_times);
$rowi=$rowi_sta;
$total_num=0;

foreach ($this->rpdata[1] as $row) {
    $worksheet->write($rowi,3,$row['blscr'],$format_times);
    $worksheet->write($rowi,4,$row['num'],$format_times);
    $total_num=$total_num+$row['num'];
    $rowi++;
}

$worksheet->write($rowi,3,'Total',$format_times);
$worksheet->write($rowi,4,$total_num,$format_times);
$rowi=$rowi_sta;
$total_num=0;

foreach ($this->rpdata[2] as $row) {
    $worksheet->write($rowi,6,$row['blscr'],$format_times);
    $worksheet->write($rowi,7,$row['num'],$format_times);
    $total_num=$total_num+$row['num'];
    $rowi++;
}

$worksheet->write($rowi,6,'Total',$format_times);
$worksheet->write($rowi,7,$total_num,$format_times);

$worksheet->setColumn(0,0,13);
$worksheet->setColumn(0,1,17);
$worksheet->setColumn(1,2,2);
$worksheet->setColumn(1,3,13);
$worksheet->setColumn(1,4,17);
$worksheet->setColumn(1,5,2);
$worksheet->setColumn(1,6,13);
$worksheet->setColumn(1,7,17);

$worksheet->setHeader("                            ".$headinfo);
$workbook->send('general_report.xls');
$workbook->close();

?>
