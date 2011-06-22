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

require_once(realpath(dirname(__FILE__) . '/../../FismaUnitTest.php'));

/**
 * Test the module model
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Application
 */
class Test_Application_Models_Module extends Test_FismaUnitTest
{
    /**
     * Enable and disable a module
     */
    public function testEnableModule()
    {
        $module = new Module();
        
        $module->enabled = true;
        $this->assertTrue($module->enabled);
        
        $module->canBeDisabled = true;
        $module->enabled = false;
        $this->assertFalse($module->enabled);
    }
    
    /**
     * Try to disable a module which cannot be disabled
     * 
     * @expectedException Fisma_Zend_Exception
     */
    public function testDisableModule()
    {
        $module = new Module();
        
        $module->canBeDisabled = false;
        $module->enabled = false;
    }
}
