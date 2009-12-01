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
 * System configuration items, such as authentication policy, account management policy, etc.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 * @version    $Id$
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
    public static function getConfig($name) 
    {
        $config = Doctrine::getTable('Configuration')->findOneByName($name);
        if (!empty($config)) {
            return $config->value;
        }
        if (Zend_Registry::isRegistered($name)) {
            return Zend_Registry::get($name);
        }
        throw new Fisma_Exception_Config("Invalid configuration name: $name");
    }
    
    /**
     * Set a configuration item in the configuration table. This static function is merely a convenience
     * function to make this common task easier to perform.
     * 
     * @param string $name
     * @param string|int $value
     */
    public static function setConfig($name, $value) 
    {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $config = Doctrine::getTable('Configuration')->findOneByName($name);
        $config->value = $value;
        $config->save();
    }

    /**
     * Handle conversion from minutes to seconds for certain configuration items
     * 
     * @param Doctrine_Event $event
     */
    public function preSave($event)
    {
        $modifyValue = $this->getModified();

        if ($modifyValue && isset($modifyValue['value'])) {
            $value = $modifyValue['value'];
            $affectedArray = array('session_inactivity_period', 'unlock_duration');
            if (in_array($this->name, $affectedArray)) {
                //convert to second
                $value *= 60;
            }
            $config->value = $value;
        }
    }
}
