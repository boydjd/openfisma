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
 * Class description
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
     */
    public function testHasArea()
    {
        $testAcl = new Fisma_Zend_Acl();

        //user not found -> erroneous false;
        $this->assertFalse($testAcl->hasArea('hidden'));

        //$user is not object -> erroneous false;
        $user = array('username'=>'root');
        $this->assertFalse($testAcl->hasArea('hidden', $user));

        //user does not has privilege -> functional false;
        $user = new MockUser();
        $this->assertFalse($testAcl->hasArea('hidden', $user));

        //username = 'root' -> overridden true;
        $user = new MockUser('root');
        $this->assertTrue($testAcl->hasArea('hidden', $user));

        //@todo test a situation with valid data, which returns a functional true;
    }

    /**
     * test requireArea
     */
    public function testRequireArea()
    {
        $testAcl = new Fisma_Zend_Acl();
        $area = 'hidden';

        //user has privilege -> no exception
        $user = new MockUser('root');
        $testAcl->requireArea($area, $user);

        //user doesn't has privilege -> exception thrown
        $this->setExpectedException('Fisma_Zend_Exception_InvalidPrivilege', 'User does not have access to this area: \''.$area.'\'');
        $testAcl->requireArea($area);
    }

    /**
     * test hasPrivilegeForClass()
     */
    public function testHasPrivilegeForClass()
    {
        $testAcl = new Fisma_Zend_Acl();
        $testClass = 'Fisma_Zend_Acl';
        $testPrivilege = 'insert';
        
        //user not found -> erroneous false;
        $this->assertFalse($testAcl->hasPrivilegeForClass($testPrivilege, $testClass));

        //user does not have privilege -> functional false;
        $user = new MockUser();        
        $this->assertFalse($testAcl->hasPrivilegeForClass($testPrivilege, $testClass, $user));

        //user has privilege -> functional true;
        //@require knowledge of Fisma_Zend_Acl->isAllowed() returning true for username='root'
        $user = new MockUser('root');
        $this->assertTrue($testAcl->hasPrivilegeForClass($testPrivilege, $testClass, $user));

        //class not found -> exception thrown;
        $testClass = 'unsupported class';
        $this->setExpectedException('Fisma_Zend_Exception', 'Privilege check failed for class \''.$testClass.'\' because the class could not be found');
        $testAcl->hasPrivilegeForClass($testPrivilege, $testClass, $user);

        //@todo test privileges with wildcards
    }

    /**
     * test requirePrivilegeForClass()
     */
    public function testRequirePrivilegeForClass()
    {
        $testAcl = new Fisma_Zend_Acl();
        $testPrivilege = 'insert';
        $testClass = 'Fisma_Zend_Acl';

        //user has privilege -> no exception
        //@require knowledge of Fisma_Zend_Acl->isAllowed() returning true for username='root'
        $user = new MockUser('root');
        $testAcl->requirePrivilegeForClass($testPrivilege, $testClass, $user);

        //user has no privilege -> exception thrown
        $this->setExpectedException('Fisma_Zend_Exception_InvalidPrivilege', 'User does not have privilege \''.$testPrivilege.'\' for class \''.$testClass.'\'');
        $testAcl->requirePrivilegeForClass($testPrivilege, $testClass);

    }

    /**
     * test hasPrivilegeForObject()
     * @require MockOrg.php
     */
    public function testHasPrivilegeForObject()
    {
        //does not have Organization Dependency -> return hasPrivilegeForClass()
        $testAcl = new Fisma_Zend_Acl();
        $testPrivilege = 'insert';
        $testObject = $testAcl;
        $this->assertEquals($testAcl->hasPrivilegeForClass($testPrivilege, get_class($testObject)), $testAcl->hasPrivilegeForObject($testPrivilege, $testObject));

        //object has Organization dependency
        require_once(realpath(dirname(__FILE__) . '/MockOrg.php'));
        //empty orgId
        $mockOrg = new Test_Library_Fisma_Zend_MockOrg();
        $user = new MockUser('');
        $this->assertFalse($testAcl->hasPrivilegeForObject($testPrivilege, $mockOrg, $user));
        //provided orgId
        $mockOrg->orgId = '1';
        $this->assertFalse($testAcl->hasPrivilegeForObject($testPrivilege, $mockOrg, $user));

        //object invalid -> exception thrown
        $testObject = -1;
        $this->setExpectedException('Fisma_Zend_Exception', '$object is not an object');
        $testAcl->hasPrivilegeForObject($testPrivilege, $testObject);

        //@todo test privilege with wildcards
    }
 
    /**
     * test requirePrivilegeForObject()
     */
    public function testRequirePrivilegeForObject()
    {
        $testAcl = new Fisma_Zend_Acl();
        $testPrivilege = 'insert';
        $testObject = $testAcl;

        //user has privilege -> no exception
        //@require knowledge of Fisma_Zend_Acl->hasPrivilegeForClass() returning true for username='root'
        $user = new MockUser('root');
        $testAcl->requirePrivilegeForObject($testPrivilege, $testObject, $user);

        //user has no privilege -> exception thrown
        $this->setExpectedException('Fisma_Zend_Exception_InvalidPrivilege', 'User does not have privilege \''.$testPrivilege.'\' for this object.');
        $testAcl->requirePrivilegeForObject($testPrivilege, $testObject);

    }

}
class MockUser
{
    public $username;
    public function __construct($username = 'defaultUser')
    {
        $this->username = $username;
    }
}
