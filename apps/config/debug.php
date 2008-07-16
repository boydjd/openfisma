<?php
/**
 * @fileName:debug.php
 *
 * @description Debug Configuration Info
 *
 * @author     Jim <jimc@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('xdebug.collect_params' , 0);
ini_set('xdebug.collect_return' , 1);
ini_set('xdebug.trace_options', 1);
ini_set('xdebug.trace_output_name', 'sws.%u');
ini_set('xdebug.auto_trace', 1);
if(function_exists('xdebug_start_trace') ) {
    //xdebug_start_trace();
}

?>
