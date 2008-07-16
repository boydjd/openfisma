<?

// result 	the word "passed" or "failed" depending on whether the whole suite passed or at least one test failed.
// totalTime 	the time in seconds for the whole suite to run
// numTestPasses 	the number of tests that passed
// numTestFailures 	the number of tests that failed.
// numCommandPasses 	the number of commands that passed.
// numCommandFailures 	the number of assertions that failed.
// numCommandErrors 	the number of commands that had an error.
// suite 	the suite table, including the hidden column of test results
// log 	the text of all logs captured in the background. Set the logging level with the setBrowserLogLevel command or with the defaultLogLevel parameter. Any messages printed to the log window will not be posted to the server.
// testTable.1 	the first test table
// testTable.2 	the second test table
// ... 	...
// testTable.N 	The Nth test table

$result = "Result: ".$_POST['result']."\n".
"Selenium Version: ".$_POST['selenium_version']."\n".
"Selenium Revision: ".$_POST['selenium_revision']."\n".
          "Total Time: ".$_POST['totalTime']."\n".
          "Num Test Passes: ".$_POST['numTestPasses']."\n".
          "Num Test Fails: ".$_POST['numTestFailures']."\n".
          "Num Command Passes: ". $_POST['numCommandPasses']."\n".
          "Num Command Fails: ".$_POST['numCommandFailures']."\n".
          "Num Command Errors: ".$_POST['numCommandErrors']."\n".
          "Suite: ".$_POST['suite']."\n".
          "Log: ".$_POST['log']."\n";
$i=1;
while (isset($_POST["testTable_$i"])) {
	$result .= "Test Table $i:\n".$_POST["testTable_$i"]."\n";
$i++;
}

$fp = fopen("/var/www/sites/continuous_integration/continuous_integration/ci.log","a");
//fwrite($fp, print_r($_POST,true));
fwrite($fp, $result);
fclose($fp);
?>
