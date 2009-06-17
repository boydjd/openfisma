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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * Creates the system menubar object in JSON format for use with YUI
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
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
        
        if (Fisma_Acl::hasPrivilege('areas', 'dashboard')) {
            $dashboard = new Fisma_Yui_MenuItem('Dashboard', '/panel/dashboard');
            $menubar->add($dashboard);
        }

        if(Fisma_Acl::hasPrivilege('assets', 'read', '*')) {
            $assets = new Fisma_Yui_MenuItem('Assets', '/panel/asset/sub/list');
            $menubar->add($assets);
        }

        if(Fisma_Acl::hasPrivilege('findings', 'read', '*')) {
            $findings = new Fisma_Yui_Menu('Findings');
            
            $findings->add(new Fisma_Yui_MenuItem('Summary', '/panel/remediation/sub/summary'));
            $findings->add(new Fisma_Yui_MenuItem('Search', '/panel/remediation/sub/searchbox'));

            if(Fisma_Acl::hasPrivilege('findings', 'create', '*')) {
                $findings->add(new Fisma_Yui_MenuItem('Create New Finding', '/panel/finding/sub/create'));
            }
            
            if(Fisma_Acl::hasPrivilege('findings', 'inject', '*')) {
                $findings->add(new Fisma_Yui_MenuItem('Upload Spreadsheet', '/panel/finding/sub/injection'));
                $findings->add(new Fisma_Yui_MenuItem('Upload Scan Results', '/panel/finding/sub/plugin'));
            }
            
            if(Fisma_Acl::hasPrivilege('findings', 'approve', '*')) {
                $findings->add(new Fisma_Yui_MenuItem('Approve Pending Findings', '/panel/finding/sub/approve'));
            }
            
            $menubar->add($findings);
        }

        if(Fisma_Acl::hasPrivilege('areas','reports')) {
            $reports = new Fisma_Yui_Menu('Reports');
            
            //POA&M report should probably be removed. The search feature does all the same things.
            //$reports->add(new Fisma_Yui_MenuItem('POA&M Report', '/panel/report/sub/poam'));
            $reports->add(new Fisma_Yui_MenuItem('FISMA Report', '/panel/report/sub/fisma'));
            //This section needs a huge overhaul
            //$reports->add(new Fisma_Yui_MenuItem('General Report', '/panel/report/sub/general'));
            $reports->add(new Fisma_Yui_MenuItem('Generate System RAFs', '/panel/report/sub/rafs'));
            $reports->add(new Fisma_Yui_MenuItem('Overdue Report', '/panel/report/sub/overdue'));
            $reports->add(new Fisma_Yui_MenuItem('Plug-in Reports', '/panel/report/sub/plugin'));
            
            $menubar->add($reports);
        }
        
        if(Fisma_Acl::hasPrivilege('areas','admin')) {
            $admin = new Fisma_Yui_Menu('Administration');
            
            if(Fisma_Acl::hasPrivilege('areas', 'configuration')) {
                $admin->add(new Fisma_Yui_MenuItem('Configuration', '/panel/config'));
            }

            if(Fisma_Acl::hasPrivilege('finding_sources', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Finding Sources', '/panel/source/sub/list'));
            }

            if(Fisma_Acl::hasPrivilege('networks', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Networks', '/panel/network/sub/list'));
            }

            if(Fisma_Acl::hasPrivilege('products', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Products', '/panel/product/sub/list'));
            }

            if(Fisma_Acl::hasPrivilege('roles', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Roles', '/panel/role/sub/list'));
            }

            if(Fisma_Acl::hasPrivilege('organizations', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Organizations', '/panel/organization/sub/list'));
            }

            if(Fisma_Acl::hasPrivilege('systems', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Systems', '/panel/system/sub/list'));
            }

            if(Fisma_Acl::hasPrivilege('users', 'read')) {
                $admin->add(new Fisma_Yui_MenuItem('Users', '/panel/account/sub/list'));
            }
            
            $menubar->add($admin);
        }
        
        $preferences = new Fisma_Yui_Menu('User Preferences');
        
        $preferences->add(new Fisma_Yui_MenuItem('Profile', '/panel/user/sub/profile'));
        $preferences->add(new Fisma_Yui_MenuItem('Change Password', '/panel/user/sub/password'));
        $preferences->add(new Fisma_Yui_MenuItem('E-mail Notifications', '/panel/user/sub/notifications'));
        
        $menubar->add($preferences);

        $this->view->menubar = $menubar->getMenus();
    }
}
