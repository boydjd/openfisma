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
 * The panel controller is the main controller for dispatching actions to other
 * controllers. It also includes actions for building the header and footer of
 * each page.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class PanelController extends SecurityController
{
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_helper->viewRenderer->setNoRender();
    }
    /** Alias of dashboardAction
     */
    public function indexAction()
    {
        $this->_forward('dashboard');
    }
    public function headerAction()
    {
        $this->_helper->layout->setLayout('layout');
        $this->_helper->actionStack('footer');
        $this->render('header', 'header');
    }
    public function footerAction()
    {
        $this->render('footer', 'footer');           
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
    public function organizationAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Organization');
        $this->_helper->actionStack('searchbox', 'Organization');
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
    public function logAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Log');
        $this->_helper->actionStack('searchbox', 'Log');
        $this->_helper->actionStack('header');
    }
}
