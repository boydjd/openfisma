<?PHP
/**
 * For include and init a Smarty object, set some paras.
 */
define("SMARTY_LIB_DIR", VENDER_TOOL_PATH._S."smarty");
define("SMARTY_ROOT_PATH", OVMS_ROOT_PATH._S."smarty");


$SMARTY_EXISTS = is_file(SMARTY_LIB_DIR._S.'Smarty.class.php');

if (!$SMARTY_EXISTS) {
	die('Smarty class not exists.');
}

define("SMARTY_TEMPLATE_DIR", SMARTY_ROOT_PATH._S.'templates');
define("SMARTY_CACHE_DIR", SMARTY_TEMPLATE_DIR._S.'cache');
define("SMARTY_CONFIGS_DIR", SMARTY_TEMPLATE_DIR._S.'configs');
define("SMARTY_COMPILE_DIR", SMARTY_ROOT_PATH._S.'templates_c');
define("SMARTY_DEBUGGING",'0');

require_once(SMARTY_LIB_DIR._S."Smarty.class.php");
$smarty = new Smarty;
$smarty->template_dir = SMARTY_TEMPLATE_DIR;
$smarty->compile_dir = SMARTY_COMPILE_DIR;
$smarty->debugging = SMARTY_DEBUGGING;
?>