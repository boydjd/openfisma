<?PHP
header("Cache-Control: no-cache, must-revalidate");

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("pubfunc.php");
require_once("tbfunc.php");
require_once("tbopt.php");
require_once("roleright.php");

$pageurl = "tbadm.php";
$pagesize = 20;

/**************User Rigth*****************/
require_once("user.class.php");
require_once("page_utils.php");

session_start();
$user = new User($db);
verify_login($user, $smarty);

// table count, table option must be unique with database schema, or will be occur error
$table_count = count($table_arr);

// init data
$totalrecords = 0;
$tid = 0;
$pagemsg = "";

$r_do = "";
$r_id = 0;
$pgno = 1;
$fid = 0;
$qv = "";
$qno = 1;
$querynext = false;

if(isset($_POST["tid"]))
	$tid = intval($_POST["tid"]);
else if(isset($_GET["tid"]))
	$tid = intval($_GET["tid"]);

$tablename = $table_name_arr[$tid - 1];
$tb_class = $table_class_index[$tid - 1][1];
$page_title = $table_class[$tb_class];

// user's right depend on each table
$screen_name = "admin_" . strtolower($table_arr[$tid - 1]);

//print_r($user->getRightFormScreen($screen_name));
// get user right for this screen
// $user->checkRightByFunction($screen_name, "function_name");

//echo $screen_name;
$view_right	= $user->checkRightByFunction($screen_name, "view");
$edit_right = $user->checkRightByFunction($screen_name, "edit");
$add_right  = $user->checkRightByFunction($screen_name, "add");
$del_right  = $user->checkRightByFunction($screen_name, "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/**************User Rigth*****************/



$smarty->assign("title", $page_title);
$smarty->assign("name", $tablename);

$smarty->display('header.tpl');

$page_title = "<b>$page_title:</b>";
$now = get_page_datetime();

$msg = "$page_title $tablename Summary";
echo underline($msg, $now);

if($tid > 0 && $tid <= $table_count)
{
	if(isset($_POST["r_id"]))
		$r_id = intval($_POST["r_id"]);
	else if(isset($_GET["r_id"]))
		$r_id = intval($_GET["r_id"]);

	if(isset($_GET["pgno"]))
		$pgno = intval($_GET["pgno"]);
	else if(isset($_POST["pgno"]))
		$pgno = intval($_POST["pgno"]);

	if(isset($_POST["qno"]))
		$qno = $_POST["qno"];

	if(isset($_POST["r_do"]))
		$r_do = $_POST["r_do"];
	else if(isset($_GET["r_do"]))
		$r_do = $_GET["r_do"];

	if(isset($_POST["fid"]))
		$fid = intval($_POST["fid"]);
	else if(isset($_GET["fid"]))
		$fid = intval($_GET["fid"]);

	if(isset($_POST["qv"]))
		$qv = $_POST["qv"];
	else if(isset($_GET["qv"]))
		$qv = $_GET["qv"];

	$of = 0;
	$asc = 0;

	if(isset($_POST["of"]))
		$of = intval($_POST["of"]);
	else if(isset($_GET["of"]))
		$of = intval($_GET["of"]);

	if(isset($_POST["asc"]))
		$asc = intval($_POST["asc"]);
	else if(isset($_GET["asc"]))
		$asc = intval($_GET["asc"]);

	Script();

	// do page
	//$dblink = connectdb();

	if($view_right) {
		if($r_do == "query")
		{
			$pagemsg = DoQuery($tid, $fid, $qv, $qno, $edit_right, $view_right, $del_right);
		}

		$toolbars = PageScroll($tid, $pgno, $of, $asc);
		$tbcnname = $table_name_arr[$tid - 1];

		echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"tbframe\">\n";
		echo "<tr>\n";
		echo "<th>[<a href=\"$pageurl?tid=$tid\">$tbcnname list</a>] (total: $totalrecords)</td>\n";
		echo "<th>";
		if($add_right)
			echo "[<a href=\"$pageurl?tid=$tid&r_do=form\" title=\"add new $tbcnname\">Add $tbcnname</a>]";
		echo "</td>\n";
		echo "<th>$toolbars</td>\n";
		echo "<th>";
		//StatForm($tid, $fid);
		//echo "</td>\n";
		//echo "</tr>\n";

		//echo "<tr>\n";
		//echo "<th colspan=\"4\">";
		QueryForm($tid, $fid, $qv, $qno, $querynext);
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}
	echo "<br>";

	if($r_do == "view" && $r_id > 0)
	{
		if($view_right) {
			DisplayItem($tid, $r_id, $pgno, $of, $asc);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else if($r_do == "edit" && $r_id > 0)
	{
		if($edit_right) {
			UpdateRecord($tid, $r_id);
			echo "<p>$tbcnname <b>modified</b> successful!</p>";

			$pagemsg = ListRecord($tid, $pgno, $of, $asc, $edit_right, $view_right, $del_right);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else if($r_do == "add")
	{
		if($add_right) {
			AddRecord($tid);
			echo "<p>$tbcnname <b>added</b> successfully.</p>";

			// continue to add new entry
			EditForm($tid);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else if($r_do == "dele")
	{
		if($del_right) {
			DeleteRecord($tid, $r_id);
			echo "<p><b>$tbcnname Deleted successfully</b></p>";

			$pagemsg = ListRecord($tid, $pgno, $of, $asc, $edit_right, $view_right, $del_right);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else if($r_do == "stat")
	{
		if($view_right) {
			$pagemsg = DoStat($tid, $fid);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else if($r_do == "form")
	{
		if($edit_right || $add_right) {
			EditForm($tid, $r_id, $pgno, $of, $asc);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else if($r_do == "query") {
	}
	else if($r_do == "rrform" & $r_id > 0) {
		echo underline("$page_title Role Right Config");
		if($edit_right) {
			echo RoleFunctionDefineForm($tid, $pgno, $of, $asc, $r_id, $edit_right);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else if($r_do == "rright" && $r_id > 0) {
		echo underline("$page_title Role Right Config");
		if($edit_right) {
			echo RoleFunctionDefine($r_id, $_POST);
			echo RoleFunctionDefineForm($tid, $pgno, $of, $asc, $r_id, false);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}
	else
	{
		if($view_right) {
			$pagemsg = ListRecord($tid, $pgno, $of, $asc, $edit_right, $view_right, $del_right);
		}
		else {
			$pagemsg = "<p><b>Insufficient permissions</b> to perform the request.</p>";
		}
	}

	//mysql_close($dblink);
	$db->sql_close();
}
else
	$pagemsg = "<p>Please select a valid administration menu.</p>";


// Display result page
echo $pagemsg;

echo "<br>";

$smarty->display('footer.tpl');

?>

