<?php
$output_cols = array(
"Asset Name",
"System",
"IP Address",
"Port",
"Product Name",
"Vendor"
);
$count_cols = count($output_cols);

require_once ('Spreadsheet/Excel/Writer.php'); // need fix 

$workbook  = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8); // fixes 255 char truncation issue

$worksheet =& $workbook->addWorksheet();

$format_header =& $workbook->addFormat(array('Size' => 10,
    'Align' => 'center',
    'Color' => 'white',
    'FgColor' => 'black',
    ));
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
$worksheet->write($rowi++, 0, $headinfo,$format_times);
$worksheet->setColumn(0,0,20);
$worksheet->setColumn(0,1,35);
$worksheet->setColumn(1,2,15);
$worksheet->mergeCells(0,0,0,5);

$worksheet->writeRow($rowi++,0,$output_cols,$format_times);

foreach( $this->asset_list as $row ) {
    $data = array(
        $row['asset_name'],
        $row['system_name'],
        $row['address_ip'],
        $row['address_port'],
        $row['prod_name'],
        $row['prod_vendor']);
    $worksheet->writeRow($rowi++,0,$data,$format_times); 
}

$worksheet->setHeader("                            ".$headinfo);
$workbook->send('assets.xls');
$workbook->close();

?>
