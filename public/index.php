<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

try {
    defined('APPLICATION_PATH')
        || define(
            'APPLICATION_PATH',
            realpath(dirname(__FILE__) . '/../application')
        );

    // Define application environment
    defined('APPLICATION_ENV')
        || define(
            'APPLICATION_ENV',
            (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production')
        );

    set_include_path(
        APPLICATION_PATH . '/../library/Symfony/Components' . PATH_SEPARATOR . 
        APPLICATION_PATH . '/../library' .  PATH_SEPARATOR . 
        get_include_path()
    );

    require_once 'Fisma.php';
    require_once 'Zend/Application.php';

    $application = new Zend_Application(
        APPLICATION_ENV,
        APPLICATION_PATH . '/config/application.ini'
    );

    Fisma::setAppConfig($application->getOptions());
    Fisma::initialize(Fisma::RUN_MODE_WEB_APP);

    $application->bootstrap()->run();
} catch (Zend_Config_Exception $zce) {
    // A zend config exception indicates that the application may not be installed properly
    echo '<h1>The application is not installed correctly</h1>';
    
    $zceMsg = $zce->getMessage();
    
    if (stristr($zceMsg, 'parse_ini_file') !== false) {
    
        if (stristr($zceMsg, 'application.ini') !== false) {
            
            if (stristr($zceMsg, 'No such file or directory') !== false) {
                echo 'The ' . APPLICATION_PATH . '/config/application.ini file is missing.';
            } elseif (stristr($zceMsg, 'Permission denied') !== false) {
                echo 'The ' . APPLICATION_PATH . '/config/application.ini file does not have the ' . 
                    'appropriate permissions set for the application to read it.';
            } else {
                echo 'An ini-parsing error has occured in ' . APPLICATION_PATH . '/config/application.ini ' . 
                    '<br/>Please check this file and make sure everything is setup correctly.';
            }
            
        } else if (stristr($zceMsg, 'database.ini') !== false) {
        
            if (stristr($zceMsg, 'No such file or directory') !== false) {
                echo 'The ' . APPLICATION_PATH . '/config/database.ini file is missing.<br/>';
                echo 'If you find a database.ini.template file in the config directory, edit this file ' . 
                    'appropriately and rename it to database.ini';
            } elseif (stristr($zceMsg, 'Permission denied') !== false) {
                echo 'The ' . APPLICATION_PATH . '/config/database.ini file does not have the appropriate ' . 
                    'permissions set for the application to read it.';
            } else {
                echo 'An ini-parsing error has occured in ' . APPLICATION_PATH . '/config/database.ini ' . 
                    '<br/>Please check this file and make sure everything is setup correctly.';
            }
        
        } else {
            echo 'An ini-parsing error has occured. <br/>Please check all configuration files and make sure ' . 
                'everything is setup correctly';
        }
    
    } elseif (stristr($zceMsg, 'syntax error') !== false) {
    
        if (stristr($zceMsg, 'application.ini') !== false) {
            echo 'There is a syntax error in ' . APPLICATION_PATH . '/config/application.ini ' . 
                '<br/>Please check this file and make sure everything is setup correctly.';
        } elseif (stristr($zceMsg, 'database.ini') !== false) {
            echo 'There is a syntax error in ' . APPLICATION_PATH . '/config/database.ini ' . 
                '<br/>Please check this file and make sure everything is setup correctly.';
        } else {
            echo 'A syntax error has been reached. <br/>Please check all configuration files and make sure ' . 
                'everything is setup correctly.';
        }
    
    } else {
        
        // Then the exception message says nothing about parse_ini_file nor 'syntax error'
        echo 'Please check all configuration files, and ensure all settings are valid.';
    }
    
    echo '<br/>For more information and help on installing OpenFISMA, please refer to the ' . 
        '<a target="_blank" href="http://manual.openfisma.org/display/ADMIN/Installation">' . 
        'Installation Guide</a>';

} catch (Doctrine_Manager_Exception $dme) {

    echo '<h1>An exception occurred while bootstrapping the application.</h1>';
    
    // Does database.ini have valid settings? Or is it the same content as database.ini.template?
    $databaseIniFail = false;
    $iniData = file(APPLICATION_PATH . '/config/database.ini');
    $iniData = str_replace(chr(10), '', $iniData);

    if (in_array('db.adapter = ##DB_ADAPTER##', $iniData)) {
        $databaseIniFail = true;
    }
    if (in_array('db.host = ##DB_HOST##', $iniData)) {
        $databaseIniFail = true;
    }
    if (in_array('db.port = ##DB_PORT##', $iniData)) {
        $databaseIniFail = true;
    }
    if (in_array('db.username = ##DB_USER##', $iniData)) {
        $databaseIniFail = true;
    }
    if (in_array('db.password = ##DB_PASS##', $iniData)) {
        $databaseIniFail = true;
    }
    if (in_array('db.schema = ##DB_NAME##', $iniData)) {
        $databaseIniFail = true;
    }

    if ($databaseIniFail) {
        echo 'You have not applied the settings in ' . APPLICATION_PATH . '/config/database.ini appropriately. ' . 
            'Please review the contents of this file and try again.';
    } else {
    
        if (Fisma::debug()) {
            echo '<p>' 
                 . get_class($dme) 
                 . '</p><p>' 
                 . $dme->getMessage() 
                 . '</p><p>'
                 . "<p><pre>Stack Trace:\n" 
                 . $dme->getTraceAsString() 
                 . '</pre></p>';
        } else {
            $logString = get_class($dme) 
                       . "\n"
                       . $dme->getMessage() 
                       . "\nStack Trace:\n" 
                       . $dme->getTraceAsString() 
                       . "\n";

            Zend_Registry::get('Zend_Log')->err($logString);
        }
    }

} catch (Exception $exception) {
    // If a bootstrap exception occurs, that indicates a serious problem, such as a syntax error.
    // We won't be able to do anything except display an error.
    echo '<h1>An exception occurred while bootstrapping the application.</h1>';
    if (Fisma::debug()) {
        echo '<p>' 
             . get_class($exception) 
             . '</p><p>' 
             . $exception->getMessage() 
             . '</p><p>'
             . "<p><pre>Stack Trace:\n" 
             . $exception->getTraceAsString() 
             . '</pre></p>';
    } else {
        $logString = get_class($exception) 
                   . "\n"
                   . $exception->getMessage() 
                   . "\nStack Trace:\n" 
                   . $exception->getTraceAsString() 
                   . "\n";
        
        Zend_Registry::get('Zend_Log')->err($logString);
    }
}
