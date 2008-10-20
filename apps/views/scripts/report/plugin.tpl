<table>
    <tr>
        <td><b>Reports</b></td>
    </tr>
<?php
foreach ($this->reports as $reportName => $reportParams) {
    print "<tr><td><a href=\"/panel/report/sub/pluginReport/name/{$reportName}\">{$reportParams['title']}</a></td></tr>";
}
?>
</table>