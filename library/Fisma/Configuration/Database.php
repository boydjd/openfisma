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
 */
class Fisma_Configuration_Database implements Fisma_Configuration_Interface
{
    /**
     * Get a configuration item from the configuration table
     * 
     * @param string $name The requested configuration item name
     * @return mixed The returned configuration item value
     * @throws Fisma_Zend_Exception_Config if the requested configuration item name is invalid
     */
    public function getConfig($name) 
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $cache = ($bootstrap) ? $bootstrap->getResource('cachemanager')->getCache('default') : null;

        if (!$cache || !$config = $cache->load('configuration_' . $name)) {

            $config = Doctrine_Query::create()
                ->select("c.${name}")
                ->from('Configuration c')
                ->limit(1)
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                ->execute();

            $config = $config[0][$name];

            if ($cache) {
                $cache->save($config, 'configuration_' . $name);
            }
        }

        return $config;
    }
    
    /**
     * Set a configuration item in the configuration table
     * 
     * @param string $name The specified configuration item name to set
     * @param mixed $value The value of the configuration item to set
     * @return void
     */
    public function setConfig($name, $value) 
    {
        CurrentUser::getInstance()->acl()->requireArea('admin');
      
        $config = Doctrine_Query::create()
            ->select("c.${name}")
            ->from('Configuration c')
            ->limit(1)
            ->execute();

        $config = $config[0];
        $config->$name = $value;
        $config->save();

        Notification::notify('CONFIGURATION_UPDATED', null, CurrentUser::getInstance());

        $cache = Zend_Controller_Front::getInstance()
                    ->getParam('bootstrap')
                    ->getResource('cachemanager')
                    ->getCache('default');

        if ($dirtyConfig = $cache->load('configuration_' . $name)) {
            $cache->remove('configuration_' . $name);
        }
    }
}
