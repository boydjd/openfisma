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
 * A Menu Item type which pops up a "Go To" dialog and redirects the user to the requested model ID.
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_MenuItem_GoTo extends Fisma_Yui_MenuItem
{
    /**
     * Constructor
     *
     * @param string $label Menu item label.
     * @param string $model  Model for redirect.
     * @param string $controller Controller for redirect.
     */
    public function __construct($label, $model, $controller)
    {
        $onClick = new Fisma_Yui_MenuItem_OnClick(
            'Fisma.Menu.goTo',
            array('model' => $model, 'controller' => $controller)
        );
        parent::__construct('Go To...', '', $onClick);
    }
}
