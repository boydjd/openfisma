<?php

class setting_manager {

    var $sanitizer;
    var $dbSurfix = '';
    var $url ='';
    var $pass_c_mismatch = true;
    var $dsn = array (               'type'=>'mysql',
                                     'host' => 'localhost',
                                     'port' => '3306',
                                     'uname'=> '',
                                     'upass'=> '',
                                    'dbname'=> '',
                                    'name_c'=> '',
                                    'pass_c'=> '',
                                    'rpath'=> '');

    var $settingNames = '';
    private static $constantNames = array ( 'type'=>'OVMS_DB_TYPE',
                                     'host' => 'OVMS_DB_HOST',
                                     'port' => 'OVMS_DB_PORT',
                                     'uname'=> 'OVMS_DB_USER',
                                     'upass'=> 'OVMS_DB_PASS',
                                    'dbname'=> 'OVMS_DB_NAME',
                                   'name_c'=> 'OVMS_DB_NAME_C',
                                   'pass_c'=>'OVMS_DB_PASS_C',
                                   'rpath'=>'OVMS_ROOT_PATH');
    private static $commentNames = array (   'host' => _INST_DB_L1,
                                     'port' => _INST_DB_L2,
                                     'uname'=> _INST_DB_L3,
                                     'upass'=> _INST_DB_L4,
                                    'dbname'=> _INST_DB_L5,
                                    'name_c'=> _INST_DB_L6,
                                    'pass_c'=> _INST_DB_L7,
                                    'rpath' => _INST_DB_L13);

    /** Get configuration from POST or constant and append no surfix.

     *  @param $from 'post' indicate configuration information from POST; 'const' from constant; default otherwise
     *  @param $sfx surfix that make different configuration.
     */
    function setting_manager($from='',$sfx=''){
        $glue = '';
        if($sfx != ''){
            $this->dbSurfix = strtoupper($sfx);
            $glue = '_';
        }
        foreach(self::$constantNames as $key => $name) {
            $this->settingNames[$key] = $name . $glue .strtoupper($sfx);
        }
        $this->sanitizer =& TextSanitizer::getInstance();
        if($from == 'post'){
            $this->readPost();
        }elseif($from == 'const' ){
            $this->readConstant();
        }else{ //default value
            $this->dsn['type'] = 'mysql';
            $this->dsn['host'] = 'localhost';
            $filepath = (! empty($_SERVER['REQUEST_URI']))
                            ? dirname($_SERVER['REQUEST_URI'])
                            : dirname($_SERVER['SCRIPT_NAME']);
        
            $filepath = str_replace("\\", "/", $filepath); // "
            $filepath = str_replace("/install", "", $filepath);
            if ( substr($filepath, 0, 1) == "/" ) {
                $filepath = substr($filepath,1);
            }
            if ( substr($filepath, -1) == "/" ) {
                $filepath = substr($filepath, 0, -1);
            }
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
            $this->url = (!empty($filepath)) ? $protocol.$_SERVER['HTTP_HOST']."/".$filepath : $protocol.$_SERVER['HTTP_HOST'];
            $this->dsn['rpath'] = realpath(dirname($_SERVER['SCRIPT_FILENAME'])."/../..");
        }
    }

    /** Read configuration from _POST data.
     */
    function readPost(){
        foreach(self::$constantNames as $key => $name) {
            if(isset($_POST[$key.$this->dbSurfix])) {
                $this->dsn[$key] = trim($this->sanitizer->stripSlashesGPC($_POST[$key.$this->dbSurfix]));
            }
        }
        $this->pass_c_mismatch = true;
        if(isset($_POST['pass_c'.$this->dbSurfix.'_ag'])) {
            $this->pass_c_mismatch = 
                ($this->dsn['pass_c'] == trim($this->sanitizer->stripSlashesGPC($_POST['pass_c'.$this->dbSurfix.'_ag']) ) );
        }
        if(isset($_POST['sws_url'])){
            $this->url = $this->sanitizer->stripSlashesGPC($_POST['sws_url']);
        }
    }

    /** Read configuration from the defined constant.
     */
    function readConstant(){
        foreach($this->settingNames as $key => $name) {
            if(defined( $name )) {
                $this->dsn[$key] = constant($name);
            }
        }

        if(defined('OVMS_URL') ) {
            $this->url = OVMS_URL;
        }
    }

    /**  If do not need to init database,use function below
     */
    function init_db_val($isInitDB = 'first') {
       assert(in_array($isInitDB, array('first','second')));
       if($isInitDB=='second'){
           self::$commentNames = array ('host' => _INST_DB_L1,
                                        'port' => _INST_DB_L2,
                                        'dbname'=> _INST_DB_L5,
                                        'uname'=> _INST_DB_L3,
                                        'upass'=> _INST_DB_L4,
                                        'rpath'=>_INST_DB_L13);

        }
     }

