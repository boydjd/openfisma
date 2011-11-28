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
 ** Dummy class to wrap Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotation
 ** @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 ** @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 ** @license    http://www.openfisma.org/content/license GPLv3
 ** @package    Test
 ** @subpackage Test_Library
 **/

class Test_Library_Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotationDummy extends Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotation
{
    /*
     * dummy implementation of parent's abstract methods
     */
    public function reflectConstructor(Zend_Reflection_Method $constructor, sfServiceDefinition $definition)
    {
    }
    public function reflectProperty(Zend_Reflection_Property $property, sfServiceDefinition $definition)
    {
    }
    public function reflectMethod(Zend_Reflection_Method $method, sfServiceDefinition $definition)
    {
    }

    /*
     * public accessor to parent's protected methods
     */
    public function getTag(Zend_Reflection_Docblock $docblock)
    {
        return parent::_getTag($docblock);
    }
    //why are the following 2 functions not public and/or static?
    public function filterUnderscore($value)
    {
        return parent::_filterUnderscore($value);
    }
    public function filterSetPrefix($value)
    {
        return parent::_filterSetPrefix($value);
    }
}
