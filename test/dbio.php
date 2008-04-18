<?php
/**
 * @file dbio.php
 * Import/Export/Truncate tables from a database, one file per table
 * import/im    tb://user:passwd@localhost/database path [-n]
 * export/ex    tb://user:passwd@localhost/database path
 * truncate/tr  tb://user:passwd@localhost/database path
 *
 * MIT License
 *
 * $Id$
*/

/* vim: set tabstop=4 shiftwidth=4 expandtab: */
define('SCHEMA_DATABASE','schema.sql');

function usage($order){
    echo "Usage:  php $order im/import/ex/export/tr/truncate db://user:password@host/database path [-n] \n";
    echo "im/import \t is import db from sql files which is exists; \n";
    echo "ex/export \t is export db to sql files; \n";
    echo "tr/truncate \t is empties all tables in this database; the parm 'path' will be ignored.";
    echo "path:\t the directory of the sql files; \n ";
    echo "[-n] is optional parameter just for create new database when parm im/import is exists. \n";
    die();
}

function checkParams($argv,$argc){
    if($argc < 3 || $argc > 5) {
         foreach ($argv as $elem){
             if(!assert($elem)) return false;
         }
         return false;
    }
    return true;
}

function actionImport($tb,$user,$passwd,$host,$database,$path,$new){
    $dbconn = mysql_select_db($database);
    if(!$dbconn) {
        if($new=='-n'){
            echo "Now create database $database. \n";
            mysql_query("create database $database");
            $exec="mysql -h $host -u $user -p$passwd $database < $path/".SCHEMA_DATABASE;
            passthru($exec);
        }elseif ($new=='') {
            echo "Database do not exist!, you need param '-n' at last. \n";
            die();
        }
    }
    $files = scandir($path);
    if(!$files){
        echo "Check params $path";
    }else{
        $sql_file = preg_grep("/[a-zA-Z0-9]*\.sql$/i",$files);
        echo "Now start importing <<< \n";
        foreach($sql_file as $elem){
        if($elem==SCHEMA_DATABASE) continue;
        $exec="mysql -h $host -u $user -p$passwd $database < $path/$elem";
        doPassthru($exec);
        }
    }
}

function actionTruncate($database){
    echo "Now emptying all tables exporting >>> \n";
    $result=mysql_list_tables($database);
    $tbs=array();
    for ($i = 0; $i < mysql_num_rows($result); $i++)
        $tbs[]=mysql_tablename($result, $i);
    mysql_free_result($result);
    foreach($tbs as $elem){
        $exec="truncate $elem";
        $result=mysql_query($exec) or die($exec.'Failure');
    }
    echo "Truncate success.";
}

function actionExport($tb,$user,$passwd,$host,$database,$path,$new){
    echo "Now start exporting --- \n";
    $result=mysql_list_tables($database);
    $tbs=array();
    for ($i = 0; $i < mysql_num_rows($result); $i++)
        $tbs[]=mysql_tablename($result, $i);
    mysql_free_result($result);
    foreach($tbs as $elem){
        $exec="mysqldump -h $host -u $user -p$passwd -q -t $database $elem > $path/$elem.sql";
        doPassthru($exec);
    }
        $exec="mysqldump -h $host -u $user -p$passwd -q -d $database > $path/".SCHEMA_DATABASE;
        doPassthru($exec);
}

function doPassthru($exec){

    echo $exec ;
    passthru($exec, $ret);
    if($ret != 0) {
        echo " : Failed\n";
    }else{
        echo " : OK \n";
    }

    return true;
}

//    Main()
if(checkParams($argv,$argc)){
    $action=$argv[1];
    list($tb,$tmp1,$tmp2,$user,$passwd,$host,$database)=split('[:/@]',$argv[2]);
    $path=$argv[3];
    $new=$argv[4];

    if(!mysql_connect($host,$user,$passwd)) die("Could   not   connect   to   mysql");


    switch ($action){
        case 'im':
        case 'import':
            actionImport($tb,$user,$passwd,$host,$database,$path,$new);
            break;
        case 'ex':
        case 'export':
            actionExport($tb,$user,$passwd,$host,$database,$path,$new);
            break;
        case 'tr':
        case 'truncate':
            actionTruncate($database);
            break;
        default:
            die("action param '$action' is error!!!");
    }

}else{
    usage($argv[0]);
}
?>
