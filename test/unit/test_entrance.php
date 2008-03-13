<?php
require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');

$test = new TestSuite('All classes tests');
$test->addTestFile('acl_t.php');
$test->run(new TextReporter());
?>
