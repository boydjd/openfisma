<?php
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
function b_back($option = null)
{
    if(!isset($option) || !is_array($option)) return '';
    $content = '';
    if(isset($option[0]) && $option[0] != ''){
        $content .= "<input type='button' value='"
            ._INST_WHOLE_2."' onclick=\"location='index.php?op="
            .htmlspecialchars($option[0])."'\" /> ";
    }else{
        $content .= "<input type='button' value='"
            ._INST_WHOLE_2."' onclick=\"javascript:history.back();\" /> ";
    }
    if(isset($option[1]) && $option[1] != ''){
        $content .= "<span style='font-size:85%;'><< "
                .htmlspecialchars($option[1])."</span> ";
    }
    return $content;
}

function b_reload($option=''){
    if(empty($option)) return '';
	if (!defined('_INST_WHOLE_6')) {
		define('_INST_WHOLE_6', 'Reload');
	}
    return  "<input type='button' value='"._INST_WHOLE_6."' onclick=\"location.reload();\" /> ";
}

function b_next($option=null){

    if(!isset($option) || !is_array($option)) return '';
    $content = '';
    if(isset($option[1]) && $option[1] != ''){
        $content .= "<span style='font-size:85%;'>"
                .htmlspecialchars($option[1])." >></span>";
    }
    $content .= "<input type='hidden' name='op' value='"
                .htmlspecialchars($option[0])."' />\n";
    $content .= "<input type='submit' name='submit' value='"._INST_WHOLE_1."' />\n";
    return $content;
}

function dir_writeable($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777);
    }
    if (is_dir($dir)) {  // if already exists
        if ($fp = @fopen("$dir/test.test", 'w')) {    // create a test file
            @fclose($fp);               
            @unlink("$dir/test.test");   
            $writeable = 1;            //return 1
        } else {
            $writeable = 0;          //return 0 
        }
    }
    return $writeable;
}   

function showMessage($msg) {
    if ($msg == '') return ;
    echo $msg."<br>";
    flush();
    ob_flush();
}

function getCreateTableMessage($sql){
    $sql = trim($sql);
    if (strtoupper(substr($sql, 0, 12)) == 'CREATE TABLE') {
        $name = preg_replace("/CREATE TABLE `([a-z0-9_]+)` .is", "\\1", $sql);
        return "Create table ".$name." ... , Success!";
    }
    return '';
}

function writeConfig(){
    define('CONF_FILE','../config.inc.php');
    if (!is_writeable(CONF_FILE)) die('The configuration file is not writeable.');
    $config = file_get_contents(CONF_FILE);
    foreach ($database as $k=>$DB) {
        foreach ($DB as $key=>$value) {
            $config = preg_replace("/[$]".strtoupper($k)."_".$key."\s*\=\s*[\"'].*?[\"'];/is", "\$".strtoupper($k)."_".$key." = '$value';", $config);
        }
    }
    if (!file_put_contents(CONF_FILE, $config))
    {
        die('Write to config file fail!');
    }
   // showMessage('Write to config file OK!');
}

function test($pre,$small){
    $big = strtoupper($small);
    eval('$end = '.$pre.$big.';');
    return $end;
}

function try_db_connect($sm){
    $db = & NewADOConnection($sm->getConfigVal('type'));
    $db->setFetchMode(ADODB_FETCH_NUM);
    $db->port = $sm->getConfigVal('port');
    $dbhost = $sm->getConfigVal('host');
    $dbname = $sm->getConfigVal('dbname');
    $name = $sm->getConfigVal('uname');
    $passwd = $sm->getConfigVal('upass');
    try {
       $db->Connect($dbhost,$name,$passwd,$dbname); 
       $out = "<p>"._OKIMG._INST_TEST_CONN_1;
    }catch(Exception $e){
       $out = "<p>"._NGIMG._INST_TEST_CONN_2;
       $errMessage = true;
    }
    return $out;
}

/*
 *this function adjust import data
 *param $datafile schema file
 *return a formatted string
 */
function format_data($dataString){
    $dataString = preg_replace('/\/\*.*\*\//','',$dataString);
    if(ereg(";$",trim($dataString))){
        $execute['opt']='execute';
    }else{
        $execute['opt']='incomplete';
    }
    $execute['sql']= $dataString;
    return $execute;    
}
/*
 *this function import data
 *param a formatted array  
 *please make sure comment in one line!
 */
function import_data($db,$dataFile){
    $tmp = "";
    foreach($dataFile as $elem){
        try{
            $handle = fopen($elem,'r+');
            $dumpline = '';
            while(!feof($handle)&& substr($dumpline,-1)!= "\n"){
                $dumpline = fgets($handle,'4096');
                $dumpline = ereg_replace("\r\n$","\n",$dumpline);
                $dumpline = ereg_replace("\r$","\n",$dumpline);
                $dumpline = trim($dumpline);
                $execute = format_data($dumpline);
                if($execute['opt']=='incomplete'){
                    $tmp .= $execute['sql'];
                }else{
                    $db->query($tmp.$execute['sql']);
                    $tmp = '';
                }
            }
        }catch(Exception $e){
            var_dump($e);
            return false;
        }
     }
    return  true; 
}

?>
