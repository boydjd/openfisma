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
class ConfigController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * The Config module
     * 
     * @var Configuration
     */
    private $_config = null;

    /**
     * Initialize internal members.
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('validate-ldap', 'html')
                    ->initContext();
        $contextSwitch = $this->_helper->contextSwitch();
        $contextSwitch->setAutoJsonSerialization(false)
                      ->addActionContext('test-email-config', 'json')
                      ->addActionContext('set-module', 'json')
                      ->initContext();
    }
    
    /**
     * Hook into the pre-dispatch to do an ACL check
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('admin');
    }

    /**
     * Returns the standard form for system configuration
     *
     * @param string $formName The name of the form to load
     * @return Zend_Form The loaded form
     */
    private function _getConfigForm($formName)
    {
        // Load the form and populate the dynamic pull downs
        $form = Fisma_Zend_Form_Manager::loadForm($formName);
        $form = Fisma_Zend_Form_Manager::prepareForm($form);

        return $form;
    }

    /**
     * Display and update the persistent configurations
     * 
     * @return void
     */
    public function generalAction()
    {
        $form = $this->_getConfigForm('general_config');

        if ($this->getRequest()->isPost()) {
            $this->_saveConfigurationForm($form, $this->getRequest()->getPost());
            
            $this->_redirect('/config/general');
        }

        // Populate default values for non-submit button elements
        foreach ($form->getElements() as $element) {

            if ($element instanceof Zend_Form_Element_Submit) {
                continue;
            }
            
            $name = $element->getName();            
            $value = Fisma::configuration()->getConfig($name);

            /**
             * @todo More ugliness. Remove this.
             */
            if (in_array($name, array('session_inactivity_period'))) {
                $value /= 60; // Convert from seconds to minutes
            }
            
            $form->setDefault($name, $value);
        }
        
        $this->view->generalConfig = $form;
    }

    /**
     * Get Ldap configuration list
     * 
     * @return void
     */
    public function listLdapAction()
    {
        $ldapQuery = Doctrine_Query::create()
                     ->select('id, username, host, port, useSsl')
                     ->from('LdapConfig')
                     ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
                     
        $ldapConfigs = $ldapQuery->execute();

        // Construct the table data for the LDAP list, including edit and delete icons
        $ldapList = array();

        foreach ($ldapConfigs as $ldapConfig) {
            $url = $this->_makeLdapUrl(
                $ldapConfig['username'], 
                $ldapConfig['host'], 
                $ldapConfig['port'], 
                $ldapConfig['useSsl']
            );
            
            $editUrl = "/config/update-ldap/id/{$ldapConfig['id']}";
            $deleteUrl = "/config/delete-ldap/id/{$ldapConfig['id']}";
                      
            $ldapList[] = array($url, $editUrl, $deleteUrl);
        }

        $dataTable = new Fisma_Yui_DataTable_Local();
            
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Connection', false, 'YAHOO.widget.DataTable.formatText'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Edit', false, 'Fisma.TableFormat.editControl'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Delete', false, 'Fisma.TableFormat.deleteControl'))
                  ->setData($ldapList);

        $this->view->dataTable = $dataTable;
    }

    /**
     * Return a displayable LDAP URL
     * 
     * The password is masked so that it is not displayed to the end user in this view
     * 
     * @param string $username
     * @param string $host
     * @param string $port
     * @param string $useSsl
     * @return string
     */
    private function _makeLdapUrl($username, $host, $port, $useSsl)
    {
        $url = $useSsl ? "ldaps://" : "ldap://";

        if (!empty($username)) {
            $url .= "$username:********@";
        }

        $url .= $host;

        if (!empty($port)) {
            $url .= ":$port";
        }
        
        return $url;
    }

    /**
     * Add/Update Technical Contact Information configurations
     * 
     * @return void
     */
    public function contactAction()
    {        
        $form = $this->_getConfigForm('contact_config');

        if ($this->getRequest()->isPost()) {
            $this->_saveConfigurationForm($form, $this->getRequest()->getPost());
            
            $this->_redirect('/config/contact');
        }

        // Populate default values for non-submit button elements
        foreach ($form->getElements() as $element) {

            if ($element instanceof Zend_Form_Element_Submit) {
                continue;
            }
            
            $name = $element->getName();            
            $value = Fisma::configuration()->getConfig($name);
            
            $form->setDefault($name, $value);
        }

        $this->view->form = $form;
    }

    /**
     * Add/Update LDAP configurations
     * 
     * @return void
     */
    public function updateLdapAction()
    {        
        $form = $this->_getConfigForm('ldap');
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
                $this->_redirect('/config/list-ldap');
                return;
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
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
     * 
     * @return void
     */
    public function deleteLdapAction()
    {        
        $id = $this->_request->getParam('id');
        Doctrine::getTable('LdapConfig')->find($id)->delete();
        $msg = "Ldap Server deleted successfully.";
        $this->view->priorityMessenger($msg, 'notice');
        $this->_redirect('/config/list-ldap');
    }

    /**
     * Validate the configuration
     * 
     * This is only happens in ajax context
     * 
     * @return void
     */
    public function validateLdapAction()
    {        
        $form = $this->_getConfigForm('ldap');
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
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                echo $errorString;
            }
        } else {
            echo "<b>Invalid Parameters</b>";
        }
        $this->_helper->viewRenderer->setNoRender();

    }

    /**
     * Display module status and controls to change module status
     */
    public function modulesAction()
    {
        $moduleQuery = Doctrine_Query::create()
                       ->from('Module m')
                       ->orderBy('m.name')
                       ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        
        $modules = $moduleQuery->execute();        

        foreach ($modules as &$module) {

            // If a module can be disabled, then create a switch button to control its ON/OFF state
            if ($module['canBeDisabled']) {

                // Need a javascript/DOM safe ID to represent this switch
                $id = preg_replace('/[^A-Za-z0-9]+/', '_', $module['name']);
                
                $module['control'] = new Fisma_Js_SwitchButton($id, 
                                                               $module['enabled'], 
                                                               'Fisma.Module.handleSwitchButtonStateChange',
                                                               array('id' => $module['id']));
            } else {
                $module['control'] = 'This module cannot be disabled.';
            }
        }
        
        $this->view->modules = $modules;
    }
    
    /**
     * Update a module's status.
     * 
     * This is called asynchronously and returns a JSON response
     */
    public function setModuleAction()
    {        
        $response = new Fisma_AsyncResponse();

        try {            
            // Load module object
            $moduleId = $this->getRequest()->getParam('id');
            
            if (empty($moduleId)) {
                throw new Fisma_Zend_Exception('ID parameter is required');
            }
            
            $module = Doctrine::getTable('Module')->find($moduleId);
            
            if (!$module) {
                throw new Fisma_Zend_Exception("Module with id '$moduleId' not found");
            }

            // Handle the 'enabled' parameter, which is a string value either 'true' or 'false'
            $enabled = $this->getRequest()->getParam('enabled');
            
            if ('true' == $enabled) {
                $module->enabled = true;
            } elseif ('false' == $enabled) {
                $module->enabled = false;
            } else {
                throw new Fisma_Zend_Exception("Invalid enabled state: $enabled");
            }

            $module->save();
            
        } catch (Fisma_User_Exception $userException) {
            $response->fail($userException->getMessage());
        } catch (Fisma_Zend_Exception_InvalidPrivilege $invalidPrivilege) {
            $response->fail('User is not authorized to perform this action.');
        }
        
        $this->view->response = $response;
    }

    /**
     * Generic save method.
     * 
     * This will validate and save post variables into the system configuration
     * 
     * @param Zend_Form $form The form which was submitted
     * @param array $post Posted variables
     */
    private function _saveConfigurationForm($form, $post)
    {
        if ($form->isValid($post)) {        
            $values = $form->getValues();
        
            foreach ($values as $item => &$value) {
            
                /**
                 * @todo this needs to be cleaned up
                 */
                if ('session_inactivity_period' == $item) {
                    $value *= 60; // convert minutes to seconds
                }

                Fisma::configuration()->setConfig($item, $value);
            }

            $this->view->priorityMessenger('Configuration updated successfully', 'notice');
        } else {
            $errorString = Fisma_Zend_Form_Manager::getErrors($form);
            $this->view->priorityMessenger("Unable to save configurations:<br>$errorString", 'warning');
        }
    }
    
    /**
     * Email event system base setting
     * 
     * @return void
     */
    public function emailAction()
    {
        $form = $this->_getConfigForm('email_config');
        
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();
            
            /**
             * @todo The wiring on this is screwy because the test email panel uses the same form to submit, even though
             * its submitting to a different action and submitting different data! This needs fixing!
             */
            unset($post['recipient']);
            $form->removeElement('recipient');

            $this->_saveConfigurationForm($form, $post);
            
            $this->_redirect('/config/email');
        }
        
        $configurations = array('sender', 
                                'subject', 
                                'send_type', 
                                'smtp_host', 
                                'smtp_port',
                                'smtp_tls', 
                                'smtp_username', 
                                'smtp_password');
                         
        foreach ($configurations as $configuration) {
            $form->setDefault($configuration, Fisma::configuration()->getConfig($configuration));
        }
        
        $this->view->form = $form;
    }

    /**
     * Validate the email configuration
     * 
     * @return void
     */
    public function testEmailConfigAction()
    {        
        // Load the form from notification_config.form file
        $form = $this->_getConfigForm('email_config');
        if ($this->_request->isPost()) {
            $postEmailConfigValues = $this->_request->getPost();
            if ($form->isValid($postEmailConfigValues)) {
                try{
                    $postEmailConfigValues = $form->getValues();
                    // Because user may not specified password for test on UI page,
                    // so have retrieve the saved one before if possible. 
                    if (empty($postEmailConfigValues['smtp_password'])) {
                        $password = Doctrine::getTable('Configuration')
                                    ->findByDql('name = ?', 'smtp_password');
                        if (!empty($password[0])) {
                            $postEmailConfigValues['smtp_password'] = $password[0]->value;
                        }
                    }
                    // The test e-mail template content
                    $mailContent = "This is a test e-mail from OpenFISMA. This is sent by the" 
                                 . " administrator to determine if the e-mail configuration is" 
                                 . " working correctly. There is no need to reply to this e-mail.";

                    // Define Zend_Mail() for sending test email
                    $mail = new Zend_Mail();
                    $mail->addTo($postEmailConfigValues['recipient']);
                    $mail->setFrom($postEmailConfigValues['sender']);
                    $mail->setSubject($postEmailConfigValues['subject']);
                    $mail->setBodyText($mailContent);

                    // Sendmail transport
                    if ($postEmailConfigValues['send_type'] == 'sendmail') {
                        $mail->send();
                    } elseif ($postEmailConfigValues['send_type'] == 'smtp') {
                        // SMTP transport
                        $emailConfig = array('auth'     => 'login',
                                             'username' => $postEmailConfigValues['smtp_username'],
                                             'password' => $postEmailConfigValues['smtp_password'],
                                             'port'     => $postEmailConfigValues['smtp_port']);
                        if (1 == $postEmailConfigValues['smtp_tls']) {
                            $emailConfig['ssl'] = 'tls';
                        }
                        $transport = new Zend_Mail_Transport_Smtp($postEmailConfigValues['smtp_host'], $emailConfig);
                        $mail->send($transport);
                    }
                    $type = 'message';
                    /** @todo english */
                    $msg  = 'Sent test email to ' . $postEmailConfigValues['recipient'] . ' successfully !';
                } catch (Zend_Mail_Exception $e) {
                    $type = 'warning';
                    $msg  = $e->getMessage();
                }
            } else {
                $type = 'warning';
                $msg  = Fisma_Zend_Form_Manager::getErrors($form);
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
     * Add/Update Privacy Policy configurations
     * 
     * @return void
     */
    public function privacyAction()
    {        
        $form = $this->_getConfigForm('privacy_policy_config');

        if ($this->getRequest()->isPost()) {
            $this->_saveConfigurationForm($form, $this->getRequest()->getPost());
            
            $this->_redirect('/config/privacy');
        }

        // Populate default values for non-submit button elements
        foreach ($form->getElements() as $element) {

            if ($element instanceof Zend_Form_Element_Submit) {
                continue;
            }
            
            $name = $element->getName();            
            $value = Fisma::configuration()->getConfig($name);
            
            $form->setDefault($name, $value);
        }

        $this->view->form = $form;
    }
     
    /**
     * Password Complexity Policy configurations
     * 
     * @return void
     */
    public function passwordAction()
    {        
        $form = $this->_getConfigForm('password_config');

        if ($this->getRequest()->isPost()) {
            $this->_saveConfigurationForm($form, $this->getRequest()->getPost());
            
            $this->_redirect('/config/password');
        }

        // Populate default values for non-submit button elements
        foreach ($form->getElements() as $element) {

            if ($element instanceof Zend_Form_Element_Submit) {
                continue;
            }
            
            $name = $element->getName();            
            $value = Fisma::configuration()->getConfig($name);

            $form->setDefault($name, $value);
        }

        $this->view->form = $form;
    }
}
