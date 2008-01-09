<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

if ( function_exists('xdebug_start_trace') ){
    ini_set('xdebug.auto_trace',1);
    ini_set('xdebug.trace_output_dir','C:/');
    xdebug_start_trace();
}

?>
