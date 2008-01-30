<?PHP
require_once("config.php");
require_once("piegraph.class.php");
require_once("report_lang.php");

// class call with the width, height & data
@$data=$_REQUEST['data'];
$pie = new PieGraph(100, 100, $data);

// colors for the data
//								        low		moderate  high
$pie->setColors(array("#00ff00","#ffff00","#ff0000"));

// legends for the data
$pie->setLegends(array($report_lang[3][1][2][1],
												$report_lang[3][1][2][2],
												$report_lang[3][1][2][3]));

// Display creation time of the graph
//$pie->DisplayCreationTime();

// Height of the pie 3d effect
$pie->set3dHeight(0);

// Display the graph
$pie->display();
?>