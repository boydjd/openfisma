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

require_once(realpath(dirname(__FILE__) . '/../../../../FismaUnitTest.php'));

/**
 * Tests for Fisma_Doctrine_Record_Listener
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Doctrine_Record_Listener extends Test_FismaUnitTest
{
    /**
     * Test ability to set enabled state
     */
    public function testSetEnabledState()
    {
        $listener = new Fisma_Doctrine_Record_Listener();
        
        $listener->setEnabled(false);
        $this->assertFalse($listener->getEnabled());
        
        $listener->setEnabled(true);
        $this->assertTrue($listener->getEnabled());
    }
}
