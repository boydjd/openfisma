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
 * Fisma_Symfony_Components_ServiceContainerLoaderAnnotationInject 
 * 
 * @uses Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotation
 * @package Fisma_Symfony_Components 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @author Loïc Frering <loic.frering@gmail.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Symfony_Components_ServiceContainerLoaderAnnotationInject extends
Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotation
{
    /**
     * __construct
     *
     * @return void
     */
    public function  __construct()
    {
        parent::__construct('Inject');
    }

    /**
     * reflectConstructor 
     * 
     * @param Zend_Reflection_Method $constructor 
     * @param sfServiceDefinition $definition 
     * @return void
     */
    public function reflectConstructor(Zend_Reflection_Method $constructor, sfServiceDefinition $definition)
    {
        $parameters = $constructor->getParameters();
        foreach ($parameters as $parameter) {
            $serviceReference = new sfServiceReference($this->_extractServiceNameFromParameter($parameter));
            $definition->addArgument($serviceReference);
        }
    }

    /**
     * reflectProperty 
     * 
     * @param Zend_Reflection_Property $property 
     * @param sfServiceDefinition $definition 
     * @return void
     */
    public function reflectProperty(Zend_Reflection_Property $property, sfServiceDefinition $definition)
    {
        $propertyName = $this->_filterUnderscore($property->getName());
        $serviceReference = new sfServiceReference($this->_extractServiceNameFromProperty($property));
        $definition->addMethodCall('set' . ucfirst($propertyName), array($serviceReference));
    }

    /**
     * reflectMethod 
     * 
     * @param Zend_Reflection_Method $method 
     * @param sfServiceDefinition $definition 
     * @return void
     */
    public function reflectMethod(Zend_Reflection_Method $method, sfServiceDefinition $definition)
    {
        $serviceReference = new sfServiceReference($this->_extractServiceNameFromMethod($method));
        $definition->addMethodCall($method->getName(), array($serviceReference));
    }

    /**
     * _extractServiceNameFromParameter 
     * 
     * @param mixed $p 
     * @return void
     */
    protected function _extractServiceNameFromParameter($p)
    {
        return $p->getName();
    }

    /**
     * _extractServiceNameFromProperty 
     * 
     * @param mixed $p 
     * @return void
     */
    protected function _extractServiceNameFromProperty($p)
    {
        $propertyName = $p->getName();
        $tagDescription = $this->_getTag($p->getDocComment())->getDescription();
        if (!empty($tagDescription)) {
            return $tagDescription;
        }
        return $this->_filterUnderscore($propertyName);
    }

    /**
     * _extractServiceNameFromMethod 
     * 
     * @param mixed $m 
     * @return void
     */
    protected function _extractServiceNameFromMethod($m)
    {
        $methodName = $m->getName();
        $tagDescription = $this->_getTag($m->getDocblock())->getDescription();
        if (!empty($tagDescription)) {
            return $tagDescription;
        }
        return $this->_filterSetPrefix($methodName);
    }
}
