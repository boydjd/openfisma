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
        $path = Fisma::getPath('config');
        $menuConfig = Doctrine_Parser_YamlSf::load($path . '/menu.yml');

        self::buildMenu($user, $menuConfig, self::$_mainMenuBar, $parent = null);
       
        return self::$_mainMenuBar;
    }

    /**
     * Add a menu item to a menu
     *
     * @param string $type The menu item type.
     * @param string $label The label shows on the menu.
     * @param string $link The link of the menu item.
     * @param mixed string|null $model The model shows on the Go to.. menu item, 
     * null if the $type is not Fisma_Yui_MenuItem_GoTo 
     * @param integer $target  The target of the link.
     * @param integer $count Add the submenu to its parent menu when $count is 1.
     * @param Fisma_Yui_Menu $root The menu holds the menu items. 
     * @param Fisma_Yui_Menu $parent The parent menu holds the menu items. 
     * @return Fisma_Yui_MenuBar The assembled Fisma YUI menu bar object
     */
    private static function addMenuItem($type, $label, $link, $model, $target, $count, $root, $parent = null)
    {
        // It is Fisma_Yui_MenuItem if the $model is null.
        if (is_null($model)) {
            if (is_null($target)) {
                $menuItem = new $type($label, $link);
            } else {
                $menuItem = new $type($label, $link, null, $target);
            }
        } else {
            $menuItem = new $type($label, $model, $link);
        }
        $root->add($menuItem);

        // Only need to add the sub menu to its parent menu once
        if ($count == 1 && !is_null($parent)) {
            $parent->add($root);
        }
    }    

    /**
     * Build a main menu recursively.
     *
     * @param User $user
     * @param array $menuValue The data from configure file.
     * @param Fisma_Yui_Menu $root The menu holds the menu items. 
     * @param Fisma_Yui_Menu $parent The parent menu holds the menu items. 
     * @return Fisma_Yui_MenuBar The assembled Fisma YUI menu bar object
     */
    protected static function buildMenu($user, $menuValue, $root, $parent = null) 
    {
        $i = 0; 
        $acl = $user->acl();
        foreach ($menuValue as $key => $value) {
            if (isset($value['module'])) {
                $module = null;
                if (strstr($value['module'], 'Vulnerability')) {
                    $module = Doctrine::getTable('Module')->findOneByName('Vulnerability Management');
                } else if (strstr($value['module'], 'Incident')) {
                    $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');
                }
     
                // Skip the module if the module is not enable
                if (!$module || !$module->enabled 
                    || !$acl->$value['privilege']['func']($value['privilege']['param'])) {
                    continue;  
                }
            }

            // Skip the menuItem and its submenu if it does not have the privilege
            if (isset($value['privilege']) && 'hasArea' == $value['privilege']['func']) {
                if (!$acl->$value['privilege']['func']($value['privilege']['param'])) {
                     continue;  
                }
            }
            
            if (isset($value['submenu'])) {

                // Skip the menu if condition is not true
                if (isset($value['condition'])) {
                    if (eval($value['condition'])) {
                        $menu = new Fisma_Yui_Menu($value['label']);
                    } else {
                        continue;
                    } 
                } else { 
                    $menu = new Fisma_Yui_Menu($value['label']);
                }

                self::buildMenu($user, $value['submenu'], $menu, $root);
            } else {
                $i++; // Track the loop count, adding the submenu to its parent when it is 1.

                // Handle the different types of menu items 
                if ('Go To...' == $value['label']) {
                    if (isset($value['privilege'])) { 
                        if ( $acl->$value['privilege']['func']($value['privilege']['param1'],
                            $value['privilege']['param2'])) {
                            self::addMenuItem(
                                'Fisma_Yui_MenuItem_GoTo',
                                $value['label'],
                                $value['click'],
                                $value['model'],
                                null,
                                $i,
                                $root,
                                $parent
                            );
                        } else {
                            $i--; // It does not count if the menuItem is not added to menu.
                        }
                    } else {
                        self::addMenuItem(
                            'Fisma_Yui_MenuItem_GoTo',
                            $value['label'],
                            $value['click'],
                            $value['model'],
                            null,
                            $i,
                            $root,
                            $parent
                        );
                    }
                } else if ('Separator' == $value['label']) {
                    if (isset($value['condition'])) {
                        if (eval($value['condition'])) {
                            $root->addSeparator();
                        } else {
                            $i--;
                        } 
                    } else {
                        if ($i == 1) {
                            $i--; // Do not need to add separator if it is the first menuItem.
                        } else {
                            $root->addSeparator();
                        }
                    }
                } else {

                    // Do not need to check hasArea privilege here because it has been checked previously 
                    if (isset($value['privilege']) && 'hasArea' != $value['privilege']['func']) {
                        if ($acl->$value['privilege']['func'](
                                $value['privilege']['param1'],
                                $value['privilege']['param2'])
                            ) {
                                self::addMenuItem(
                                    'Fisma_Yui_MenuItem',
                                    $value['label'],
                                    $value['link'],
                                    null,
                                    isset($value['target']) ? $value['target'] : null,
                                    $i,
                                    $root,
                                    $parent
                                );
                        } else {
                            $i--; // It does not count if the menuItem is not added to menu.
                        }
                    } else {
                        if (isset($value['condition'])) {
                            
                            // Add the menu item based on the return of Evaluating the condition
                            if (eval($value['condition'])) {
                                self::addMenuItem(
                                    'Fisma_Yui_MenuItem',
                                    $value['label'],
                                    $value['link'],
                                    null,
                                    isset($value['target']) ? $value['target'] : null,
                                    $i,
                                    $root,
                                    $parent
                                );
                            } else {
                                $i--; // It does not count if the menuItem is not added to menu.
                            }
                        } else {
                            self::addMenuItem(
                                'Fisma_Yui_MenuItem',
                                $value['label'],
                                $value['link'],
                                null,
                                isset($value['target']) ? $value['target'] : null,
                                $i,
                                $root,
                                $parent
                            );
                        }
                    }
                }
            }
        }
    }
}
