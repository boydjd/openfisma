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
 * The panel controller is the main controller for dispatching actions to other
 * controllers. It also includes actions for building the header and footer of
 * each page.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class PanelController extends SecurityController
{
    /**
     * Invoked before each Action
     * 
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_helper->viewRenderer->setNoRender();
    }
    
    /** 
     * Alias of dashboardAction
     * 
     * @return void
     */
    public function indexAction()
    {
        $this->_forward('dashboard');
    }

    /** 
     * The header of the page
     * 
     * @return void
     */
    public function headerAction()
    {
        $this->view->mainMenuBar = Fisma_Menu::getMainMenu();
        
        $this->_helper->layout->setLayout('layout');
        $this->_helper->actionStack('footer');
        $this->render('header', 'header');
    }

    /** 
     * The footer of the page
     * 
     * @return void
     */
    public function footerAction()
    {
        $this->render('footer', 'footer');           
    }

    /** 
     * Forward to dashboard Controller
     * 
     * @return void
     */
    public function dashboardAction()
    {
        $this->_helper->actionStack('index', 'Dashboard');
        $this->_helper->actionStack('header');
    }
    /** 
     * Forward to finding Controller
     * 
     * @return void
     */
    public function findingAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub', 'searchbox');
        $this->_helper->actionStack($sub, 'Finding');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to account Controller
     * 
     * @return void
     */
    public function accountAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'User');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to search Controller
     * 
     * @return void
     */
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

    /** 
     * Forward to remediation Controller
     * 
     * @return void
     */
    public function remediationAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Remediation');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to report Controller
     * 
     * @return void
     */
    public function reportAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Report');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to system Controller
     * 
     * @return void
     */
    public function systemAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'System');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to product Controller
     * 
     * @return void
     */
    public function productAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Product');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to organiztion Controller
     * 
     * @return void
     */
    public function organizationAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Organization');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to source Controller
     * 
     * @return void
     */
    public function sourceAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Source');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to network Controller
     * 
     * @return void
     */
    public function networkAction()
    {
        $sub = $this->_request->getParam('sub');
        $this->_helper->actionStack($sub, 'Network');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to config Controller
     * 
     * @return void
     */
    public function configAction()
    {
        $sub = $this->_request->getParam('sub', 'index');
        $this->_helper->actionStack('header');
        $this->_helper->actionStack($sub, 'Config');
    }

    /** 
     * Forward to user Controller
     * 
     * @return void
     */
    public function userAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'User');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to user Controller
     * 
     * @return void
     */
    public function systemDocumentAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'system-document');
        $this->_helper->actionStack('header');
    }

    /**
     * Forward to auth Controller
     * 
     * @return void
     */
    public function authAction()
    {
        $sub = $this->_request->getParam('sub');
        $this->_helper->actionStack($sub, 'Auth');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to asset Controller
     * 
     * @return void
     */
    public function assetAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Asset');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to role Controller
     * 
     * @return void
     */
    public function roleAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Role');
        $this->_helper->actionStack('header');
    }

    /** 
     * Forward to log Controller
     * 
     * @return void
     */
    public function logAction()
    {
        $req = $this->getRequest();
        $sub = $req->getParam('sub');
        $this->_helper->actionStack($sub, 'Log');
        $this->_helper->actionStack('header');
    }
}
