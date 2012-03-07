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
 * A list whose items can be reordered by YUI's DragDrop helper
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_InteractiveOrderedList
{
    /**
     * Assigning class-level variables
     *
     * @param string    $id                 The HTML id of the UL
     * @param string    $contentModule      The module of the view script for each item in the list
     * @param string    $contentScript      The link to the view script for each item in the list
     * @param array     $dataList           The array of objects to pass to each item's view script
     * @param bool      $enabled            Whether the list is editable
     * @param string    $jsHandlers         The name of the extra JS function that handles the DragDrop events
     */
    public function __construct($id, $contentModule, $contentScript, $dataList, $enabled, $jsHandlers)
    {
        $this->id = $id;
        $this->contentModule = $contentModule;
        $this->contentScript = $contentScript;
        $this->dataList = $dataList;
        $this->enabled = $enabled;
        $this->jsHandlers = $jsHandlers;
    }
    /**
     * Constructing the HTML markup of the whole list
     *
     * @param Zend_View_Layout $layout
     * @return string
     */
    public function render($layout = null)
    {
        $layout = (!isset($layout)) ? Zend_Layout::getMvcInstance() : $layout;
        $view = $layout->getView();

        return $view->partial('yui/interactive-ordered-list.phtml', 'default', $this);
    }

    /**
     * Calls and returns render()
     *
     * @return void
     */
    public function __tostring()
    {
        return $this->render();
    }
}