    /** Check if the required data are provided.
     *  if not error message are composed and returned.
     */
    function checkData($isInitDB = 'first'){
        $this -> init_db_val($isInitDB);
        $ret = "";
        foreach(self::$commentNames as $key => $comment){
            if ( empty($this->dsn[$key]) ) {
                $err = sprintf(_INST_DB_L9,$comment);
                $ret .=  "<p><span style='color:#ff0000;'><b>".$err."</b></span></p>\n";
            }
        }
        if(false == $this->pass_c_mismatch) {
            $ret .="<p><span style='color:#ff0000;'><b>"._INST_DB_L12."</b></span></p>\n";
        }
        return $ret;
    }

    function editform($isInitDB = 'first'){
        $ret =
            "<table width='100%' class='outer' cellspacing='5'>
                <tr>
                    <th colspan='2'><h4 style='color:green;'>$this->dbSurfix</h4></th>
                </tr>
                <tr valign='top' align='left'>
                    <td class='head'>
                        <b>"._INST_DB_L10."</b><br />
                        <span style='font-size:85%;'>"._INST_DB_L11."</span>
                    </td>
                    <td class='even'>
                        <select  size='1' name='database' id='database'>";
        $dblist = $this->getDBList();
        foreach($dblist as $val){
            $ret .= "<option value='$val'";
            if($val == $this->dsn['type']) $ret .= " selected='selected'";
            $ret .= "'>$val</option>";
        }
        $ret .=         "</select>
                    </td>
                </tr>
                ";
        $hints = array(_INSTALL_L66, _INSTALL_L67,_INSTALL_L65,_INSTALL_L68,_INSTALL_L64,'','','');
        $this->init_db_val($isInitDB);
        foreach(self::$commentNames as $key=>$comment) {
            $ret .= $this->editform_sub($comment,
                       current($hints),
                       $key.$this->dbSurfix, 
                       $this->dsn[$key]);
            if($key == 'pass_c') {
                $ret .= $this->editform_sub('Confirm password',
                           '',
                           $key.$this->dbSurfix.'_ag', 
                           $this->dsn[$key]);
            }
            next($hints);
        }
        $ret .= "</table>";
        return $ret;
    }

  function editform_sub($title, $desc, $name, $value) {
      if( preg_match('/pass/i', $name) ) {
          $inputType = 'password';
          return  "<tr valign='top' align='left'>
                    <td class='head'>
                        <b>".$title."</b><br />
                        <span style='font-size:85%;'>".$desc."</span>
                    </td>
                    <td class='even'>
                        <input type='$inputType' name='".$name."' id='".$name."' size='30' maxlength='100'/>
                    </td>
                </tr>
                ";
      }else {
          $inputType = 'text';
          return  "<tr valign='top' align='left'>
                    <td class='head'>
                        <b>".$title."</b><br />
                        <span style='font-size:85%;'>".$desc."</span>
                    </td>
                    <td class='even'>
                        <input type='$inputType' name='".$name."' id='".$name."' size='30' maxlength='100' value='".htmlspecialchars($value)."' />
                    </td>
                </tr>
                ";
      }
  } 

  function confirmForm(){
        $ret =
            "<table border='0' cellpadding='0' cellspacing='0' valign='top' width='90%'><tr><td class='bg2'>
                <table width='100%' border='0' cellpadding='4' cellspacing='1' style=\"TABLE-LAYOUT:fixed;word-break:break-all;word-wrap:break-word;\">
                    <tr>
                        <td class='bg3'><b>"._INST_DB_L10."</b></td> 
                        <td class='bg1'>".$this->dsn['type']."</td>
                    </tr>";

         foreach(self::$commentNames as $key => $comment) {
             $ret .= "
                    <tr>
                        <td class='bg3'><b>$comment</b></td> 
                        <td class='bg1' style=\"word-break:break-all;\">".$this->dsn[$key]."</td>
                    </tr>";
         }
         $ret .= " </table></td></tr>
            </table>";

            foreach(self::$constantNames as $key => $name) {
                $ret .= "<input type='hidden' name='$key$this->dbSurfix' value='".$this->dsn[$key]."' />";
            }
        return $ret;
    }

    function getDBList()
    {
		return array('mysql');
        //$dirname = '../class/database/';
        //$dirlist = array();
        //if (is_dir($dirname) && $handle = opendir($dirname)) {
        //    while (false !== ($file = readdir($handle))) {
        //        if ( !preg_match("/^[.]{1,2}$/",$file) ) {
        //            if (strtolower($file) != 'cvs' && is_dir($dirname.$file) ) {
        //                $dirlist[$file] = strtolower($file);
        //            }
        //        }
        //   }
        //    closedir($handle);
        //    asort($dirlist);
        //    reset($dirlist);
        //}
        //return $dirlist;
    }

    function saveToFile(mainfile_manager &$mm) {
        foreach($this->settingNames as $key => $cntName){
            $mm->setRewrite($cntName, $this->dsn[$key]);
        }
        return $mm->doRewrite();
    }

    function getConfigVal($idx) {
        return $this->dsn[$idx];
    }
    function getConfigName($idx) {
        return self::$commentNames[$idx];
    }

    function clearRootAccount(mainfile_manager &$mm) {
        $mm->setRewrite($this->settingNames['uname'], $this->dsn['name_c']);
        $mm->setRewrite($this->settingNames['upass'], $this->dsn['pass_c']);
        return $mm->doRewrite();
    }
}
?>
