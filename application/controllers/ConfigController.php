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
                    ->addActionContext('test-email-config', 'html')
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
        }
        $this->view->assign('ldaps', $ldaps->toArray());
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
                        $ldap = Doctrine::getTable('LdapConfig')
                                ->findByDql('host = ? AND port = ? AND username = ?',
                                        array($data['host'], $data['port'], $data['username']));
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
    }

    /**
     * Validate the email configuration
     *
     * This is only happens in ajax context
     */
    public function testEmailConfigAction()
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $form = $this->getConfigForm('notification_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                try{
                    $data = $form->getValues();
                    unset($data['id']);
                    unset($data['saveEmailConfig']);
                    unset($data['Reset']);
                    if (empty($data['smtp_password'])) {
                        $password = Doctrine::getTable('Configuration')
                                         ->findByDql('name = ?','smtp_password');
                        if (!empty($password[0])) {
                            $data['smtp_password'] = $password[0]->value;
                        }
                    }
                    if (empty($data['smtp_tls'])) {
                        $tls = Doctrine::getTable('Configuration')
                                    ->findByDql('name = ?','smtp_tls');
                        if (!empty($tls[0])) {
                            $data['smtp_tls'] = $tls[0]->value;
                        }
                    }                    
                    $mailContent="This is a test e-mail from OpenFISMA. This is sent by the" 
                                ." administrator to determine if the e-mail configuration is" 
                                ." working correctly. There is no need to reply to this e-mail.";
                    
                    if($data['send_type'] == 'sendmail') {
                        $mail = new Zend_Mail();
                        $mail->setBodyText($mailContent)
                             ->setFrom($data['sender'])
                             ->addTo($data['addto'])
                             ->setSubject($data['subject'])
                             ->send();
                    } elseif ($data['send_type'] == 'smtp') {
                        $emailconfig = array('auth' => 'login',
                                             'username' => $data['smtp_username'],
                                             'password' => $data['smtp_password'],
                                             'port' => $data['smtp_port']);
                       if ($data['smtp_tls'] == 1) {
                           $emailconfig['ssl'] = 'tls';
                       }
                        $transport = new Zend_Mail_Transport_Smtp($data['smtp_host'],$emailconfig);
                        
                        // send messages
                        $mail = new Zend_Mail();
                        $mail->addTo($data['addto']);
                        $mail->setFrom($data['sender']);
                        $mail->setSubject($data['subject']);
                        $mail->setBodyText($mailContent);
                        $mail->send($transport);
                    }
                    echo "Sent to '".$data['addto']."' test successfully !";
                } catch (Zend_Mail_Exception $e) {
                    echo $e->getMessage();
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
