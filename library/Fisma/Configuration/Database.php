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
 * A configuration implementation which uses a Doctrine table
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Configuration
 * @version    $Id$
 */
class Fisma_Configuration_Database implements Fisma_Configuration_Interface
{
    /**
     * Get a configuration item from the configuration table
     * 
     * @param string $name
     * @return mixed
     */
    public function getConfig($name) 
    {
        $config = Doctrine::getTable('Configuration')->findOneByName($name);

        if (!$config) {
            throw new Fisma_Exception_Config("Invalid configuration name: $name");
        }
            
        return $config->value;
    }
    
    /**
     * Set a configuration item in the configuration table
     * 
     * @param string $name The specified configuration item name to set
     * @param mixed $value The value of the configuration item to set
     */
    public function setConfig($name, $value) 
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $config = Doctrine::getTable('Configuration')->findOneByName($name);
        
        if (!$config) {
            $config = new Configuration();
        }
        
        $config->value = $value;
        $config->save();
    }
}
