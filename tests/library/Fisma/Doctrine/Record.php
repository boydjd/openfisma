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

require_once(realpath(dirname(__FILE__) . '/../../../FismaUnitTest.php'));

/**
 * Tests for the Fisma_Doctrine_Record class
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Doctrine_Record extends Test_FismaUnitTest
{
    /**
     * Test the ability to get an original value even after a field is modified several times.
     * 
     * In order to test this, we need to use a known subclass, since Fisma_Doctrine_Record itself does not contain all
     * of the functionality required to test this. (Namely, it doesn't contain any setUp or setTableDefinition.) We use
     * the Finding model for this purpose since we know that Finding is central to the application and won't go away
     * any time soon.
     */
    public function testGetOriginalValue()
    {
        $finding = new Finding();
        
        // Set a field several times
        $finding->description = 'Janet Reno Dance Party';
        $finding->description = 'Deep Thoughts With Jack Handy';
        $finding->description = 'Celebrity Jeopardy';
        
        $this->assertNull($finding->getOriginalValue('description'));
    }
}
