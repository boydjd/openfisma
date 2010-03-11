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
 * Menu building for OpenFISMA
 * 
 * @author     Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Menu
 * @version    $Id$
 */
class Fisma_Menu
{
    /**
     * Constructs a main menu for OpenFISMA.
     * 
     * @return Fisma_Yui_MenuBar The assembled Fisma YUI menu bar object
     */
    public static function getMainMenu()
    {
        $mainMenuBar = new Fisma_Yui_MenuBar();

        if (Fisma_Acl::hasArea('dashboard')) {
            $dashboard = new Fisma_Yui_MenuItem('Dashboard', '/panel/dashboard');
            $mainMenuBar->add($dashboard);
        }

        if (Fisma_Acl::hasPrivilegeForClass('read', 'Finding')) {
            $findings = new Fisma_Yui_Menu('Findings');
            
            $findings->add(new Fisma_Yui_MenuItem('Summary', '/panel/remediation/sub/summary'));
            $findings->add(new Fisma_Yui_MenuItem('Search', '/panel/remediation/sub/searchbox'));

            if (Fisma_Acl::hasPrivilegeForClass('create', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Create New Finding', '/panel/finding/sub/create'));
            }
            
            if (Fisma_Acl::hasPrivilegeForClass('inject', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Upload Spreadsheet', '/panel/finding/sub/injection'));
                $findings->add(new Fisma_Yui_MenuItem('Upload Scan Results', '/panel/finding/sub/plugin'));
            }
            
            if (Fisma_Acl::hasPrivilegeForClass('approve', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Approve Pending Findings', '/panel/finding/sub/approve'));
            }
            
            $mainMenuBar->add($findings);
        }

        if (Fisma_Acl::hasPrivilegeForClass('read', 'Organization')) {
            $systems = new Fisma_Yui_Menu('System Inventory');

            $systems->add(new Fisma_Yui_MenuItem('Systems', '/panel/system/sub/list'));
            
            if (Fisma_Acl::hasPrivilegeForClass('read', 'Asset')) {
                $systems->add(new Fisma_Yui_MenuItem('Assets', '/panel/asset/sub/list'));
            }

            $systems->add(new Fisma_Yui_MenuItem('Organizations', '/panel/organization/sub/tree'));

            $systems->add(new Fisma_Yui_MenuItem('Documentation', '/panel/system-document/sub/list'));
            
            $mainMenuBar->add($systems);
        }

        if (Fisma_Acl::hasArea('incident')) {
            // Incidents main menu
            $incidentMenu = new Fisma_Yui_Menu('Incidents');

            $incidentMenu->add(new Fisma_Yui_MenuItem('Report An Incident', '/panel/incident/sub/report'));
      
            if (Fisma_Acl::hasPrivilegeForClass('read', 'Incident')) {
                $incidentMenu->add(new Fisma_Yui_MenuItem('Search', '/panel/incident/sub/list'));
                $incidentMenu->add(new Fisma_Yui_MenuItem('Dashboard', '/incident-dashboard'));
            }

            // Incident Administration submenu
            if (Fisma_Acl::hasArea('incident_admin')) {
                $incidentAdminSubmenu = new Fisma_Yui_Menu('Administration');

                if (Fisma_Acl::hasPrivilegeForClass('read', 'IrCategory')) {
                    $incidentAdminSubmenu->add(new Fisma_Yui_MenuItem('Categories', '/panel/ircategory/sub/list'));
                }
                
                if (Fisma_Acl::hasPrivilegeForClass('read', 'IrWorkflowDef')) {
                    $incidentAdminSubmenu->add(new Fisma_Yui_MenuItem('Workflows', '/panel/irworkflow/sub/list'));
                }

                $incidentMenu->add($incidentAdminSubmenu);
            }
        
            // Incident reports submenu
            if (Fisma_Acl::hasArea('incident_report')) {
                $reportsSubmenu = new Fisma_Yui_Menu('Reports');
                $reportsSubmenu->add(new Fisma_Yui_MenuItem('Incidents By Category', '/panel/irreport/sub/category'));
                $reportsSubmenu->add(new Fisma_Yui_MenuItem('Incidents By Month', '/panel/irreport/sub/month'));
                $incidentMenu->add($reportsSubmenu);
            }

            $mainMenuBar->add($incidentMenu);
        }
        
        if (Fisma_Acl::hasArea('reports')) {
            $reports = new Fisma_Yui_Menu('Reports');
            
            $reports->add(new Fisma_Yui_MenuItem('FISMA Report', '/panel/report/sub/fisma'));
            
            if (Fisma_Acl::hasPrivilegeForClass('read', 'Organization')) {
                $reports->add(new Fisma_Yui_MenuItem('Overdue Report', '/panel/report/sub/overdue'));    
            }
            
            $reports->add(new Fisma_Yui_MenuItem('Plug-in Reports', '/panel/report/sub/plugin'));
            
            $mainMenuBar->add($reports);
        }
        
        if (Fisma_Acl::hasArea('admin')) {
            $admin = new Fisma_Yui_Menu('Administration');
            
            if (Fisma_Acl::hasArea('configuration')) {
                $admin->add(new Fisma_Yui_MenuItem('Configuration', '/panel/config'));
            }

            if (Fisma_Acl::hasPrivilegeForClass('read', 'Source')) {
                $admin->add(new Fisma_Yui_MenuItem('Finding Sources', '/panel/source/sub/list'));
            }

            if (Fisma_Acl::hasPrivilegeForClass('read', 'Network')) {
                $admin->add(new Fisma_Yui_MenuItem('Networks', '/panel/network/sub/list'));
            }

            if (Fisma_Acl::hasPrivilegeForClass('read', 'Product')) {
                $admin->add(new Fisma_Yui_MenuItem('Products', '/panel/product/sub/list'));
            }

            if (Fisma_Acl::hasPrivilegeForClass('read', 'Role')) {
                $admin->add(new Fisma_Yui_MenuItem('Roles', '/panel/role/sub/list'));
            }

            if (Fisma_Acl::hasPrivilegeForClass('read', 'User')) {
                $admin->add(new Fisma_Yui_MenuItem('Users', '/panel/account/sub/list'));
            }
            
            $mainMenuBar->add($admin);
        }
        
        $preferences = new Fisma_Yui_Menu('User Preferences');
        
        $preferences->add(new Fisma_Yui_MenuItem('Profile', '/panel/user/sub/profile'));
        if ('database' == Fisma::configuration()->getConfig('auth_type')
            || 'root' == User::currentUser()->username) {
            $preferences->add(new Fisma_Yui_MenuItem('Change Password', '/panel/user/sub/password'));
        }
        $preferences->add(new Fisma_Yui_MenuItem('E-mail Notifications', '/panel/user/sub/notification'));
        
        $mainMenuBar->add($preferences);

        if (Fisma::debug()) {
            $debug = new Fisma_Yui_Menu('Debug');
            
            $debug->add(new Fisma_Yui_MenuItem('Error log', '/debug/errorlog'));
            $debug->add(new Fisma_Yui_MenuItem('PHP Info', '/debug/phpinfo'));
            $debug->add(new Fisma_Yui_MenuItem('PHP log', '/debug/phplog'));
            
            $mainMenuBar->add($debug);
        }

        return $mainMenuBar;
    }
}
