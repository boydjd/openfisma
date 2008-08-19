<?php
/**
 * ConfigController.php
 *
 * Config Controller for the system
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'config.php';
/**
 * Config Controller for the system
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=Licensea
 */
class ConfigController extends SecurityController
{

    /**
     * Display and edit the settings 
     */
    public function viewAction()
    {
        $config = new Config();
        $configItems = $config->getList(array('key' , 'value'));
        $configArray = NULL;
        foreach ($configItems as $item) {
            $configArray[$item['key']] = $item['value'];
        }
        $configPost = $this->_request->getPost();

        $general = $this->getForm('config');
        if ( isset($configPost['max_absent_time']) ) {
            // save the general configuration setting
            if ($general->isValid($configPost)) {
                $configPost = $general->getValues();
                unset($configPost['save']);
                unset($configPost['reset']);
                foreach ($configPost as $k => $v) {
                    $where = $config->getAdapter()->quoteInto('`key` = ?', $k);
                    $config->update(array('value' => $v), $where);
                }
                $msg = 'Configuration updated successfully';
                $this->message($msg, self::M_NOTICE);
            } else {
                $general->populate($configPost);
            }
        } else {
            $general->setDefaults($configArray);
        }

        //get ldap configuration
        $query = $config->getAdapter()->select()->from('ldap_config', '*')
                                                ->order('id ASC');
        $result = $config->getAdapter()->fetchAll($query);
        if ( !empty($result) ) {
            foreach ($result as $row) {
                $multiOptions[$row['group']][] = $row;
            }
            $this->view->assign('ldap_configs', $multiOptions);
        }
        $ldap = $this->getForm('ldap');

        $this->view->form = array('general' => $general,
                                  'ldap'    => $ldap);
        $this->render();
    }


    /**
     * Save the Ldap configuration setting
     */
    public function saveldapAction()
    {
        $keys = $this->_request->getPost('keys');
        $db = Zend_Registry::get('db');
        foreach ($keys as $group => $row) {
            foreach ($row as $k => $v) {
                $where = $db->quoteInto('`group` = ?', $group);
                $where .= ' AND ' . $db->quoteInto('`key` = ?', $k);
                $db->update('ldap_config', array('value'=>$v), $where);
            }
        }
        // @REVIEW
        $msg = 'Configuration updated successfully';
        $this->message($msg, self::M_NOTICE);
        $this->_forward('config', 'panel');
    }

    /**
     * Add Ldap Server
     *
     * @todo check filter
     */
    public function addldapAction()
    {
        $ldap = $this->_request->getPost();
        if ( empty($ldap['accountCanonicalForm']) ) {
            $ldap['accountCanonicalForm'] = 4;
        }
        if ( 'openldap' == $ldap['serverType'] ) {
            $ldap['bindRequiresDn'] = 1;
        }
        $form = $this->getForm('ldap');
        if ($form->isValid($ldap)) {
            $ldap = $form->getValues();
            $db = Zend_Registry::get('db');
            $query = $db->select()->from('ldap_config', '*')
                                  ->where('`key` = ?', 'host')
                                  ->where('value = ?', $ldap['host']);
            $result = $db->fetchRow($query);
            if ( !empty($result) ) {
                // @REVIEW Line 3
                $msg = 'The Server Host is exist,Choose another name';
                $model = self::M_WARNING;
            } else {
                $query = $db->select()->from('ldap_config',
                                             array('group'=>'MAX(`group`)'));
                $result = $db->fetchRow($query);
                $lastGroup = $result['group'];
                $newGroup = $lastGroup + 1;
                $errno = 0;
                foreach ($ldap as $k=>$v) {
                    if ( !empty($v) ) {
                        $data = array('group'=>$newGroup, 'key'=>$k,
                                      'value'=>$v, 'description'=>$k);
                        $ret = $db->insert('ldap_config', $data);
                        if ( $ret != 1 ) {
                            $errno++;
                        }
                    }
                }
                if ( $errno > 0 ) {
                    $msg = $errno . 'lines insert failed';
                    $model = self::M_WARNING;
                } else { 
                    $msg = 'Create new Ldap Server successfully.';
                    $model = self::M_NOTICE;
                }
            }
            $this->message($msg, $model);
        } else {
            $form->populate($ldap);
            $errors = $form->getMessages();
            $this->view->assign('ldap', $ldap);
            $this->view->assign('errors', $errors);
        }
        $this->_forward('config', 'panel');
    }

    /**
     * Delete a Ldap Server
     *
     */
    public function deleteldapAction()
    {
        $group = $this->_request->getParam('group');
        $db = Zend_Registry::get('db');
        $where = $db->quoteInto('`group` = ?', $group);
        $db->delete('ldap_config', $where);
        // @REVIEW
        $msg = "Ldap Server deleted successfully.";
        $this->message($msg, self::M_NOTICE);
        $this->_forward('config', 'panel');
    }
}
