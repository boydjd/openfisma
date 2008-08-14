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
        $result = $config->fetchAll();
        $this->view->assign('general_configs', $result->toArray());
        
        $query = $config->getAdapter()->select()->from('ldap_config', '*')
                                                ->order('id ASC');
        $result = $config->getAdapter()->fetchAll($query);
        if ( !empty($result) ) {
            foreach ($result as $row) {
                $multiOptions[$row['group']][] = $row;
            }
            $this->view->assign('ldap_configs', $multiOptions);
        }
        $this->view->assign('configs', $result);
        $this->render();
    }
    /** 
     * Save the configuration setting
     *
     */
    public function saveAction()
    {
        $keys = $this->_request->getPost('keys');
        $config = new config();
        foreach ($keys as $k => $v) {
            if ( 'auth_type' == $k && 'ldap' == $v ) {
                $query = $config->getAdapter()->select()
                                              ->from('ldap_config', '*');
                $result = $config->getAdapter()->fetchCol($query);
                if ( empty($result) ) {
                    $flag = true;
                    continue;
                }
            }
            $where = $config->getAdapter()->quoteInto('`key` = ?', $k);
            $config->update(array('value' => $v), $where);
        }
        // @REVIEW Line 2
        $msg = 'Configuration updated successfully';
        $model = self::M_NOTICE;
        if ( isset($flag) &&  true == $flag ) {
            $msg .=' Except authentication type,Please create a new Ldap Server First!';
            $model = self::M_WARNING;
        }
        $this->message($msg, $model);
        $this->_forward('config', 'panel');
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
        $ldap = $this->_request->getPost('ldap');
        $db = Zend_Registry::get('db');
        $query = $db->select()->from('ldap_config', '*')
                              ->where('`key` = ?', 'name')
                              ->where('value = ?', $ldap['name']);
        $result = $db->fetchRow($query);
        if ( !empty($result) ) {
            // @REVIEW Line 3
            $msg = 'The Server Name is exist,Choose another name';
            $model = self::M_WARNING;
        } else { 
            $query = $db->select()->from('ldap_config',
                                         array('group'=>'MAX(`group`)'));
            $result = $db->fetchRow($query);
            $lastGroup = $result['group'];
            $newGroup = $lastGroup + 1;
            $errno = 0;
            foreach ($ldap as $k=>$v) {
                $data = array('group'=>$newGroup, 'key'=>$k, 'value'=>$v,
                              'description'=>$k);
                $ret = $db->insert('ldap_config', $data);
                if ( $ret != 1 ) {
                    $errno++;
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
