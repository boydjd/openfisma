<?php
$output_cols = array(
"System",
"ID#",
"Description",
"Type",
"Status",
"Source",
"Asset",
"Location",
"Risk Level",
"Recommendation",
"Corrective Action",
"ECD"
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
$worksheet->mergeCells($rowi,0,$rowi,2);
$worksheet->write($rowi++, 0, 'Results',$format_header);
$worksheet->setColumn(0,1,10);
$worksheet->setColumn(2,2,50);
$worksheet->setColumn(3,8,10);
$worksheet->setColumn(9,10,50);
$worksheet->setColumn(11,11,50);
$worksheet->mergeCells($rowi,0,$rowi,13);
//inject the titles
$worksheet->writeRow($rowi++,0,$output_cols,$format_times);

foreach( $this->poam_list as $p ) {
    $data = array(
        $this->system_list[$p['system_id']],
        $p['id'],
        $p['finding_data'],
        $p['type'],
        $p['status'],
        $this->source_list[$p['source_id']],
        $p['asset_id'],
        $this->network_list[$p['network_id']],
        $p['threat_level'],
        $p['action_suggested'],
        $p['action_planned'],
        $p['action_est_date']);
    $worksheet->writeRow($rowi++,0,$data,$format_times); 
}

$worksheet->setHeader("                            ".$headinfo);
$workbook->send('report.xls');
$workbook->close();

?>
