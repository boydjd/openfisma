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

require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Tests for the Fisma facade class
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma extends Test_FismaUnitTest
{
    /**
     * Test the ability to globally set the enabled state of a Fisma_Doctrine_Record_Listener.
     * 
     * This covers the logic for loading listeners if they haven't been loaded when the enabled state is set.
     * 
     * This test isn't ideal because it relies on knowledge of a known Fisma_Doctrine_Record_Listener subclass in order
     * to test the logic in the Fisma class, but it's a necessary evil to get coverage of this rather important
     * function.
     */
    public function testGloballySetListenerEnabledState()
    {
        Fisma::setListenerEnabled(true);
        $this->assertTrue(IndexListener::getEnabled());
        
        Fisma::setListenerEnabled(false);
        $this->assertFalse(IndexListener::getEnabled());
    }
}
