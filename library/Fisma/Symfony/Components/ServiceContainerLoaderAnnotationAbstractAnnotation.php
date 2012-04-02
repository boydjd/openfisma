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
 * Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotation 
 * 
 * @package Fisma_Symfony_Components
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @author Loïc Frering <loic.frering@gmail.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
abstract class Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotation
{
    /**
     * _name 
     * 
     * @var mixed
     */
    protected $_name;

    /**
     * __construct 
     * 
     * @param mixed $name 
     * @return void
     */
    public function  __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * getName 
     * 
     * @return void
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * reflectConstructor 
     * 
     * @param Zend_Reflection_Method $constructor 
     * @param sfServiceDefinition $definition 
     */
    abstract public function reflectConstructor(Zend_Reflection_Method $constructor, sfServiceDefinition $definition);

    /**
     * reflectProperty 
     * 
     * @param Zend_Reflection_Property $property 
     * @param sfServiceDefinition $definition 
     */
    abstract public function reflectProperty(Zend_Reflection_Property $property, sfServiceDefinition $definition);

    /**
     * reflectMethod 
     * 
     * @param Zend_Reflection_Method $method 
     * @param sfServiceDefinition $definition 
     */
    abstract public function reflectMethod(Zend_Reflection_Method $method, sfServiceDefinition $definition);

    /**
     * _getTag 
     * 
     * @param Zend_Reflection_Docblock $docblock 
     * @return void
     */
    protected function _getTag(Zend_Reflection_Docblock $docblock)
    {
        return $docblock->getTag($this->getName());
    }

    /**
     * _filterUnderscore 
     * 
     * @param mixed $value 
     * @return void
     */
    protected function _filterUnderscore($value)
    {
        if (strpos($value, '_') === 0) {
            return substr($value, 1);
        }
        return $value;
    }

    /**
     * _filterSetPrefix 
     * 
     * @param mixed $value 
     * @return void
     */
    protected function _filterSetPrefix($value)
    {
        if (strpos($value, 'set') === 0) {
            return Fisma_String::lcfirst(substr($value, 3));
        }
        return Fisma_String::lcfirst($value);
    }
}
