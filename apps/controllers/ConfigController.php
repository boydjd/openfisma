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
require_once 'Zend/Form/Element/Hidden.php';
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
     * The Config module 
     */
    private $_config = null;

    public function init()
    {
        parent::init();
        $this->_config = new Config();
    }

    /**
     * Display and update the persistent configurations
     */
    public function viewAction()
    {
        // Fill up with data
        $general = $this->getForm('config');
        $ret = $this->_config->getList(array('key' , 'value'));
        $configs = NULL;
        foreach ($ret as $item) {
            if ($item['key'] == Config::EXPIRING_TS) {
                $item['value'] /= 3600; //convert to hour from second
            }
            $configs[$item['key']] = $item['value'];
        }

        // Update the change
        $general->setDefaults($configs);
        if ($this->_request->isPost()) {
            $configPost = $this->_request->getPost();
            if ($general->isValid($configPost)) {
                $values = $general->getValues();
                //array_intersect_key requires PHP > 5.1.0
                $validVals = array(
                    Config::MAX_ABSENT  =>0,
                    Config::AUTH_TYPE   =>0,
                    Config::F_THRESHOLD =>0,
                    Config::EXPIRING_TS =>0 
                 );
                $values = array_intersect_key($values, $validVals);
                foreach ($values as $k => $v) {
                    $where = $this->_config->getAdapter()->quoteInto('`key` = ?', $k);
                    if ($k == Config::EXPIRING_TS) {
                        $v *= 3600; //convert to second
                    }
                    $this->_config->update(array('value' => $v), $where);
                }
                $msg = 'Configuration updated successfully';
                $this->message($msg, self::M_NOTICE);
            } else {
                $general->populate($configPost);
            }
        }

        //get ldap configuration
        $ldaps = $this->_config->getLdap();
        $this->view->assign('ldaps', $ldaps);
        $this->view->generalform = $general;
        $this->render();
    }

    /**
     *  Add/Update LDAP configurations
     */
    public function ldapupdateAction()
    {
        $form = $this->getForm('ldap');
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            if ($form->isValid($data)) {
                $values = $form->getValues();
                unset($values['SaveLdap']);
                unset($values['Reset']);
                $this->_config->saveLdap($values);
                //$msg = 'Configuration updated successfully';
                //$this->message($msg, self::M_NOTICE);
                $this->_redirect('/panel/config/');
                return;
            }
        } else {
            //only represent the view
            $id = $this->_request->getParam('id');
            if (!empty($id)) {
                $ldaps = $this->_config->getLdap($id);
                $form->setDefaults($ldaps[0]);
            }
        }
        $this->view->form = $form;
        $this->render();
    }

    /**
     * Delete a Ldap configuration
     */
    public function ldapdelAction()
    {
        $id = $this->_request->getParam('id');
        $this->_config->delLdap($id);
        // @REVIEW
        $msg = "Ldap Server deleted successfully.";
        $this->message($msg, self::M_NOTICE);
        $this->_forward('view');
    }
}
