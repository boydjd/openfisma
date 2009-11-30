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

/**
 * The configuration controller deals with displaying and updating system
 * configuration items through the user interface.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
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
        $contextSwitch = $this->_helper->contextSwitch();
        $contextSwitch->setAutoJsonSerialization(false)
                      ->addActionContext('test-email-config', 'json')
                      ->initContext();
    }

    /**
     * getConfigForm() - Returns the standard form for system configuration.
     *
     * @param string $formName The name of the form to load
     * @return Zend_Form
     */
    public function getConfigForm($formName)
    {
        // Load the form and populate the dynamic pull downs
        $form = Fisma_Form_Manager::loadForm($formName);
        $form = Fisma_Form_Manager::prepareForm($form);

        return $form;
    }

    /**
     * The default Action, handle the configuration updating.
     */
    public function indexAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        if ($this->_request->isPost()) {
            $type = $this->_request->getParam('type');
            $form = $this->getConfigForm($type . '_config');
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                $values = $form->getValues();
                foreach ($values as $k => $v) {
                    $config = Doctrine::getTable('Configuration')->findOneByName($k);
                    if ($config) {
                        $config->value = $v;
                        $config->save();
                    }
                }
                $msg = 'Configuration updated successfully';
                Notification::notify('CONFIGURATION_UPDATED', null, User::currentUser());
                $this->view->priorityMessenger($msg, 'notice');
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                $this->view->priorityMessenger("Unable to save configurations:<br>$errorString", 'warning');
            }
        }
        $this->render();
    }

    /**
     * Display and update the persistent configurations
     */
    public function generalAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');

        $form = $this->getConfigForm('general_config');
        $configs = Doctrine::getTable('Configuration')->findAll();

        $configArray = array();
        foreach ($configs as $config) {
            if (in_array($config->name, array(Configuration::EXPIRING_TS, Configuration::UNLOCK_DURATION))) {
                $config->value /= 60; //convert to minute from second
            }
            $configArray[$config->name] = $config->value;
        }
        $form->setDefaults($configArray);
        $this->view->generalConfig = $form;
    }

    /**
     * Get Ldap configuration list
     */
    public function ldaplistAction()
    {
        $ldaps = Doctrine::getTable('LdapConfig')->findAll();
        // @see http://jira.openfisma.org/browse/OFJ-30
        foreach ($ldaps as $ldap) {
            $ldap->password = '********';
            $ldap->url = $this->_makeLdapUrl($ldap);
        }
        $this->view->assign('ldaps', $ldaps->toArray());
    }

    /**
     * Just removed this from the view script and threw it in here so we could finish standards
     * 
     * @todo cleanup
     * 
     * @param LdapConfig $value An LdapConfig object to convert to URL form
     * @return string
     */
    private function makeLdapUrl($value)
    {
        $url = $value['useSsl'] ? "ldaps://" : "ldap://";
        if (!empty($value['username'])) {
            $url .= $value['username'];
            if (!empty($value['password'])) {
                $url .= ':' . $value['password'];
            }
            $url .= '@';
        }
        $url .= $value['host'];

        if (!empty($value['port'])) {
            $url .= ':' .$value['port'];
        }
        return $url;
    }

    /**
     *  Add/Update Technical Contact Information configurations
     */
    public function contactAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $form = $this->getConfigForm('contact_config');
        $columns = array('contact_name', 'contact_phone', 'contact_email', 'contact_subject');
        foreach ($columns as $column) {
            $configs[$column] = Configuration::getConfig($column);
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
    }

    /**
     *  Add/Update LDAP configurations
     */
    public function ldapupdateAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $form = $this->getConfigForm('ldap');
        $id = $this->_request->getParam('id');
        
        $ldap = new LdapConfig();
        if (!empty($id)) {
            $ldap = $ldap->getTable('LdapConfig')->find($id);
            // @see http://jira.openfisma.org/browse/OFJ-30
            $ldap->password = '********';
        }
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                $values = $form->getValues();
                $ldap->merge($values);
                $ldap->save();
                
                $msg = 'Configuration updated successfully';
                $this->view->priorityMessenger($msg, 'notice');
                $this->_redirect('/panel/config/');
                return;
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->view->priorityMessenger("Unable to save Ldap Configurations:<br>$errorString", 'warning');
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
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $id = $this->_request->getParam('id');
        Doctrine::getTable('LdapConfig')->find($id)->delete();
        $msg = "Ldap Server deleted successfully.";
        $this->view->priorityMessenger($msg, 'notice');
        $this->_forward('index');
    }

    /**
     * Validate the configuration
     *
     * This is only happens in ajax context
     */
    public function ldapvalidAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $form = $this->getConfigForm('ldap');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                try{
                    $data = $form->getValues();
                    unset($data['id']);
                    unset($data['SaveLdap']);
                    unset($data['Reset']);
                    if (empty($data['password'])) {
                        $dql = 'host = ? AND port = ? AND username = ?';
                        $params = array($data['host'], $data['port'], $data['username']);
                        $ldap = Doctrine::getTable('LdapConfig')
                                ->findByDql($dql, $params);
                        if (!empty($ldap[0])) {
                            $data['password'] = $ldap[0]->password;
                        }
                    }
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
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $form = $this->getConfigForm('notification_config');
        $columns = array('sender', 'subject', 'send_type', 'smtp_host', 'smtp_port',
                         'smtp_tls', 'smtp_username', 'smtp_password');
        foreach ($columns as $column) {
            $configs[$column] = Configuration::getConfig($column);
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
        $this->_helper->layout->setLayout('ajax');
    }

    /**
     * Validate the email configuration
     */
    public function testEmailConfigAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        // Load the form from notification_config.form file
        $form = $this->getConfigForm('notification_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                try{
                    $data = $form->getValues();
                    // Because user may not specified password for test on UI page,
                    // so have retrieve the saved one before if possible. 
                    if (empty($data['smtp_password'])) {
                        $password = Doctrine::getTable('Configuration')
                                    ->findByDql('name = ?', 'smtp_password');
                        if (!empty($password[0])) {
                            $data['smtp_password'] = $password[0]->value;
                        }
                    }
                    // The test e-mail template content
                    $mailContent = "This is a test e-mail from OpenFISMA. This is sent by the" 
                                 . " administrator to determine if the e-mail configuration is" 
                                 . " working correctly. There is no need to reply to this e-mail.";

                    // Define Zend_Mail() for sending test email
                    $mail = new Zend_Mail();
                    $mail->addTo($data['recipient']);
                    $mail->setFrom($data['sender']);
                    $mail->setSubject($data['subject']);
                    $mail->setBodyText($mailContent);

                    // Sendmail transport
                    if ($data['send_type'] == 'sendmail') {
                        $mail->send();
                    } elseif ($data['send_type'] == 'smtp') {
                        // SMTP transport
                        $emailConfig = array('auth'     => 'login',
                                             'username' => $data['smtp_username'],
                                             'password' => $data['smtp_password'],
                                             'port'     => $data['smtp_port']);
                        if (1 == $data['smtp_tls']) {
                            $emailConfig['ssl'] = 'tls';
                        }
                        $transport = new Zend_Mail_Transport_Smtp($data['smtp_host'], $emailConfig);
                        $mail->send($transport);
                    }
                    $type = 'message';
                    /** @todo english */
                    $msg  = 'Sent test email to ' . $data['recipient'] . ' successfully !';
                } catch (Zend_Mail_Exception $e) {
                    $type = 'warning';
                    $msg  = $e->getMessage();
                }
            } else {
                $type = 'warning';
                $msg  = Fisma_Form_Manager::getErrors($form);
            }
        } else {
            $type = 'warning';
            /** @todo english */
            $msg  = "Invalid Parameters";
        }
        echo Zend_Json::encode(array('msg' => $msg, 'type' => $type));
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     *  Add/Update Privacy Policy configurations
     */
    public function privacyAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $form = $this->getConfigForm('privacy_policy_config');
        $form->setDefaults(array('privacy_policy' => Configuration::getConfig('privacy_policy')));
        $this->view->form = $form;
    }
     
    /**
     *  Password Complexity Policy configurations
     */
    public function passwordAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $form = $this->getConfigForm('password_config');
        $columns = array('failure_threshold' ,'unlock_enabled', 'unlock_duration', 'pass_expire',
                         'pass_warning', 'pass_uppercase', 'pass_lowercase',
                         'pass_numerical', 'pass_special', 'pass_min_length', 'pass_max_length');
        foreach ($columns as $column) {
            $configs[$column] = Configuration::getConfig($column);
        }
        $configs[Configuration::UNLOCK_DURATION] /= 60 ;//Convert to minutes
        $form->setDefaults($configs);
        $this->view->form = $form;
    }
}
