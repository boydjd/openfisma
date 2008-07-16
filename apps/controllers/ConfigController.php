<?PHP
/**
 * @file ConfigController.php
 *
 * @description Config Controller
 *
 * @author     Jim <jimc@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'config.php';

class ConfigController extends SecurityController
{
    //config the time period for disabling inactive accounts
    public function viewAction(){
        $req = $this->getRequest();
        $config = new Config();
        $msg = null;
        $result = $config->fetchAll();
        $this->view->assign('msg',$msg);
        $this->view->assign('configs',$result->toArray());
        $this->render();
    }

    public function saveAction(){
        $req = $this->getRequest();
        $keys = $req->getPost('keys');
        $config = new config();

        foreach($keys as $k=>$v) {
            $where = $config->getAdapter()->quoteInto('`key` = ?', $k);
            $config->update(array('value'=>$v),$where);
        }

        $msg = 'modify disable period successfully!';
        $this->_forward('config','panel');
    }

}


