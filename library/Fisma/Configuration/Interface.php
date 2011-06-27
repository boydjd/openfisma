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
 * Interface for a system configuration object
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Configuration
 */
interface Fisma_Configuration_Interface
{
    /**
     * Get a configuration item by name
     * 
     * @param string $name The configuration item name to obtain
     * @return mixed The value of the requested configuration item name
     * @throws Fisma_Zend_Exception_Config if not found the requested configuration item name
     */
    public function getConfig($name);
    
    /**
     * Set a configuration item by name
     * 
     * @param string $name The configuration item name to set
     * @param mixed $value The corresponding value of the configuration item name
     * @return void
     */
    public function setConfig($name, $value);
}
