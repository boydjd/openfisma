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

        if (Fisma_Zend_Acl::hasArea('dashboard')) {
            $dashboard = new Fisma_Yui_MenuItem('Dashboard', '/panel/dashboard');
            $mainMenuBar->add($dashboard);
        }

        if (Fisma_Zend_Acl::hasArea('finding')) {
            $findings = new Fisma_Yui_Menu('Findings');
            
            $findings->add(new Fisma_Yui_MenuItem('Summary', '/panel/remediation/sub/summary'));
            $findings->add(new Fisma_Yui_MenuItem('Search', '/panel/remediation/sub/searchbox'));

            if (Fisma_Zend_Acl::hasPrivilegeForClass('create', 'Finding')
                || Fisma_Zend_Acl::hasPrivilegeForClass('inject', 'Finding')
                || Fisma_Zend_Acl::hasPrivilegeForClass('approve', 'Finding')) {

                $findings->addSeparator();    
            }

            if (Fisma_Zend_Acl::hasPrivilegeForClass('approve', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Approve Pending Findings', '/panel/finding/sub/approve'));
            }

            if (Fisma_Zend_Acl::hasPrivilegeForClass('create', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Create New Finding', '/panel/finding/sub/create'));
            }
            
            if (Fisma_Zend_Acl::hasPrivilegeForClass('inject', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Upload Spreadsheet', '/panel/finding/sub/injection'));
                $findings->add(new Fisma_Yui_MenuItem('Upload Scan Results', '/panel/finding/sub/plugin'));
            }
                        
            if (Fisma_Zend_Acl::hasArea('incident_admin')
                || Fisma_Zend_Acl::hasArea('incident_report')) {
                    
                $findings->addSeparator();
            }
            
            // Finding Administration submenu
            if (Fisma_Zend_Acl::hasArea('finding_admin')) {
                $findingAdminSubmenu = new Fisma_Yui_Menu('Administration');

                if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'Source')) {
                    $findingAdminSubmenu->add(new Fisma_Yui_MenuItem('Finding Sources', '/panel/source/sub/list'));
                }

                $findings->add($findingAdminSubmenu);
            }
        
            // Finding reports submenu
            if (Fisma_Zend_Acl::hasArea('finding_report')) {
                $findingReportsSubmenu = new Fisma_Yui_Menu('Reports');

                $findingReportsSubmenu->add(new Fisma_Yui_MenuItem('OMB FISMA', '/finding-report/fisma'));

                $findingReportsSubmenu->add(new Fisma_Yui_MenuItem('Overdue Findings', '/finding-report/overdue'));

                /**
                 * @todo This doesn't belong here, but plugin reports needs to be re-written.
                 */
                $findingReportsSubmenu->add(new Fisma_Yui_MenuItem('Plug-in Reports', '/finding-report/plugin'));

                $findings->add($findingReportsSubmenu);
            }
            
            $mainMenuBar->add($findings);
        }

        if (Fisma_Zend_Acl::hasArea('system_inventory')) {
            $systemInventoryMenu = new Fisma_Yui_Menu('System Inventory');
            
            if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'Asset')) {
                $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Assets', '/panel/asset/sub/list'));
            }

            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Documentation', '/panel/system-document/sub/list'));

            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Organizations', '/panel/organization/sub/tree'));
            
            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Systems', '/panel/system/sub/list'));

            $systemInventoryMenu->addSeparator();

            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Dashboard', '/organization-dashboard'));

            // Organization Administration submenu
            if (Fisma_Zend_Acl::hasArea('system_inventory_admin')) {
                $systemInventoryAdminMenu = new Fisma_Yui_Menu('Administration');

                if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'Network')) {
                    $systemInventoryAdminMenu->add(new Fisma_Yui_MenuItem('Networks', '/panel/network/sub/list'));
                }

                if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'Product')) {
                    $systemInventoryAdminMenu->add(new Fisma_Yui_MenuItem('Products', '/panel/product/sub/list'));
                }

                $systemInventoryMenu->add($systemInventoryAdminMenu);
            }

            // Organization reports submenu
            if (Fisma_Zend_Acl::hasArea('system_inventory_report')) {
                $systemInventoryReportsMenu = new Fisma_Yui_Menu('Reports');

                $systemInventoryReportsMenu->add(
                    new Fisma_Yui_MenuItem('Personnel', '/organization-report/personnel')
                );

                $systemInventoryReportsMenu->add(
                    new Fisma_Yui_MenuItem('Privacy', '/organization-report/system-privacy')
                );

                $systemInventoryReportsMenu->add(
                    new Fisma_Yui_MenuItem('Security Authorizations', '/organization-report/system-privacy')
                );

                $systemInventoryMenu->add($systemInventoryReportsMenu);
            }

            $mainMenuBar->add($systemInventoryMenu);
        }

        $incidentModule = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if ($incidentModule && $incidentModule->enabled && Fisma_Zend_Acl::hasArea('incident')) {
            // Incidents main menu
            $incidentMenu = new Fisma_Yui_Menu('Incidents');

            $incidentMenu->add(new Fisma_Yui_MenuItem('Report An Incident', '/panel/incident/sub/report'));
      
            $incidentMenu->add(new Fisma_Yui_MenuItem('Search', '/panel/incident/sub/list'));

            $incidentMenu->addSeparator();

            $incidentMenu->add(new Fisma_Yui_MenuItem('Dashboard', '/incident-dashboard'));

            // Incident Administration submenu
            if (Fisma_Zend_Acl::hasArea('incident_admin')) {
                $incidentAdminSubmenu = new Fisma_Yui_Menu('Administration');

                if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'IrCategory')) {
                    $incidentAdminSubmenu->add(new Fisma_Yui_MenuItem('Categories', '/panel/ircategory/sub/list'));
                }
                
                if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'IrWorkflowDef')) {
                    $incidentAdminSubmenu->add(new Fisma_Yui_MenuItem('Workflows', '/panel/irworkflow/sub/list'));
                }

                $incidentMenu->add($incidentAdminSubmenu);
            }
        
            // Incident reports submenu
            if (Fisma_Zend_Acl::hasArea('incident_report')) {
                $reportsSubmenu = new Fisma_Yui_Menu('Reports');
                $reportsSubmenu->add(new Fisma_Yui_MenuItem('Incident Categories', '/incident-report/category'));
                $reportsSubmenu->add(new Fisma_Yui_MenuItem('Incident History', '/incident-report/history'));
                $incidentMenu->add($reportsSubmenu);
            }

            $mainMenuBar->add($incidentMenu);
        }
                
        if (Fisma_Zend_Acl::hasArea('admin')) {
            $admin = new Fisma_Yui_Menu('Administration');
            
            $admin->add(new Fisma_Yui_MenuItem('E-mail', '/config/email'));
            
            $admin->add(new Fisma_Yui_MenuItem('General Policies', '/config/general'));

            if ('ldap' == Fisma::configuration()->getConfig('auth_type')) {
                $admin->add(new Fisma_Yui_MenuItem('LDAP', '/config/ldaplist'));
            }

            $admin->add(new Fisma_Yui_MenuItem('Modules', '/config/modules'));

            $admin->add(new Fisma_Yui_MenuItem('Password Policy', '/config/password'));

            $admin->add(new Fisma_Yui_MenuItem('Privacy Policy', '/config/privacy'));

            if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'Role')) {
                $admin->add(new Fisma_Yui_MenuItem('Roles', '/panel/role/sub/list'));
            }
            
            $admin->add(new Fisma_Yui_MenuItem('Technical Contact', '/config/contact'));

            if (Fisma_Zend_Acl::hasPrivilegeForClass('read', 'User')) {
                $admin->add(new Fisma_Yui_MenuItem('Users', '/panel/account/sub/list'));
            }
            
            $mainMenuBar->add($admin);
        }
        
        $preferences = new Fisma_Yui_Menu('User Preferences');
        
        if ('database' == Fisma::configuration()->getConfig('auth_type')
            || 'root' == User::currentUser()->username) {
            $preferences->add(new Fisma_Yui_MenuItem('Change Password', '/panel/user/sub/password'));
        }
        $preferences->add(new Fisma_Yui_MenuItem('E-mail Notifications', '/panel/user/sub/notification'));

        $preferences->add(new Fisma_Yui_MenuItem('Profile', '/panel/user/sub/profile'));
        
        $mainMenuBar->add($preferences);

        if (Fisma::debug()) {
            $debug = new Fisma_Yui_Menu('Debug');
            
            $debug->add(new Fisma_Yui_MenuItem('APC System Cache', '/debug/apc-cache/type/system'));
            $debug->add(new Fisma_Yui_MenuItem('APC User Cache', '/debug/apc-cache/type/user'));
            $debug->add(new Fisma_Yui_MenuItem('Error log', '/debug/errorlog'));
            $debug->add(new Fisma_Yui_MenuItem('PHP Info', '/debug/phpinfo'));
            $debug->add(new Fisma_Yui_MenuItem('PHP log', '/debug/phplog'));
            
            $mainMenuBar->add($debug);
        }

        return $mainMenuBar;
    }
}
