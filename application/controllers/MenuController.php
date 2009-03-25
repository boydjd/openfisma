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
        $menubar = new Yui_MenuBar();
        
        if ($this->view->acl->isAllowed('dashboard', 'read')) {
            $dashboard = new Yui_MenuItem('Dashboard', '/panel/dashboard');
            $menubar->add($dashboard);
        }

        if($this->view->acl->isAllowed('asset','read')) {
            $assets = new Yui_MenuItem('Assets', '/panel/asset/sub/searchbox/s/search');
            $menubar->add($assets);
        }

        if($this->view->acl->isAllowed('finding','read')) {
            $findings = new Yui_Menu('Findings');
            
            if($this->view->acl->isAllowed('remediation', 'read')) {
                $findings->add(new Yui_MenuItem('Summary', '/panel/remediation/sub/summary'));
                $findings->add(new Yui_MenuItem('Search', '/panel/remediation/sub/searchbox'));
            }

            if($this->view->acl->isAllowed('finding', 'create')) {
                $findings->add(new Yui_MenuItem('Create New Finding', '/panel/finding/sub/create'));
            }
            
            if($this->view->acl->isAllowed('finding', 'inject')) {
                $findings->add(new Yui_MenuItem('Upload Spreadsheet', '/panel/finding/sub/injection'));
                $findings->add(new Yui_MenuItem('Upload Scan Results', '/panel/finding/sub/plugin'));
            }
            
            if($this->view->acl->isAllowed('finding', 'approve')) {
                $findings->add(new Yui_MenuItem('Approve Pending Findings', '/panel/finding/sub/approve'));
            }
            
            $menubar->add($findings);
        }

        if($this->view->acl->isAllowed('report','read')) {
            $reports = new Yui_Menu('Reports');
            
            if($this->view->acl->isAllowed('report', 'generate_poam_report')) {
                $reports->add(new Yui_MenuItem('POA&M Report', '/panel/report/sub/poam'));
            }

            if($this->view->acl->isAllowed('report', 'generate_fisma_report')) {
                $reports->add(new Yui_MenuItem('FISMA Report', '/panel/report/sub/fisma'));
            }

            if($this->view->acl->isAllowed('report', 'generate_general_report')) {
                $reports->add(new Yui_MenuItem('General Report', '/panel/report/sub/general'));
            }

            if($this->view->acl->isAllowed('report', 'generate_system_rafs')) {
                $reports->add(new Yui_MenuItem('Generate System RAFs', '/panel/report/sub/rafs'));
            }

            if($this->view->acl->isAllowed('report', 'generate_overdue_report')) {
                $reports->add(new Yui_MenuItem('Overdue Report', '/panel/report/sub/overdue'));
            }

            $reports->add(new Yui_MenuItem('Plug-in Reports', '/panel/report/sub/plugin'));
            
            $menubar->add($reports);
        }
        
        if($this->view->acl->isAllowed('report','read')) {
            $admin = new Yui_Menu('Administration');
            
            if($this->view->acl->isAllowed('app_configuration', 'update')) {
                $admin->add(new Yui_MenuItem('Configuration', '/panel/config'));
            }

            if($this->view->acl->isAllowed('admin_sources', 'read')) {
                $admin->add(new Yui_MenuItem('Finding Sources', '/panel/source/sub/list'));
            }

            if($this->view->acl->isAllowed('admin_networks', 'read')) {
                $admin->add(new Yui_MenuItem('Networks', '/panel/network/sub/list'));
            }

            if($this->view->acl->isAllowed('admin_products', 'read')) {
                $admin->add(new Yui_MenuItem('Products', '/panel/product/sub/list'));
            }

            if($this->view->acl->isAllowed('admin_roles', 'read')) {
                $admin->add(new Yui_MenuItem('Roles', '/panel/role/sub/list'));
            }

            if($this->view->acl->isAllowed('admin_organizations', 'read')) {
                $admin->add(new Yui_MenuItem('Organizations', '/panel/organization/sub/list'));
            }

            if($this->view->acl->isAllowed('admin_systems', 'read')) {
                $admin->add(new Yui_MenuItem('Systems', '/panel/system/sub/list'));
            }

            if($this->view->acl->isAllowed('admin_users', 'read')) {
                $admin->add(new Yui_MenuItem('Users', '/panel/account/sub/list'));
            }
            
            $menubar->add($admin);
        }
        
        $preferences = new Yui_Menu('User Preferences');
        
        $preferences->add(new Yui_MenuItem('Profile', '/panel/user/sub/profile'));
        $preferences->add(new Yui_MenuItem('Change Password', '/panel/user/sub/password'));
        $preferences->add(new Yui_MenuItem('E-mail Notifications', '/panel/user/sub/notifications'));
        
        $menubar->add($preferences);

        $this->view->menubar = $menubar->getMenus();
    }
}
