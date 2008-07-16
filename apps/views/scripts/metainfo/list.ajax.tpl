<?php
foreach($this->list as $k=>$s){
    if( $s == $this->selected ) {
        $select = 'selected="selected"';
    }else{
        $select = '';
    }
    echo "<option value=\"$k\" {$select}>$s</option>\n";
}
