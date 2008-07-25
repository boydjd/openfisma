<?php
/**
 * setting.php
 *
 * Setting for the whole sysetm
 *
 * @package Config
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

Zend_Registry::set('installed', false);
if(is_file(CONFIGS . DS . CONFIGFILE_NAME)) {
    $config=new Zend_Config_Ini(CONFIGS . DS . CONFIGFILE_NAME ); 
    if( !empty($config->database) ) {
        Zend_Registry::set('datasource',$config->database);
        Zend_Registry::set('installed', true);
    }
}
