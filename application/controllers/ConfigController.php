<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The configuration controller deals with displaying and updating system
 * configuration items through the user interface.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class ConfigController extends SecurityController
{
    /**
     * The Config module 
     */
    private $_config = null;

    /**
     * init() - Initialize internal members.
     */
    public function init()
    {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('ldapvalid', 'html')
                    ->initContext();
    }

    /**
     * getConfigForm() - Returns the standard form for system configuration.
     *
     * @param string $formName The name of the form to load
     * @return Zend_Form
     */
    public function getConfigForm($formName) {
        // Load the form and populate the dynamic pull downs
        $form = Fisma_Form_Manager::loadForm($formName);
        $form = Fisma_Form_Manager::prepareForm($form);

        return $form;
    }

    /**
     * @todo english
     * The default Action
     */
    public function indexAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->actionStack('password');
        $this->_helper->actionStack('notification');
        $this->_helper->actionStack('contact');
        $this->_helper->actionStack('privacy');
        $this->_helper->actionStack('view');
    }
    /**
     * Display and update the persistent configurations
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');

        $form = $this->getConfigForm('general_config');
        if ($this->_request->isPost()) {
            $configPost = $this->_request->getPost();
            if ('genernal' == $this->_request->getParam('type')) {
                if ($form->isValid($configPost)) {
                    $values = $form->getValues();
                    //array_intersect_key requires PHP > 5.1.0
                    $validVals = array(
                        Configuration::SYSTEM_NAME =>0,
                        Configuration::MAX_ABSENT  =>0,
                        Configuration::AUTH_TYPE   =>0,
                        Configuration::EXPIRING_TS =>0,
                        Configuration::USE_NOTIFICATION =>0,
                        Configuration::BEHAVIOR_RULE =>0,
                        Configuration::ROB_DURATION  =>0
                    );

                    $values = array_intersect_key($values, $validVals);
                    // to store modified key
                    $records = array();
                    foreach ($values as $k => $v) {
                        $config = Doctrine::getTable('Configuration')->findOneByName($k);
                        if (in_array($k, array(Configuration::EXPIRING_TS, Configuration::UNLOCK_DURATION))) {
                            $v *= 60; //convert to second
                        }
                        if (in_array($k, array(Configuration::USE_NOTIFICATION,Configuration::BEHAVIOR_RULE))) {
                            $config->description = $v;
                            if ($config->isModified()) {
                                $config->save();
                                array_push($records, $k);
                            }
                        } else {
                            $config->value = $v;
                            if ($config->isModified()) {
                                $config->save();
                                array_push($records, $k);
                            }
                        }
                    }
                    
                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $errorString = Fisma_Form_Manager::getErrors($form);
                    // Error message
                    $this->message("Unable to save general policies:<br>$errorString", self::M_WARNING);
                }
            }
        }
        
        $configs = Doctrine::getTable('Configuration')->findAll();

        $configArray = array();
        foreach ($configs as $config) {
            if (in_array($config->name, array(Configuration::EXPIRING_TS, Configuration::UNLOCK_DURATION))) {
                $config->value /= 60; //convert to minute from second
            }
            if (in_array($config->name, array(Configuration::USE_NOTIFICATION, Configuration::BEHAVIOR_RULE))) {
                $config->value = $config->description;
            }
            $configArray[$config->name] = $config->value;
        }
        $form->setDefaults($configArray);
        $this->view->generalConfig = $form;

        if ('ldap' == Configuration::getConfig('auth_type', true)) {
            $this->_helper->actionStack('ldaplist');
        }

        $this->render();
    }

    /**
     * Get Ldap configuration list
     */
    public function ldaplistAction()
    {
        $ldaps = Doctrine::getTable('LdapConfig')->findAll();
        $this->view->assign('ldaps', $ldaps->toArray());
        $this->render();
    }

    /**
     *  Add/Update Technical Contact Information configurations
     */
    public function contactAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        
        $form = $this->getConfigForm('contact_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ('contact' == $this->_request->getParam('type')) {
                if ($form->isValid($data)) {
                    $data = $form->getValues();
                    unset($data['saveContactConfig']);
                    unset($data['reset']);
                    foreach ($data as $k => $v) {
                        $config = Doctrine::getTable('Configuration')->findOneByName($k);
                        $config->value =  $v;
                        $config->save();
                    }
                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $errorString = Fisma_Form_Manager::getErrors($form);
                    // Error message
                    $this->message("Unable to save Technical Contact Information:<br>$errorString",
                        self::M_WARNING);
                }
            }
        }
        $columns = array('contact_name', 'contact_phone', 'contact_email', 'contact_subject');
        foreach ($columns as $column) {
            $configs[$column] = Configuration::getConfig($column);
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
        $this->render();
    }


    /**
     *  Add/Update LDAP configurations
     */
    public function ldapupdateAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        
        $form = $this->getConfigForm('ldap');
        $id = $this->_request->getParam('id');
        
        $ldap = new LdapConfig();
        if (!empty($id)) {
            $ldap = $ldap->getTable('LdapConfig')->find($id);
        }
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                $values = $form->getValues();
                $ldap->merge($values);
                $ldap->save();
                
                $msg = 'Configuration updated successfully';
                $this->message($msg, self::M_NOTICE);
                $this->_redirect('/panel/config/');
                return;
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->message("Unable to save Ldap Configurations:<br>$errorString", self::M_WARNING);
            }
        } else {
            //only represent the view
            $form->setDefaults($ldap->toArray());
        }
        $this->view->form = $form;
        $this->render();
    }

    /**
     * Delete a Ldap configuration
     */
    public function ldapdelAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        
        $id = $this->_request->getParam('id');
        Doctrine::getTable('LdapConfig')->find($id)->delete();
        $msg = "Ldap Server deleted successfully.";
        $this->message($msg, self::M_NOTICE);
        $this->_forward('index');
    }

    /**
     * Validate the configuration
     *
     * This is only happens in ajax context
     */
    public function ldapvalidAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        
        $form = $this->getConfigForm('ldap');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                try{
                    $data = $form->getValues();
                    unset($data['id']);
                    unset($data['SaveLdap']);
                    unset($data['Reset']);
                    $ldapcn = new Zend_Ldap($data);
                    $ldapcn->connect();
                    $ldapcn->bind();
                    echo "<b> Bind successfully! </b>";
                }catch (Zend_Ldap_Exception $e) {
                    echo "<b>". $e->getMessage(). "</b>";
                }
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                echo $errorString;
            }
        } else {
            echo "<b>Invalid Parameters</b>";
        }
        $this->_helper->viewRenderer->setNoRender();

    }

    /**
     * Notification event system base setting
     *
     */
    public function notificationAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        
        $form = $this->getConfigForm('notification_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ('notification' == $this->_request->getParam('type')) {
                if ($form->isValid($data)) {
                    $data = $form->getValues();
                    unset($data['save']);
                    unset($data['reset']);
                    unset($data['submit']);
                    foreach ($data as $k => $v) {
                        $config = Doctrine::getTable('Configuration')->findOneByName($k);
                        $config->value = $v;
                        $config->save();
                    }
                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $errorString = Fisma_Form_Manager::getErrors($form);
                    // Error message
                    $this->message("Unable to save Notifciation Policies:<br>$errorString", self::M_WARNING);
                }
            }
        }
        
        $columns = array('sender', 'subject', 'send_type', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password');
        foreach ($columns as $column) {
            $configs[$column] = Configuration::getConfig($column);
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
        $this->render();
    }

    /**
     *  Add/Update Privacy Policy configurations
     */
    public function privacyAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        
        $form = $this->getConfigForm('privacy_policy_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ('privacy' == $this->_request->getParam('type')) {
                if ($form->isValid($data)) {
                    $data = $form->getValues();
                    $config = Doctrine::getTable('Configuration')->findOneByName('privacy_policy');
                    $config->value = $data['privacy_policy'];
                    $config->save();
                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $errorString = Fisma_Form_Manager::getErrors($form);
                    // Error message
                    $this->message("Unable to save privacy policies:<br>$errorString", self::M_WARNING);
                }
            }
        }
        
        $form->setDefaults(array('privacy_policy' => Configuration::getConfig('privacy_policy')));
        $this->view->form = $form;
        $this->render();
    }
     
    /**
     *  Password Complexity Policy configurations
     */
    public function passwordAction()
    {
        Fisma_Acl::requirePrivilege('areas', 'configuration');
        
        $form = $this->getConfigForm('password_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ('password' == $this->_request->getParam('type')) {
                if ($form->isValid($data)) {
                    $values = $form->getValues();
                    unset($values['savePassword']);
                    unset($values['reset']);
                    unset($values['submit']);
                    foreach ($values as $k => $v) {
                        if ($k == Configuration::UNLOCK_DURATION) {
                            $v *=  60;//Convert to sencond
                        }
                        $config = Doctrine::getTable('Configuration')->findOneByName($k);
                        $config->value = $v;
                        $config->save();
                    }
                    $msg = 'Password Complexity Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $errorString = Fisma_Form_Manager::getErrors($form);
                    // Error message
                    $this->message("Unable to save password policies:<br>$errorString", self::M_WARNING);
                }
            }
        }
        
        $columns = array('failure_threshold' ,'unlock_enabled', 'unlock_duration', 'pass_expire',
                         'pass_warning', 'pass_uppercase', 'pass_lowercase',
                         'pass_numerical', 'pass_special', 'pass_min_length', 'pass_max_length');
        foreach ($columns as $column) {
            $configs[$column] = Configuration::getConfig($column);
        }
        $configs[Configuration::UNLOCK_DURATION] /= 60 ;//Convert to minutes
        $form->setDefaults($configs);
        $this->view->form = $form;
        $this->render();
    }
}
