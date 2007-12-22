<?php
/** This is an autoloading mechanism. 
 *  All the classes in a single file should follow the naming convention
 */
function __autoload($class_name) {
    if( @!include_once(dirname(__FILE__) . "/class/$class_name.class.php") ){
        eval('class '.$class_name.' extends Exception {}');
        throw new $class_name("[autoloading]:Class $class_name is not defined");
    }
}

?>
