<?php
/**
 * PanelController.php
 *
 * Panel Controller
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once CONTROLLERS . DS . 'SecurityController.php';
/**
 * Manipulate the menus
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class PanelController extends SecurityController
{
    /** Alias of dashboardAction
     */
    public function indexAction()
    {
        $this->_forward('dashboard');
    }
    public function headerAction()
    {
        $this->_helper->layout->assign('header',
            $this->view->render($this->_helper->viewRenderer->getViewScript()));
        $this->_helper->layout->setLayout('default');
    }
    public function dashboardAction()
    {
        $this->_helper->actionStack('index', 'Dashboard');
        $this->_helper->actionStack('header');
    }
    /** finding menu
     */
    public function findingAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub', 'searchbox');
        $this->_helper->actionStack($sub, 'Finding');
        $this->_helper->actionStack('header');
    }
    public function accountAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Account');
        $this->_helper->actionStack('searchbox', 'Account');
        $this->_helper->actionStack('header');
    }
    public function searchAction()
    {
        $req = $this->getRequest();
        $action = $req->getParam('obj');
        if ('finding' == $action) {
            $this->_helper->actionStack($action, 'Search');
            $this->_helper->actionStack('finding', 'Summary');
            $this->_helper->actionStack('header');
        }
    }
    public function remediationAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Remediation');
        $this->_helper->actionStack('header');
    }
    public function reportAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Report');
        $this->_helper->actionStack('header');
    }
    public function systemAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'System');
        $this->_helper->actionStack('searchbox', 'System');
        $this->_helper->actionStack('header');
    }
    public function productAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Product');
        $this->_helper->actionStack('searchbox', 'Product');
        $this->_helper->actionStack('header');
    }
    public function sysgroupAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Sysgroup');
        $this->_helper->actionStack('searchbox', 'Sysgroup');
        $this->_helper->actionStack('header');
    }
    public function sourceAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Source');
        $this->_helper->actionStack('searchbox', 'Source');
        $this->_helper->actionStack('header');
    }
    public function networkAction()
    {
        $sub = $this->_request->getParam('sub');
        $this->_helper->actionStack($sub, 'Network');
        $this->_helper->actionStack('searchbox', 'Network');
        $this->_helper->actionStack('header');
    }

    public function configAction()
    {
        $sub = $this->_request->getParam('sub', 'index');
        $this->_helper->actionStack('header');
        $this->_helper->actionStack($sub, 'Config');
    }
    public function userAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'User');
        $this->_helper->actionStack('header');
    }
    public function assetAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Asset');
        $this->_helper->actionStack('header');
    }
    public function roleAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Role');
        $this->_helper->actionStack('searchbox', 'Role');
        $this->_helper->actionStack('header');
    }
}
