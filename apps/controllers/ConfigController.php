<?PHP
/**
 * @file ConfigController.php
 *
 * Config Controller for the system
 *
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'config.php';

class ConfigController extends SecurityController
{
    /**
     * Display and edit the settings 
    */
    public function viewAction(){
        $req = $this->getRequest();
        $config = new Config();
        $msg = null;
        $result = $config->fetchAll();
        $this->view->assign('msg',$msg);
        $this->view->assign('configs',$result->toArray());
        $this->render();
    }

    /** 
     * Save the configuration setting
    */
    public function saveAction(){
        $req = $this->getRequest();
        $keys = $req->getPost('keys');
        $config = new config();

        foreach($keys as $k=>$v) {
            $where = $config->getAdapter()->quoteInto('`key` = ?', $k);
            $config->update(array('value'=>$v),$where);
        }

        $msg = 'Configuration updated successfully';
        $this->_forward('config','panel');
    }

}


