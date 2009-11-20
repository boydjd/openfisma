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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * The install controller handles all of the actions for the installer program.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Controller
 * @version    $Id$
 */
class InstallController extends Zend_Controller_Action
{

    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $this->_helper->layout->setLayout('install');
    }

    /**
     * Default Action
     */
    public function indexAction()
    {
        $this->view->back = '';
        $this->view->next = '/install/envcheck';
    }

    /**
     * Check the current environment for installing system
     */
    public function envcheckAction()
    {
        define('REQUEST_PHP_VERSION', '5');
        $this->view->back = '/install';
        if (version_compare(phpversion(), REQUEST_PHP_VERSION, '>')) {
            $this->view->next = '/install/checking';
            $this->view->checklist = array(
                'version' => 'ok'
            );
        } else {
            $this->view->checklist = array(
                'version' => 'failure'
            );
            $this->view->next = '';
        }
    }

    /**
     * Check the the dir whether is writable
     */
    public function checkingAction()
    {
        $wDirectories = array(
            Fisma::getPath('data') . '/logs',
            Fisma::getPath('data') . '/uploads/evidence',
            Fisma::getPath('data') . '/cache',
            Fisma::getPath('data') . '/sessions',
            Fisma::getPath('data') . '/temp',
            Fisma::getPath('data') . '/index',
            Fisma::getPath('data') . '/uploads/evidence',
            Fisma::getPath('data') . '/uploads/scanreports',
            Fisma::getPath('model') . '/generated',
            Fisma::getPath('config')
        );
        $notwritables = array();
        foreach ($wDirectories as $k => $wok) {
            if (!is_writeable($wok)) {
                array_push($notwritables, $wok);
                unset($wDirectories[$k]);
            }
        }
        $this->view->notwritables = $notwritables;
        $this->view->writables = $wDirectories;
        $this->view->back = '/install/envcheck';
        if (empty($notwritables)) {
            $this->view->next = '/install/dbsetting';
        } else {
            $this->view->next = '';
        }
    }

    /**
     * Configure the database
     */
    public function dbsettingAction()
    {
        $this->view->installpath = dirname(dirname(dirname(__FILE__)));
        $this->view->dsn = array(
            'host' => 'localhost',
            'port' => '3306'
        );
        $this->view->title = 'General settings';
        $this->view->back = '/install/checking';
        $this->view->next = '/install/dbreview';
    }

    /**
     * Review the database's configuration
     */
    public function dbreviewAction()
    {
        $dsn = $this->_getParam('dsn');
        if (empty($dsn['name_c'])
            && empty($dsn['pass_c'])
            && empty($dsn['pass_c_ag'])) {
            $dsn['name_c'] = $dsn['uname'];
            $dsn['pass_c'] = $dsn['upass'];
            $dsn['pass_c_ag'] = $dsn['upass'];
        }
        $passwordCompare = array(
            $dsn['pass_c']
        );
        $filter = array(
            '*' => array(
                'StringTrim',
                'stripTags'
            )
        );
        $validator = array(
            'encrypt' => 'NotEmpty',
            'type' => 'Alnum',
            'host' => array(
                'NotEmpty',
                new Zend_Validate_Hostname(
                        Zend_Validate_Hostname::ALLOW_LOCAL | 
                        Zend_Validate_Hostname::ALLOW_IP 
                    )
            ) ,
            'port' => array(
                'Int',
                new Zend_Validate_Between(0, 65535)
            ) ,
            'uname' => 'NotEmpty',
            'upass' => 'NotEmpty',
            'dbname' => 'NotEmpty',
            'adminpwd' => 'NotEmpty',
            'name_c' => 'NotEmpty',
            'pass_c' => 'NotEmpty',
            'pass_c_ag' => array(
                'NotEmpty',
                new Zend_Validate_InArray($passwordCompare)
            )
        );
        $fv = new Zend_Filter_Input($filter, $validator);
        $input = $fv->setData($dsn);
        $this->view->title = 'General settings';
        $this->view->dsn = $dsn;
        if ($input->hasInvalid() || $input->hasMissing()) {
            $message = $input->getMessages();
            $this->view->back = '/install/checking';
            $this->view->next = '/install/dbreview';
            $this->view->message = $message;
            $this->render('dbsetting');
        } else {
            $this->view->back = '/install/dbsetting';
            $this->view->next = '/install/initial';
        }
    }

    /**
     * Initilize the system
     */
    public function initialAction()
    {
        $dsn = $this->_getParam('dsn');
        $checklist = array(
            'connection' => 'failure',
            'creation' => 'failure',
            'grant' => 'failure',
            'schema' => 'failure',
            'savingconfig' => 'failure'
        );
        $method = 'connection';
        // create config file
        $configInfo = file_get_contents(Fisma::getPath('config') . '/app.conf.template');
        $configInfo = str_replace(
            array(
                '##DB_ADAPTER##', 
                '##DB_HOST##', 
                '##DB_PORT##',
                '##DB_USER##', 
                '##DB_PASS##', 
                '##DB_NAME##'
            ), 
            array(
                $dsn['type'], 
                $dsn['host'], 
                $dsn['port'], 
                $dsn['uname'],
                $dsn['upass'], 
                $dsn['dbname']
            ), 
            $configInfo
        );
        file_put_contents(Fisma::getPath('config') . '/app.conf', $configInfo);
        
        // test the connection of database
        try {
            $method = 'connection / creation';
            Fisma::initialize(Fisma::RUN_MODE_WEB_APP);
            Fisma::connectDb();
            Fisma::getNotificationEnabled(false);

            $checklist['connection'] = 'ok';
            // create database
            $method = 'creation';
            Doctrine::createDatabases();
            $checklist['creation'] = 'ok';
            Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
            Doctrine::createTablesFromModels(Fisma::getPath('model'));
            Zend_Auth::getInstance()->setStorage(new Fisma_Auth_Storage_Session())
                                    ->clearIdentity();

            //load sample data
            Doctrine::loadData(Fisma::getPath('fixture'));

            $config = Doctrine::getTable('Configuration')->findOneByName('hash_type');
            $config->value = $dsn['encrypt'];
            $config->save();
            
            $root = Doctrine::getTable('User')->find(1);
            $root->password = $dsn['adminpwd'];
            $root->save();
            
            $checklist['schema'] = 'ok';
            $checklist['savingconfig'] = 'ok';
            $this->view->next = '/install/complete';
        } catch (Exception $e) {
            @unlink(Fisma::getPath('config') . '/app.conf');
            throw $e;
        }

        $this->view->dsn = $dsn;
        $this->view->title = 'Initial Database';
        $this->view->method = $method;
        $this->view->checklist = $checklist;
        $this->view->back = '/install/dbsetting';
        
        // Overwrite the default host_url value. Can't use the Configuration class b/c that requires authentication.
        $setHostUrlQuery = Doctrine_Query::create()
                           ->update('Configuration c')
                           ->set('value', '?', Zend_Controller_Front::getInstance()->getRequest()->getHttpHost())
                           ->where('name LIKE ?', 'host_url');
        if (!$setHostUrlQuery->execute()) {
            throw new Exception("Unable to set the host_url value in the configuration table.");
        }
        
        $this->render('initial');
    }

    /**
     * Completing the installation
     */
    public function completeAction()
    {
        $this->view->title = 'Install complete';
        $this->view->next = '/';
    }

    /**
     * Handling the error
     */
    public function errorAction()
    {
        $this->getResponse()->clearBody();
        
        // Display an error. Notice that the installer displays stack traces even when in "production" mode.
        $errors = $this->_getParam('error_handler');
        if (!empty($errors)) {
            $content = get_class($errors->exception)
                     . ": "
                     . $errors->exception->getMessage()
                     . '<br>'
                     . $errors->exception->getTraceAsString()
                     . '<br>';
        }
        $this->view->error = $content;
    }
}
