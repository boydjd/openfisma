<?php
/**
 * @file database.php
 *
 * @description Database Configuration Info
 *
 * @author     Jim <jimc@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

   require_once( WEB_ROOT . DS . 'ovms.ini.php');

    Zend_Registry::set('legacy_datasource', new Zend_Config(
        array(
        'default' => array(
            'adapter' => 'mysqli',
            'params' => array(
                'host' => OVMS_DB_HOST,
                'port' => '',
                'username' => OVMS_DB_USER,
                'password' => OVMS_DB_PASS,
                'dbname' => 'legacy_fisma',
                'profiler' => false
            )
        ))
    ));

    Zend_Registry::set('datasource', new Zend_Config(
        array(
        'default' => array(
            'adapter' => 'mysqli',
            'params' => array(
                'host' => OVMS_DB_HOST,
                'port' => '',
                'username' => OVMS_DB_USER,
                'password' => OVMS_DB_PASS,
                'dbname' => OVMS_DB_NAME,
                'profiler' => false
            )
        ))
    ));
?>
