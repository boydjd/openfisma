<?PHP

require_once("ovms.ini.php");

// required for all pages, sets smarty directory locations for cache, templates, etc.
require_once("smarty.inc.php");
require_once('Zend/Registry.php');
require_once("roles_ini.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");

// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

$customer_logo = $CUSTOMER_LOGO;
$login_warning = $LOGIN_WARNING;

date_default_timezone_set('America/New_York');
$current_time_string = date('Y-m-d H:i:s');
$current_time_stamp = time();

// uncomment this line to start smarty debugging
// $smarty->debugging = TRUE;

function displayLoginInfor($smarty, $user) {
	global $customer_logo;

	$screen_name = "header";
	if(isset($smarty)) {
		if(isset($user)) {

            $dashboard_menu = $user->checkRightByFunction("dashboard", "read");

            $finding_menu = $user->checkRightByFunction("finding", "read");
            $finding_add = $user->checkRightByFunction("finding", "create");
            $finding_upload = $user->checkRightByFunction("finding", "create");

            $asset_menu = $user->checkRightByFunction("asset", "read");
            $asset_summary = $user->checkRightByFunction("asset", "read");
            $asset_new = $user->checkRightByFunction("asset", "create");

            $remediation_menu = $user->checkRightByFunction("remediation", "read");

            $report_menu = $user->checkRightByFunction("report", "read");

            $admin_menu = $user->checkRightByFunction($screen_name, "admin_menu");

            $vulner_menu = $user->checkRightByFunction("vulnerability", "read");

            $report_poam_generate = $user->checkRightByFunction("report", "generate_poam_report");
            $report_fisma_generate = $user->checkRightByFunction("report", "generate_fisma_report");
            $report_general_generate = $user->checkRightByFunction("report", "generate_general_report");
            $report_system_generate = $user->checkRightByFunction("report", "generate_system_rafs");
            $report_overdue= $user->checkRightByFunction("report", "generate_overdue_report");

            $admin_user_view = $user->checkRightByFunction("admin_users", "read");
            $admin_role_view = $user->checkRightByFunction("admin_roles", "read");
            $admin_system_view = $user->checkRightByFunction("admin_systems", "read");
            $admin_products_view = $user->checkRightByFunction("admin_products", "read");
            $admin_group_view = $user->checkRightByFunction("admin_system_groups", "read");
            $admin_function_view = $user->checkRightByFunction("admin_functions", "read");

            $vulner_summary = $user->checkRightByFunction("vulnerability", "read");
            $vulner_add = $user->checkRightByFunction("vulnerability", "create");

			$smarty->assign('dashboard_menu', $dashboard_menu);
			$smarty->assign('finding_menu', $finding_menu);
			$smarty->assign('asset_menu', $asset_menu);

			$smarty->assign('remediation_menu', $remediation_menu);
			$smarty->assign('report_menu', $report_menu);
			$smarty->assign('admin_menu', $admin_menu);
			$smarty->assign('vulner_menu', $vulner_menu);

			$smarty->assign('finding_add', $finding_add);
			$smarty->assign('finding_upload', $finding_upload);

			$smarty->assign('asset_summary', $asset_summary);
			$smarty->assign('asset_new', $asset_new);

			$smarty->assign('report_poam_generate', $report_poam_generate);
			$smarty->assign('report_fisma_generate', $report_fisma_generate);
			$smarty->assign('report_general_generate', $report_general_generate);
            $smarty->assign('report_system_generate', $report_system_generate);
            $smarty->assign('report_overdue', $report_overdue);

			$smarty->assign('admin_user_view', $admin_user_view);
			$smarty->assign('admin_role_view', $admin_role_view);
			$smarty->assign('admin_system_view', $admin_system_view);
			$smarty->assign('admin_products_view', $admin_products_view);
			$smarty->assign('admin_group_view', $admin_group_view);
			$smarty->assign('admin_function_view', $admin_function_view);

			$smarty->assign('vulner_summary', $vulner_summary);
			$smarty->assign('vulner_add', $vulner_add);

			if(empty($user->user_name_first) && empty($user->user_name_last)) {
				$smarty->assign("firstname", $user->getUsername());
			}
			else {
				$smarty->assign("firstname", $user->user_name_first);
				$smarty->assign("lastname", $user->user_name_last);
			}
		}

		$smarty->assign("customer_logo", $customer_logo);
	}
}

Zend_Registry::set('acl', acl_initialize());

?>
