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
 * An object which represents a menu in YUI
 * 
 * This object is designed to be easily converted to JSON, so it's JSON members are exposed in PHP as public members.
 * 
 * This design constraint makes this class a little difficult to understand, so refer to the unit tests if you are
 * trying to figure out how this works. Also see the phpdoc on $submenu.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_Menu
{
    /**
     * Holds menu title
     * 
     * @var string
     */
    public $text;
    
    /**
     * Stores submenu id and item data
     * 
     * Example:
     * 
     * 'id' => 'uniqueMenuIdGoesHere'
     * 'itemdata' => array(
     *     0 => array(
     *         new MenuItem('Item 1'),
     *         new MenuItem('Item 2')
     *     ),
     *     1 => array(
     *         new MenuItem('Item 3')
     *     )
     * )
     * 
     * The example above would draw the following menus:
     * 
     * ------
     * Item 1
     * Item 2
     * ------
     * Item 3
     * ------
     *
     * @var array
     */
    public $submenu = array();

    /**
     * To perform initialization as a default constructor
     * 
     * @param string $title The specified menu title
     * @return void
     */
    function __construct($title) 
    {
        $this->text = $title;
        
        $this->submenu['id'] = uniqid();
        
        // itemdata is an array of arrays. Create one empty inner array to begin with.
        $this->submenu['itemdata'] = array();
        $this->submenu['itemdata'][0] = array();
    }
    
    /**
     * Append a specified submenu item to the submenu data array of the menu
     * 
     * @param Fisma_Yui_MenuItem $item The specified submenu item
     * @return void
     * @todo is it necessary to support Fisma_Yui_Menu based cascading menu in the future?
     */
    public function add($item) 
    {
        $currentMenu = count($this->submenu['itemdata']) - 1;
        
        $this->submenu['itemdata'][$currentMenu][] = $item;
    }
    
    /**
     * Add a menu separator immediately after the menu item which was most recently added
     * 
     */
    public function addSeparator()
    {
        $this->submenu['itemdata'][] = array();
    }
}
