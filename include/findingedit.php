<?PHP

header("Cache-Control: no-cache, must-revalidate");

include("Smarty.class.php");

$smarty = new Smarty;

$smarty->compile_check = true;
$smarty->debugging = true;


$smarty->assign('now', gmdate ("M d Y H:i:s", time()));
$smarty->display('findingdetail.tpl');

?>
