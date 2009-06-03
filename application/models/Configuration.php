<?php
/**
 * Copyright (c) 2009 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 * @version   $Id$
 * @package   Model
 */

/**
 * System configuration items, such as authentication policy, account management policy, etc.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 */
class Configuration extends BaseConfiguration
{
    /**
     * Get a configuration item from the configuration table. This static function is merely a convenience
     * function to make this common task easier to perform.
     * 
     * @param string $name
     * @return string|int $value
     */
    public static function getConfig($name) {
        $config = Doctrine::getTable('Configuration')->findOneByName($name);
        
        return $config->value;
    }
    
    /**
     * Set a configuration item in the configuration table. This static function is merely a convenience
     * function to make this common task easier to perform.
     * 
     * @param string $name
     * @param string|int $value
     */
    public static function setConfig($name, $value) {
        Fisma_Acl::requirePrivilege('app_configuration', 'update');
        
        $config = Doctrine::getTable('Configuration')->findOneByName($name);
        $config->value = $value;
        $config->save();
    }
}