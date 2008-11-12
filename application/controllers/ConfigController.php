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

    public function init()
    {
        parent::init();
        $this->_config = new Config();
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
        $form = Form_Manager::loadForm($formName);
        $form = Form_Manager::prepareForm($form);

        return $form;
    }

    public function indexAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->actionStack('password');
        $this->_helper->actionStack('notification');
        $this->_helper->actionStack('contact');
        $this->_helper->actionStack('view');
    }
    /**
     * Display and update the persistent configurations
     */
    public function viewAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        // Fill up with data
        $form = $this->getConfigForm('general_config');
        $ret = $this->_config->getList(array('key', 'value', 'description'));
        $configs = NULL;
        foreach ($ret as $item) {
            if (in_array($item['key'], array(Config::EXPIRING_TS, Config::UNLOCK_DURATION))) {
                $item['value'] /= 60; //convert to minute from second
            }
            if (in_array($item['key'], array(Config::USE_NOTIFICATION,
                Config::BEHAVIOR_RULE))) {
                $item['value'] = $item['description'];
            }

            $configs[$item['key']] = $item['value'];
        }

        // Update the change
        $form->setDefaults($configs);
        if ($this->_request->isPost()) {
            $configPost = $this->_request->getPost();
            if (isset($configPost[Config::MAX_ABSENT])) {
                if ($form->isValid($configPost)) {
                    $values = $form->getValues();
                    //array_intersect_key requires PHP > 5.1.0
                    $validVals = array(
                        Config::SYSTEM_NAME =>0,
                        Config::MAX_ABSENT  =>0,
                        Config::AUTH_TYPE   =>0,
                        Config::EXPIRING_TS =>0,
                        Config::USE_NOTIFICATION =>0,
                        Config::BEHAVIOR_RULE =>0,
                        Config::ROB_DURATION  =>0
                     );
                    $values = array_intersect_key($values, $validVals);
                    foreach ($values as $k => $v) {
                        //@todo check $values whether is modified
                        $records[] = $k;
                        $where = $this->_config->getAdapter()
                            ->quoteInto('`key` = ?', $k);
                        if (in_array($k, array(Config::EXPIRING_TS, Config::UNLOCK_DURATION))) {
                            $v *= 60; //convert to second
                        }
                        if (in_array($k, array(Config::USE_NOTIFICATION,Config::BEHAVIOR_RULE))) {
                            $this->_config->update(array('description' => $v), $where);
                        } else {
                            $this->_config
                                 ->update(array('value' => $v), $where);
                        }
                    }
                    $this->_notification
                         ->add(Notification::CONFIGURATION_MODIFIED,
                            $this->_me->account, $records);

                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $form->populate($configPost);
                }
            }
        }
        
        //get ldap configuration
        $ldaps = $this->_config->getLdap();
        $this->view->assign('ldaps', $ldaps);
        $this->view->generalConfig = $form;
        $this->render();
    }

    /**
     *  Add/Update Technical Contact Information configurations
     */
    public function contactAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        $config = new Config();
        $form = $this->getConfigForm('contact_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if (isset($data[Config::CONTACT_NAME])) {
                if ($form->isValid($data)) {
                    $data = $form->getValues();
                    unset($data['submit']);
                    unset($data['reset']);
                    foreach ($data as $k => $v) {
                        $where = $config->getAdapter()
                            ->quoteInto('`key` = ?', $k);
                        $config->update(array('value' => $v), $where);
                    }
                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $form->populate($data);
                }
            }
        }
        $items = $config->getList(array('key', 'value'));
        $configs = array();
        foreach ($items as $item) {
            $configs[$item['key']] = $item['value'];
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
    }


    /**
     *  Add/Update LDAP configurations
     */
    public function ldapupdateAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        $form = $this->getForm('ldap');
        $id = $this->_request->getParam('id');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                $values = $form->getValues();
                unset($values['SaveLdap']);
                unset($values['Reset']);
                $this->_config->saveLdap($values, $id);
                //$msg = 'Configuration updated successfully';
                //$this->message($msg, self::M_NOTICE);
                $this->_redirect('/panel/config/');
                return;
            }
        } else {
            //only represent the view
            if (!empty($id)) {
                $ldaps = $this->_config->getLdap($id);
                $form->setDefaults($ldaps[$id]);
            }
        }
        $this->view->form = $form;
    }

    /**
     * Delete a Ldap configuration
     */
    public function ldapdelAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        $id = $this->_request->getParam('id');
        $this->_config->delLdap($id);
        // @REVIEW
        $msg = "Ldap Server deleted successfully.";
        $this->message($msg, self::M_NOTICE);
        $this->_forward('view');
    }

    /**
     * Validate the configuration
     *
     * This is only happens in ajax context
     */
    public function ldapvalidAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        $form = $this->getForm('ldap');
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
            }
        } else {
            echo "<b>Invalid Parameters</b>";
        }
    }
    /**
     * Notification event system base setting
     *
     */
    public function notificationAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        $config = new Config();
        $form = $this->getConfigForm('notification_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if (isset($data[Config::SENDER])) {
                if ($form->isValid($data)) {
                    $data = $form->getValues();
                    unset($data['submit']);
                    unset($data['reset']);
                    foreach ($data as $k => $v) {
                        $where = $config->getAdapter()
                            ->quoteInto('`key` = ?', $k);
                        $config->update(array('value' => $v), $where);
                    }
                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $form->populate($data);
                }
            }
        } 
        $items = $config->getList(array('key', 'value'));
        $configs = array();
        foreach ($items as $item) {
            $configs[$item['key']] = $item['value'];
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
    }

    /**
     *  Add/Update Privacy Policy configurations
     */
    public function privacyAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        $config = new Config();
        $form = $this->getConfigForm('privacy_policy_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if (isset($data[Config::PRIVACY_POLICY])) {
                if ($form->isValid($data)) {
                    $data = $form->getValues();
                    $where = $config->getAdapter()
                        ->quoteInto('`key` = ?', 'privacy_policy');
                    $config->update(array('description' =>
                        $data['privacy_policy']), $where);
                    $msg = 'Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $form->populate($data);
                }
            }
        }
        $items = $config->getList(array('key', 'description'));
        $configs = array();
        foreach ($items as $item) {
            $configs[$item['key']] = $item['description'];
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
    }
     
    /**
     *  Password Complexity Policy configurations
     */
    public function passwordAction()
    {
        Config_Fisma::requirePrivilege('app_configuration', 'update');
        
        $config = new Config();
        $form = $this->getConfigForm('password_config');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if (isset($data[Config::PASS_UPPERCASE])) {
                if ($form->isValid($data)) {
                    $values = $form->getValues();
                    unset($values['submit']);
                    unset($values['reset']);
                    foreach ($values as $k => $v) {
                        if ($k == Config::UNLOCK_DURATION) {
                            $v *=  60;//Convert to sencond
                        }
                        $where = $config->getAdapter()
                            ->quoteInto('`key` = ?', $k);
                        $config->update(array('value' => $v), $where);
                    }
                    $msg = 'Password Complexity Configuration updated successfully';
                    $this->message($msg, self::M_NOTICE);
                } else {
                    $form->populate($data);
                }
            }
        }
        $items = $config->getList(array('key', 'value'));
        $configs = array();
        foreach ($items as $item) {
            if ($item['key'] == Config::UNLOCK_DURATION) {
                $item['value'] /= 60; //convert to minute from second
            }
            $configs[$item['key']] = $item['value'];
        }
        $form->setDefaults($configs);
        $this->view->form = $form;
        $this->render();
    }
}
