<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Menu
 */
class Fisma_Menu
{
    // Hold the main menu bar.
    private static $_mainMenuBar = null;

    /**
     * Constructs a main menu.
     *
     * @param User $user
     * @return Fisma_Yui_MenuBar Build a menu bar from a YAML configure file
     */
    public static function getMainMenu($user)
    {
        self::$_mainMenuBar = new Fisma_Yui_MenuBar();
        try {
            $path = Fisma::getPath('config');
            $menuConfig = Doctrine_Parser_YamlSf::load($path . '/menu.yml');
            self::buildMenu($user, $menuConfig, self::$_mainMenuBar);
        } catch(Exception $e) {
            Zend_Registry::get('Zend_Log')->err($e);
        }
        return self::$_mainMenuBar;
    }

    /**
     * Add a menu item to a menu
     *
     * @param string $type The menu item type.
     * @param string $label The label shows on the menu.
     * @param string $link The link of the menu item.
     * @param string$onClick  The javascript callback for onclick event.
     * @param string $target  The target of the link.
     * @param Fisma_Yui_Menu $root The menu holds the menu items.
     */
    private static function addMenuItem($type, $label, $link, $onClick, $target, $root, $pull)
    {
        if (!is_null($onClick)) {
            $onClick = new Fisma_Yui_MenuItem_OnClick($onClick);
        }
        $menuItem = new $type($label, $link, $onClick, $target, $pull);
        $root->add($menuItem);
    }

    /**
     * Build a main menu recursively.
     *
     * @param User $user
     * @param array $menuValue The data from configure file.
     * @param Fisma_Yui_Menu $root The menu holds the menu items.
     */
    protected static function buildMenu($user, $menuValue, $root)
    {
        $acl = $user->acl();
        foreach ($menuValue as $key => $value) {
            if (self::_hideItem($value, $user)) {
                continue;
            }
            $pull = isset($value['pull']) ? 'pull-' . $value['pull'] : '';
            $label = isset($value['label']) ? $value['label'] : $key;
            // Handle dynamic values
            // Replace $systemName with information from Fisma::configuration in label
            $systemName = Fisma::configuration()->getConfig('system_name');
            $label = str_replace('$systemName', $systemName, $label);

            // Replace $currentUser with information from CurrentUser in label
            $currentUser = CurrentUser::getAttribute('displayName');
            $label = str_replace('$currentUser', $currentUser, $label);

            // Replace $notificationCount with information from CurrentUser in label
            $notificationCount = CurrentUser::getAttribute('Notifications')->count();
            $label = str_replace('$notificationCount', $notificationCount, $label);

            // Replace $mailToAdmin with information from Fisma::configuration in link
            if (isset($value['link'])) {
                $view = self::_getCurrentView();
                $mailurl = 'mailto:' . Fisma::configuration()->getConfig('contact_email')
                         . '?Subject='. $view->escape(Fisma::configuration()->getConfig('contact_subject'), 'url');
                $value['link'] = str_replace('$mailToAdmin', $mailurl, $value['link']);
            }

            if (isset($value['submenu'])) {
                $menu = new Fisma_Yui_Menu($label, $pull);
                self::buildMenu($user, $value['submenu'], $menu);
                $menu->removeEmptyGroups();
                if (!$menu->isEmpty()) {
                    $root->add($menu);
                }
            } elseif ('Separator' == $value['label']) {
                $root->addSeparator();
            } else {
                self::addMenuItem(
                    'Fisma_Yui_MenuItem',
                    $label,
                    isset($value['link']) ? $value['link'] : null,
                    isset($value['onclick']) ? $value['onclick'] : null,
                    isset($value['target']) ? $value['target'] : null,
                    $root,
                    $pull
                );
            }
        }
    }

    /**
     * Determine whether a menu item should be hidden.
     */
    protected static function _hideItem($item, $user)
    {
        $acl = $user->acl();
        if (isset($item['module'])) {
            $module = null;
            if (strstr($item['module'], 'Vulnerability')) {
                $module = Doctrine::getTable('Module')->findOneByName('Vulnerability Management');
            } else if (strstr($item['module'], 'Finding')) {
                $module = Doctrine::getTable('Module')->findOneByName('Findings');
            } else if (strstr($item['module'], 'Incident')) {
                $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');
            } else if (strstr($item['module'], 'Compliance')) {
                $module = Doctrine::getTable('Module')->findOneByName('Compliance');
            } else if (strstr($item['module'], 'System')) {
                $module = Doctrine::getTable('Module')->findOneByName('System Inventory');
            }

            // Skip the module if the module is not enable
            if (!$module || !$module->enabled) {
                return true;
            }
        }

        if (isset($item['privilege']['func'])) {
            $func = $item['privilege']['func'];
            $param1 = isset($item['privilege']['param']) ? $item['privilege']['param'] : $item['privilege']['param1'];
            $param2 = isset($item['privilege']['param2']) ? $item['privilege']['param2'] : null;
            if (!$acl->$func($param1, $param2)) {
                return true;
            }
        }

        // Skip the menu if condition is not true
        if (isset($item['condition']) && !eval($item['condition'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the current view object, mainly used for escaping
     *
     * @param Zend_View $view Optional. Used for mocking in unittesting
     * @return Zend_View
     */
    protected static function _getCurrentView($view = null)
    {
        return (empty($view)) ? Zend_Layout::getMvcInstance()->getView() : $view;
    }

    /**
     * Returns whether the application is using APC
     *
     * @return boolean
     */
    public static function isApc()
    {
        $resources = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('resources');
        $usingApc = (strtolower($resources['cachemanager']['default']['backend']['name']) == 'apc');
        $apcAvailable = in_array('apc', get_loaded_extensions());

        return ($apcAvailable && $usingApc);
    }
}
