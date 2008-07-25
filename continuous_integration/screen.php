<?
/**
 * /ci/screen.php
 *
 * Fetches a screenshot of OpenFISMA's headless Selenium test harness and
 * displays it in the browser.
 *
 * This page depends on having ImageMagick installed on the host.
 *
 * @package continuous_integration
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
 
$command = "DISPLAY=:1 import -window root {$_SERVER['DOCUMENT_ROOT']}/ci/screen.png 2>&1";
$result = shell_exec($command);
$command = htmlspecialchars($command);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head><title>Screenshot</title></head>
<body>
  <img border="1" src="/ci/screen.png"><br>
  <?= $command ?><br>
  <strong><?= $result ?>&nbsp;</strong><br>
</body>
</html>
