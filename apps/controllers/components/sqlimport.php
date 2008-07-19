<?php
/* vim: set tabstop=4 shiftwidth=4 expandtab: */

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
        $ret = true;
        if($handle = fopen($elem,'r')) {
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
                    $ret = $db->query($tmp.$execute['sql']);
                    $tmp = '';
                }
                if( !$ret ) {
                    break;
                }
            }
        }else{
            $ret = false;
        }
        if(!$ret){
            return $ret;
        }
     }
     return  true; 
}

?>
