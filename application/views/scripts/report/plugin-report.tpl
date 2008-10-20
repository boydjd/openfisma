<table class="pluginReport">
    <tr>
<?php  
    foreach ($this->columns as $column) {
        print "<td><b>$column</b></td>";
    }
?>
    </tr>
<?php
    foreach ($this->rows as $row) {
        print '<tr>';
        foreach ($row as $cell) {
            print "<td>$cell</td>";
        }
        print '</tr>';
    }
?>
</table>