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

require_once(realpath(dirname(__FILE__) . '/../../../../Case/Unit.php'));

/**
 * test /library/Fisma/Symfony/Components/ServiceContainerLoaderAnnotationAbstractAnnotation.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotation extends Test_Case_Unit
{
    /**
     * setup before each test
     * 
     * @return void
     * @requires ServiceContainerLoaderAnnotationAbstractAnnotationDummy
     * @requires ZendReflectionDocBlockDummy
     */
    public function setup()
    {
        require_once(realpath(dirname(__FILE__) . '/ServiceContainerLoaderAnnotationAbstractAnnotationDummy.php'));
        require_once(realpath(dirname(__FILE__) . '/ZendReflectionDocblockDummy.php'));
    }
    /**
     * test constructor
     * @return void
     */
    public function testConstructor()
    {
        $name = 'very long';
        $tooLong = new Test_Library_Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotationDummy($name);
        $this->assertEquals($name, $tooLong->getName());
    }

    /**
     * test filters
     * @return void
     */
    public function testFilters()
    {
        $tooLong = new Test_Library_Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotationDummy('testObject');
        $message = 'bem bem';
        $this->assertEquals($message, $tooLong->filterUnderscore('_'.$message));
        $this->assertEquals($message, $tooLong->filterUnderscore($message));
        $this->assertEquals($message, $tooLong->filterSetPrefix('set'.$message));
        $this->assertEquals('a'.$message, $tooLong->filterSetPrefix('A'.$message));
    }

    /**
     * test getTag()
     * @return void
     */
    public function testGetTag()
    {
        $tooLong = new Test_Library_Fisma_Symfony_Components_ServiceContainerLoaderAnnotationAbstractAnnotationDummy('testObject');
        $this->assertEquals('testObject_tagged', $tooLong->getTag(new Test_Library_Fisma_Symfony_Components_ZendReflectionDocblockDummy()));
    }
}

