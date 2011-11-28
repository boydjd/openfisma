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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * test /library/Fisma/Zend/Acl.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Zend_Acl extends Test_Case_Unit
{
    /**
     * test hasArea
     * @return void
     */
    public function testHasArea()
    {
        $testAcl = new Fisma_Zend_Acl('randomUser');
        $this->assertFalse($testAcl->hasArea('hidden'));

        $testAcl = new Fisma_Zend_Acl('root');
        $this->assertTrue($testAcl->hasArea('hidden'));

        // @todo test a situation with valid data, which returns a functional true;
    }

    /**
     * test requireArea
     * @return void
     */
    public function testRequireArea()
    {
        $area = 'hidden';

        $testAcl = new Fisma_Zend_Acl('root'); 
        $testAcl->requireArea($area, $user);

        $this->setExpectedException('Fisma_Zend_Exception_InvalidPrivilege', 'User does not have access to this area: \''.$area.'\'');
        $testAcl = new Fisma_Zend_Acl('defaultUser');
        $testAcl->requireArea($area);
    }

    /**
     * test hasPrivilegeForClass()
     * @return void
     */
    public function testHasPrivilegeForClass()
    {
        $testClass = 'Fisma_Zend_Acl';
        $testPrivilege = 'insert';
        
        $testAcl = new Fisma_Zend_Acl('defaultUser');
        $this->assertFalse($testAcl->hasPrivilegeForClass($testPrivilege, $testClass));

        // user has privilege -> functional true;
        // @require knowledge of Fisma_Zend_Acl->isAllowed() returning true for username='root'
        $testAcl = new Fisma_Zend_Acl('root');
        $this->assertTrue($testAcl->hasPrivilegeForClass($testPrivilege, $testClass));

        //class not found -> exception thrown;
        $testClass = 'unsupported class';
        $this->setExpectedException('Fisma_Zend_Exception', 'Privilege check failed for class \''.$testClass.'\' because the class could not be found');
        $testAcl->hasPrivilegeForClass($testPrivilege, $testClass);

        //@todo test privileges with wildcards
    }

    /**
     * test requirePrivilegeForClass()
     * @return void
     */
    public function testRequirePrivilegeForClass()
    {
        $testPrivilege = 'insert';
        $testClass = 'Fisma_Zend_Acl';

        // @require knowledge of Fisma_Zend_Acl->isAllowed() returning true for username='root'
        $testAcl = new Fisma_Zend_Acl('root');
        $testAcl->requirePrivilegeForClass($testPrivilege, $testClass);

        $testAcl = new Fisma_Zend_Acl('defaultUser');
        $this->setExpectedException('Fisma_Zend_Exception_InvalidPrivilege', "User does not have privilege '".$testPrivilege."' for class '".$testClass."'");
        $testAcl->requirePrivilegeForClass($testPrivilege, $testClass);
    }

    /**
     * test hasPrivilegeForObject()
     * @return void
     * @require MockOrg.php
     */
    public function testHasPrivilegeForObject()
    {
        //does not have Organization Dependency -> return hasPrivilegeForClass()
        $testAcl = new Fisma_Zend_Acl('defaultUser');
        $testPrivilege = 'insert';
        $testObj = $testAcl;
        $this->assertEquals($testAcl->hasPrivilegeForClass($testPrivilege, get_class($testObj)), $testAcl->hasPrivilegeForObject($testPrivilege, $testObj));

        //object has Organization dependency
        require_once(realpath(dirname(__FILE__) . '/MockOrg.php'));
        //empty orgId
        $mockOrg = new Test_Library_Fisma_Zend_MockOrg();
        $this->assertFalse($testAcl->hasPrivilegeForObject($testPrivilege, $mockOrg));
        //provided orgId
        $mockOrg->orgId = '1';
        $this->assertFalse($testAcl->hasPrivilegeForObject($testPrivilege, $mockOrg));

        //object invalid -> exception thrown
        $testObj = -1;
        $this->setExpectedException('Fisma_Zend_Exception', '$object is not an object');
        $testAcl->hasPrivilegeForObject($testPrivilege, $testObj);

        //@todo test privilege with wildcards
    }
 
    /**
     * test requirePrivilegeForObject()
     * @return void
     */
    public function testRequirePrivilegeForObject()
    {
        $testPrivilege = 'insert';
        //@require knowledge of Fisma_Zend_Acl->hasPrivilegeForClass() returning true for username='root'
        $testAcl = new Fisma_Zend_Acl('root');
        $testObj = $testAcl;

        $testAcl->requirePrivilegeForObject($testPrivilege, $testObj);

        //user has no privilege -> exception thrown
        $testAcl = new Fisma_Zend_Acl('defaultUser');
        $this->setExpectedException('Fisma_Zend_Exception_InvalidPrivilege', 'User does not have privilege \''.$testPrivilege.'\' for this object.');
        $testAcl->requirePrivilegeForObject($testPrivilege, $testObj);

    }
}
