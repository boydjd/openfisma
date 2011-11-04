<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Defines constants used in TreeNodeDragBehavior.js.
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_DragDrop
{
    /**
     * A type constant of drag operation of organization tree which defines the operation that move
     * the specified organization node as previous of the target organization node among their siblings.
     */
    const DRAG_ABOVE = 0;

    /**
     * A type constant of drag operation of organization tree which defines the operation that move
     * the specified organization node as child of the target organization node in organization tree.
     */
    const DRAG_ONTO = 1;

    /**
     * A type constant of drag operation of organization tree which defines the operation that move
     * the specified organization node as next of the target node among their siblings.
     */
    const DRAG_BELOW = 2;
}
