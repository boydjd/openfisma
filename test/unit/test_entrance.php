<?php
require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');

if(!defined('DS') ) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('TEST_ROOT', dirname(__FILE__));
define('ROOT', realpath(TEST_ROOT . DS . '..' . DS . '..') );

require_once(ROOT . DS .'public'.DS.'ovms.ini.php');
$test = new TestSuite('All classes tests');
$test->addTestFile('acl_t.php');
$test->run(new TextReporter());
?>
