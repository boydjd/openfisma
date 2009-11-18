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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * Creates the system menubar object in JSON format for use with YUI.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Controller
 * @version    $Id$
 */
class MenuController extends SecurityController
{
    /**
     * Setup contexts for this controller
     */
    function init() 
    {
        parent::init();
        $this->_helper->contextSwitch()
                      ->addActionContext('main', 'json')
                      ->initContext();
    }
    
    /**
     * Creates the main menu. Render the menu bar to a JSON object. This action is called
     * asynchronously by YUI.
     */
    function mainAction()
    {
        $menubar = new Fisma_Yui_MenuBar();
        
        // Tell the browser to cache the menu bar
        $this->getResponse()->setHeader('Cache-Control', 'max-age=3600', true);
        $this->getResponse()->setHeader('Last-Modified', 'Thu, 01 Dec 1984 16:00:00 GMT', true);
        $this->getResponse()->setHeader('Expires', 'Thu, 01 Dec 2100 16:00:00 GMT', true);
        $this->getResponse()->setHeader('Pragma', null, true);
        
        if (Fisma_Acl::hasPrivilege('area', 'dashboard')) {
            $dashboard = new Fisma_Yui_MenuItem('Dashboard', '/panel/dashboard');
            $menubar->add($dashboard);
        }

        if (Fisma_Acl::hasPrivilege('finding', 'read', '*')) {
            $findings = new Fisma_Yui_Menu('Findings');
            
            $findings->add(new Fisma_Yui_MenuItem('Summary', '/panel/remediation/sub/summary'));
            $findings->add(new Fisma_Yui_MenuItem('Search', '/panel/remediation/sub/searchbox'));

            if (Fisma_Acl::hasPrivilege('finding', 'create', '*')) {
                $findings->add(new Fisma_Yui_MenuItem('Create New Finding', '/panel/finding/sub/create'));
            }
            
            if (Fisma_Acl::hasPrivilege('finding', 'inject', '*')) {
                $findings->add(new Fisma_Yui_MenuItem('Upload Spreadsheet', '/panel/finding/sub/injection'));
                $findings->add(new Fisma_Yui_MenuItem('Upload Scan Results', '/panel/finding/sub/plugin'));
            }
            
            if (Fisma_Acl::hasPrivilege('finding', 'approve', '*')) {
                $findings->add(new Fisma_Yui_MenuItem('Approve Pending Findings', '/panel/finding/sub/approve'));
            }
            
            $menubar->add($findings);
        }

        if (Fisma_Acl::hasPrivilege('system', 'read', '*')) {
            $systems = new Fisma_Yui_Menu('System Inventory');

            $systems->add(new Fisma_Yui_MenuItem('Systems', '/panel/system/sub/list'));
            
            if (Fisma_Acl::hasPrivilege('asset', 'read', '*')) {
                $systems->add(new Fisma_Yui_MenuItem('Assets', '/panel/asset/sub/list'));
            }

            if (Fisma_Acl::hasPrivilege('organization', 'read')) {
                $systems->add(new Fisma_Yui_MenuItem('Organizations', '/panel/organization/sub/tree'));
            }

            $systems->add(new Fisma_Yui_MenuItem('Documentation', '/panel/system-document/sub/list'));
            
            $menubar->add($systems);
        }
        
        if (Fisma_Acl::hasPrivilege('area','reports')) {
            $reports = new Fisma_Yui_Menu('Reports');
            
            $reports->add(new Fisma_Yui_MenuItem('FISMA Report', '/panel/report/sub/fisma'));
            //$reports->add(new Fisma_Yui_MenuItem('Generate System RAFs', '/panel/report/sub/rafs'));
            $reports->add(new Fisma_Yui_MenuItem('Overdue Report', '/panel/report/sub/overdue'));
            $reports->add(new Fisma_Yui_MenuItem('Plug-in Reports', '/panel/report/sub/plugin'));
            
            $menubar->add($reports);
        }
        
        if (Fisma_Acl::hasPrivilege('area','admin')) {
            $admin = new Fisma_Yui_Menu('Administration');
            
            if (Fisma_Acl::hasPrivilege('area', 'configuration')) {
                $admin->add(new Fisma_Yui_MenuItem('Configuration', '/panel/config'));
            }

            if (Fisma_Acl::hasPrivilege('source', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Finding Sources', '/panel/source/sub/list'));
            }

            if (Fisma_Acl::hasPrivilege('network', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Networks', '/panel/network/sub/list'));
            }

            if (Fisma_Acl::hasPrivilege('product', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Products', '/panel/product/sub/list'));
            }

            if (Fisma_Acl::hasPrivilege('role', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Roles', '/panel/role/sub/list'));
            }

            if (Fisma_Acl::hasPrivilege('user', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Users', '/panel/account/sub/list'));
            }
            
            $menubar->add($admin);
        }
        
        $preferences = new Fisma_Yui_Menu('User Preferences');
        
        $preferences->add(new Fisma_Yui_MenuItem('Profile', '/panel/user/sub/profile'));
        if ('database' == Configuration::getConfig('auth_type')
            || 'root' == User::currentUser()->username) {
            $preferences->add(new Fisma_Yui_MenuItem('Change Password', '/panel/user/sub/password'));
        }
        $preferences->add(new Fisma_Yui_MenuItem('E-mail Notifications', '/panel/user/sub/notification'));
        
        $menubar->add($preferences);

        if (Fisma::debug()) {
            $debug = new Fisma_Yui_Menu('Debug');
            
            $debug->add(new Fisma_Yui_MenuItem('PHP Info', '/debug/phpinfo'));
            
            $menubar->add($debug);
        }

        $this->view->menubar = $menubar->getMenus();
    }
}
