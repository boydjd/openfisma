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
 * Modules represent separate functionalities in OpenFISMA. 
 * 
 * This is currently a light-weight implementation, but should be a first step towards a more modular approach to
 * augmenting OpenFISMA's functionality.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Module extends BaseModule
{
    /**
     * Set custom mutators
     * 
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->hasMutator('enabled', 'setEnabled');
    }

    /**
     * Update the 'enabled' state of this module
     * 
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        if ($enabled === false  && !$this->canBeDisabled) {
            throw new Fisma_Zend_Exception("The '$this->name' module cannot be disabled");
        } else {
            $this->_set('enabled', $enabled);
        }
    }
}