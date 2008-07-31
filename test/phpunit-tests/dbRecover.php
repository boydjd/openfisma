<?php
/**
 * dbRecover.php
 * recover fisma's database;
 * @package Test
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * $Id$
*/

define('SCHEMA_DATABASE','schema.sql');


function recoverDB($user,$passwd,$host,$database,$path){
    $dbconn = mysql_select_db($database);
    if(!$dbconn) die("database connect error!");
    $files=scandir($path);
    if(!$files) die("path error!");
        $sql_file = preg_grep("/[a-zA-Z0-9]*\.sql$/i",$files);
        echo "Now start importing <<< <br/>";
        foreach($sql_file as $elem){
        if($elem==SCHEMA_DATABASE) continue;
        $exec="mysql -h $host -u $user -p$passwd $database < $path\\$elem";
        doPassthru($exec, $ret);
    }
}

function doPassthru($exec, $ret){
    echo $exec ;
    passthru($exec, $ret);
    if($ret != 0) {
        echo " : Failed <br/>";
    }else{
        echo " : OK <br/>";
    }
    return true;
}

if( include('../public/ovms.ini.php') ){
    $host	  = OVMS_DB_HOST;
    $user	  = OVMS_DB_USER;
    $passwd	  = OVMS_DB_PASS;
    $database = OVMS_DB_NAME;
    $path     = OVMS_ROOT_PATH;
}else{
    $host	  = "localhost";
    $user	  = "root";
    $passwd	  = "root";
    $database = "fisma";
    $path     = "D:\wamp\www\openfisma";
}
$sqldbpath=$path.'\testdb';
if(!mysql_connect($host,$user,$passwd)) die("Could   not   connect   to   mysql");
if (isset($_GET['recover'])){
    if(!mysql_connect($host,$user,$passwd)) die("Could   not   connect   to   mysql");
    recoverDB($user,$passwd,$host,$database,$sqldbpath);
}else {
    echo "<p><a href='dbRecover.php?recover=click'>recover database</a><br/>";
}
echo "<p><a href=../>localhost:8366</a>"
?>
