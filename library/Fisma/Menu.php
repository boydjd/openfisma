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
     * @param User $user
     * @return Fisma_Yui_MenuBar The assembled Fisma YUI menu bar object
     */
    public static function getMainMenu($user)
    {
        $acl = $user->acl();
        $mainMenuBar = new Fisma_Yui_MenuBar();

        if ($acl->hasArea('dashboard')) {
            $dashboard = new Fisma_Yui_MenuItem('Dashboard', '/dashboard');
            $mainMenuBar->add($dashboard);
        }

        if ($acl->hasArea('finding')) {
            $findings = new Fisma_Yui_Menu('Findings');
            
            if ($acl->hasPrivilegeForClass('read', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Summary', '/finding/remediation/summary'));
                $findings->add(new Fisma_Yui_MenuItem('Search', '/finding/remediation/list'));
                $findings->add(new Fisma_Yui_MenuItem_GoTo('Go To...', 'Finding', '/finding/remediation'));
            }

            if ($acl->hasPrivilegeForClass('read', 'Finding')
                && ($acl->hasPrivilegeForClass('create', 'Finding')
                    || $acl->hasPrivilegeForClass('inject', 'Finding')
                    || $acl->hasPrivilegeForClass('approve', 'Finding'))) {

                $findings->addSeparator();    
            }

            if ($acl->hasPrivilegeForClass('create', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Create New Finding', '/finding/remediation/create'));
            }
            
            if ($acl->hasPrivilegeForClass('inject', 'Finding')) {
                $findings->add(new Fisma_Yui_MenuItem('Upload Spreadsheet', '/finding/index/injection'));
            }
                                    
            $findings->addSeparator();

            $findings->add(new Fisma_Yui_MenuItem('Dashboard', '/finding/dashboard'));

            // Finding Administration submenu
            if ($acl->hasArea('finding_admin')) {
                $findingAdminSubmenu = new Fisma_Yui_Menu('Administration');

                if ($acl->hasPrivilegeForClass('read', 'Source')) {
                    $findingAdminSubmenu->add(new Fisma_Yui_MenuItem('Finding Sources', '/finding/source/list'));
                }

                $findings->add($findingAdminSubmenu);
            }
        
            // Finding reports submenu
            if ($acl->hasArea('finding_report')) {
                $findingReportsSubmenu = new Fisma_Yui_Menu('Reports');

                $findingReportsSubmenu->add(new Fisma_Yui_MenuItem('OMB FISMA', '/finding/report/fisma'));

                $findingReportsSubmenu->add(
                    new Fisma_Yui_MenuItem('Overdue Findings', '/finding/report/overdue/format/html')
                );

                /**
                 * @todo This doesn't belong here, but plugin reports needs to be re-written.
                 */
                $findingReportsSubmenu->add(new Fisma_Yui_MenuItem('Plug-in Reports', '/finding/report/plugin'));

                $findings->add($findingReportsSubmenu);
            }
            
            $mainMenuBar->add($findings);
        }

        $vmModule = Doctrine::getTable('Module')->findOneByName('Vulnerability Management');
        if ($vmModule && $vmModule->enabled && $acl->hasArea('vulnerability')) {
            $mainMenuBar->add(self::buildVulnerabilitiesMenu($acl));
        }

        if ($acl->hasArea('system_inventory')) {
            $systemInventoryMenu = new Fisma_Yui_Menu('System Inventory');
            
            if ($acl->hasPrivilegeForClass('read', 'Asset')) {
                $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Assets', '/asset/list'));
            }
            
            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Controls', '/security-control/list'));

            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Documentation', '/system-document/list'));

            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Organizations', '/organization/tree'));
            
            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Systems', '/system/list'));

            $systemInventoryMenu->addSeparator();

            $systemInventoryMenu->add(new Fisma_Yui_MenuItem('Dashboard', '/organization-dashboard'));

            // Organization Administration submenu
            if ($acl->hasArea('system_inventory_admin')) {
                $systemInventoryAdminMenu = new Fisma_Yui_Menu('Administration');

                $systemInventoryAdminMenu->add(new Fisma_Yui_MenuItem('Controls', '/security-control-admin'));

                if ($acl->hasPrivilegeForClass('read', 'DocumentType')) {
                    $systemInventoryAdminMenu->add(new Fisma_Yui_MenuItem('Document Types', '/document-type/list'));
                }

                if ($acl->hasPrivilegeForClass('read', 'Network')) {
                    $systemInventoryAdminMenu->add(new Fisma_Yui_MenuItem('Networks', '/network/list'));
                }

                $systemInventoryMenu->add($systemInventoryAdminMenu);
            }

            // Organization reports submenu
            if ($acl->hasArea('system_inventory_report')) {
                $systemInventoryReportsMenu = new Fisma_Yui_Menu('Reports');

                $systemInventoryReportsMenu->add(
                    new Fisma_Yui_MenuItem(
                        'Documentation Compliance', 
                        '/organization-report/documentation-compliance/format/html'
                    )
                );

                $systemInventoryReportsMenu->add(
                    new Fisma_Yui_MenuItem('Personnel', '/organization-report/personnel/format/html')
                );

                $systemInventoryReportsMenu->add(
                    new Fisma_Yui_MenuItem('Privacy', '/organization-report/privacy/format/html')
                );

                $systemInventoryReportsMenu->add(
                    new Fisma_Yui_MenuItem(
                        'Security Authorizations', 
                        '/organization-report/security-authorization/format/html'
                    )
                );

                $systemInventoryMenu->add($systemInventoryReportsMenu);
            }

            $mainMenuBar->add($systemInventoryMenu);
        }

        $incidentModule = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if ($incidentModule && $incidentModule->enabled && $acl->hasArea('incident')) {
            // Incidents main menu
            $incidentMenu = new Fisma_Yui_Menu('Incidents');

            $incidentMenu->add(new Fisma_Yui_MenuItem('Report An Incident', '/incident/report'));
      
            $incidentMenu->add(new Fisma_Yui_MenuItem('Search', '/incident/list'));
            $incidentMenu->add(new Fisma_Yui_MenuItem_GoTo('Go To...', 'Incident', '/incident'));

            $incidentMenu->addSeparator();

            $incidentMenu->add(new Fisma_Yui_MenuItem('Dashboard', '/incident-dashboard'));

            // Incident Administration submenu
            if ($acl->hasArea('incident_admin')) {
                $incidentAdminSubmenu = new Fisma_Yui_Menu('Administration');

                if ($acl->hasPrivilegeForClass('read', 'IrSubCategory')) {
                    $incidentAdminSubmenu->add(new Fisma_Yui_MenuItem('Categories', '/ir-category/list'));
                }
                
                if ($acl->hasPrivilegeForClass('read', 'IrWorkflowDef')) {
                    $incidentAdminSubmenu->add(new Fisma_Yui_MenuItem('Workflows', '/ir-workflow/list'));
                }

                $incidentMenu->add($incidentAdminSubmenu);
            }
        
            // Incident reports submenu
            if ($acl->hasArea('incident_report')) {
                $reportsSubmenu = new Fisma_Yui_Menu('Reports');

                $reportsSubmenu->add(
                    new Fisma_Yui_MenuItem('Incident Bureaus', '/incident-report/bureau/format/html')
                );

                $reportsSubmenu->add(
                    new Fisma_Yui_MenuItem('Incident Categories', '/incident-report/category/format/html')
                );

                $reportsSubmenu->add(
                    new Fisma_Yui_MenuItem('Incident History', '/incident-report/history/format/html')
                );

                $incidentMenu->add($reportsSubmenu);
            }

            $mainMenuBar->add($incidentMenu);
        }
                
        if ($acl->hasArea('admin')) {
            $admin = new Fisma_Yui_Menu('Administration');
            
            $admin->add(new Fisma_Yui_MenuItem('E-mail', '/config/email'));
            
            $admin->add(new Fisma_Yui_MenuItem('General Policies', '/config/general'));

            if ('ldap' == Fisma::configuration()->getConfig('auth_type')) {
                $admin->add(new Fisma_Yui_MenuItem('LDAP', '/config/list-ldap'));
            }

            $admin->add(new Fisma_Yui_MenuItem('Modules', '/config/modules'));

            $admin->add(new Fisma_Yui_MenuItem('Password Policy', '/config/password'));

            $admin->add(new Fisma_Yui_MenuItem('Privacy Policy', '/config/privacy'));

            if ($acl->hasPrivilegeForClass('read', 'Role')) {
                $admin->add(new Fisma_Yui_MenuItem('Roles', '/role/list'));
            }
            
            $admin->add(new Fisma_Yui_MenuItem('Search', '/config/search'));
            
            $admin->add(new Fisma_Yui_MenuItem('Technical Contact', '/config/contact'));

            if ($acl->hasPrivilegeForClass('read', 'User')) {
                $admin->add(new Fisma_Yui_MenuItem('Users', '/user/list'));
            }
            
            $mainMenuBar->add($admin);
        }
        
        $preferences = new Fisma_Yui_Menu('User Preferences');
        
        if ('database' == Fisma::configuration()->getConfig('auth_type')
            || 'root' == $user->username) {
            $preferences->add(new Fisma_Yui_MenuItem('Change Password', '/user/password'));
        }
        $preferences->add(new Fisma_Yui_MenuItem('E-mail Notifications', '/user/notification'));

        $preferences->add(new Fisma_Yui_MenuItem('Profile', '/user/profile'));
        
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

    /**
     * Constructs a vulnerabilities menu
     *
     * @param Zend_Acl $acl
     * @return Fisma_Yui_Menu
     */
    protected static function buildVulnerabilitiesMenu(Zend_Acl $acl)
    {
        $menu = new Fisma_Yui_Menu('Vulnerabilities');

        $menu->add(new Fisma_Yui_MenuItem('Search', '/vm/vulnerability/list'));
        $menu->add(new Fisma_Yui_MenuItem_GoTo('Go To...', 'Vulnerability', '/vm/vulnerability'));

        $menu->addSeparator();

        if ($acl->hasPrivilegeForClass('create', 'Vulnerability')) {
            $menu->add(new Fisma_Yui_MenuItem('Upload Scan Results', '/vm/vulnerability/plugin'));
        }

        $menu->addSeparator();

        if ($acl->hasArea('vulnerability_admin')) {
            $adminMenu = new Fisma_Yui_Menu('Administration');

            if ($acl->hasPrivilegeForClass('read', 'Product')) {
                $adminMenu->add(new Fisma_Yui_MenuItem('Products', '/vm/product/list'));
            }

            if ($acl->hasPrivilegeForClass('read', 'VulnerabilityResolution')) {
                $adminMenu->add(new Fisma_Yui_MenuItem('Resolutions', '/vm/vulnerability-resolution/list'));
            }

            $menu->add($adminMenu);
        }

        if ($acl->hasArea('vulnerability_report')) {
            $reportsMenu = new Fisma_Yui_Menu('Reports');

            $reportsMenu->add(
                new Fisma_Yui_MenuItem('Aggregated Risk', '/vm/vulnerability-report/risk/format/html')
            );
            
            $reportsMenu->add(
                new Fisma_Yui_MenuItem('Reopened Vulnerabilities', '/vm/vulnerability-report/reopened/format/html')
            );

            $reportsMenu->add(
                new Fisma_Yui_MenuItem('Vulnerable Services', '/vm/vulnerability-report/vulnerable-service/format/html')
            );

            $menu->add($reportsMenu);
        }
        
        return $menu;
    }
}
