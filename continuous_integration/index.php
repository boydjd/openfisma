<?
/**
 * /ci/index.php
 *
 * This is the main page for OpenFISMA continuous integration. This page
 * displays information about testing status and revision status.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
 
/* Fetch the revisions table */
$numRevisions = 10; // how many latest revisions to get
$maxRevision = 0;
$db = mysqli_connect('localhost','ci','ci','ci_control');
$query = "SELECT revision_id, author, commit_time, commit_message
          FROM revision
          ORDER BY revision_id DESC
          LIMIT $numRevisions";
$result = mysqli_query($db, $query);
if (!$result) {
  die ("error in query\n$query\n");
}
$revisionsTable = "<table border=1><tr><td><b>Revision #</b></td><td><b>Author</b></td><td><b>Commit Date</b></td><td><b>Message</b></td></tr>\n";
while ($row = mysqli_fetch_assoc($result)) {
  if ($maxRevision == 0)
    $maxRevision = $row['revision_id'];
  $message = str_replace("\n", "<br>", $row['commit_message']);
  $revisionsTable .= "<tr><td>{$row['revision_id']}</td>
                          <td>{$row['author']}</td>
                          <td>{$row['commit_time']}</td>
                          <td>$message</td>
                     </tr>\n";
}
$revisionsTable .= "</table>";

/* Fetch the test status table */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>OpenFISMA Continous Integration</title>
</head>

<body>
<h2>OpenFISMA Continuous Integration</h2>
<ul>
<li>Latest committed revision: <?= $maxRevision ?>
<li>Latest tested revision:
<li>Test status of current build:
<li>Contributors to this build:
<li>View the selenium test harness: <a href="/ci/screen.php">Screenshot</a>
</ul>
The last <?= $numRevisions ?> revisions: <?= $revisionsTable ?>
</body>
</html>