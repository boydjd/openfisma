<?php

class mainfile_manager {

    var $path = '';
    var $rewrite = array();

    var $report = '';
    var $error = false;

    function mainfile_manager($path='./ovms.ini.php'){
        $this->path = $path;
    }

    function setRewrite($def, $val){
        $this->rewrite[$def] = $val;
    }

    function copyDistFile($distfile = './mainfile.dist.php'){
        if ( ! copy($distfile, $this->path) ) {
            $this->report .= _NGIMG.sprintf(_INST_WC_L7, "<b>".$this->path."</b>")."<br />\n";
            $this->error = true;
            return false;
        }
        $this->report .= _OKIMG.sprintf(_INST_WC_L6, "<b>".$this->path."</b>", "<b>".$distfile."</b>")."<br />\n";
        return true;
    }

    function doRewrite(){
        clearstatcache();
        if ( ! $file = fopen($this->path,"r") ) {
            $this->error = true;
            return false;
        }
        $content = fread($file, filesize($this->path) );
        fclose($file);
          $this->report="";
          foreach($this->rewrite as $key => $val){
            if(is_int($val) &&
             preg_match("/(define\()([\"'])(".$key.")\\2,\s*([0-9]+)\s*\)/",$content)){
                $content = preg_replace("/(define\()([\"'])(".$key.")\\2,\s*([0-9]+)\s*\)/"
                , "define('".$key."', ".$val.")"
                , $content);

                $this->report .= _OKIMG.sprintf(_INST_WC_L3, "<b>$key</b>", $val)."<br />\n";
            }
            elseif(preg_match("/(define\()([\"'])(".$key.")\\2,\s*([\"'])(.*?)\\4\s*\)/",$content)){
                $content = preg_replace("/(define\()([\"'])(".$key.")\\2,\s*([\"'])(.*?)\\4\s*\)/"
                , "define('".$key."', '". str_replace( '$', '\$', addslashes( $val ) ) ."')"
                , $content);
                $this->report .= _OKIMG.sprintf(_INST_WC_L3, "<b>$key</b>", $val)."<br />\n";
            }else{
                $this->error = true;
                $this->report .= _NGIMG.sprintf(_INST_WC_L9, "<b>$val</b>")."<br />\n";
                return false;
            }
        }

        if ( !$file = fopen($this->path,"w") ) {
            $this->error = true;
            return false;
        }

        if ( fwrite($file,$content) == -1 ) {
            fclose($file);
            $this->error = true;
            return false;
        }

        fclose($file);

        return true;
    }

    function report(){
        $content = "<table align='center'><tr><td align='left'>\n";
        $content .= $this->report;
        $content .= "</td></tr></table>\n";
        return $content;
    }

    function error(){
        return $this->error;
    }
}

?>
