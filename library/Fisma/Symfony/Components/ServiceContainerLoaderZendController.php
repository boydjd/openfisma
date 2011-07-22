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
 * Fisma_Symfony_Components_ServiceContainerLoaderZendController 
 * 
 * @uses Fisma_Symfony_Components_ServiceContainerLoaderAnnotations
 * @package Fisma_Symfony_Components 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @author Loïc Frering <loic.frering@gmail.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Symfony_Components_ServiceContainerLoaderZendController extends 
Fisma_Symfony_Components_ServiceContainerLoaderAnnotations
{
    /**
     * _definitions 
     * 
     * @var array
     */
    protected $_definitions = array();

    /**
     * _annotations 
     * 
     * @var array
     */
    protected $_annotations = array();

    /**
     * _reflect 
     * 
     * @param mixed $file 
     * @return void
     */
    protected function _reflect($file)
    {
        require_once $file;
        $r = new Zend_Reflection_File($file);
        try {
            $r = $r->getClass();
            if ($r->getDocblock()->hasTag('Service')) {
                $serviceName = $this->_reflectServiceName($r);
                $definition = $this->_reflectDefinition($r);
                $this->_definitions[$serviceName] = $definition;
            }
        }
        catch(Zend_Reflection_Exception $e) {
        }
        catch(ReflectionException $e) {
        }
    }

    /**
     * _reflectConstructor 
     * 
     * @param Zend_Reflection_Class $r 
     * @param sfServiceDefinition $definition 
     * @return void
     */
    protected function _reflectConstructor(Zend_Reflection_Class $r, sfServiceDefinition $definition)
    {
        $definition->addArgument(new sfServiceReference('zend.controller.request'));
        $definition->addArgument(new sfServiceReference('zend.controller.response'));
        $definition->addArgument(new sfServiceReference('zend.controller.params'));
    }

    /**
     * _reflectServiceName 
     * 
     * @param Zend_Reflection_Class $r 
     * @return string 
     */
    protected function _reflectServiceName(Zend_Reflection_Class $r)
    {
        $className = $r->getName();
        return 'zend.controller.' . $className;
    }
}

