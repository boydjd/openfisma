<select name="asset_list" size="8" style="width: 190px;">
<?php 
    foreach( $this->assets as $a ) {
        echo '<option value="', $a['id'], '">', $a['name'], '</option>',"\n"; 
    } 
?>
</select>
