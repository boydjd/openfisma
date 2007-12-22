<?php
$contentadd = file_get_contents('INSTALL.txt');
$outcome = nl2br($contentadd);
$content .=
"<p>".$outcome."
</p>
"
?>
