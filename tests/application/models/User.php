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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Tests for the user model
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Model
 */
class Test_Application_Models_User extends Test_Case_Unit
{
    /**
     * Disable listeners for the User model and create a new application configuration
     * 
     * This improves test quality by removing coupling, but some tests may selectively re-enable listeners to test the
     * functionality inside them.
     */
    public function setUp()
    {
        Doctrine::getTable('User')->getRecordListener()->setOption('disabled', true);  

        // Create a new configuration object for each test case to prevent cross-test contamination
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
    }

    /**
     * If salt and hash type are undefined, setting the password should define them automatically
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testSaltAndHashAreDefinedIfPasswordIsDefined()
    {
        Fisma::configuration()->setConfig('hash_type', 'sha1');

        $user = new User();

        $this->assertNull($user->passwordSalt);
        $this->assertNull($user->hashType);

        $user->password = 'password1';
        // Nobody will ever guess this password!

        $this->assertNotNull($user->passwordSalt);
        $this->assertNotNull($user->hashType);
    }

    /**
     * Ensure that passwords are not stored in plain text
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testPasswordNotInPlainText()
    {
        Fisma::configuration()->setConfig('hash_type', 'sha1');

        $user = new User();

        $secretPassword = 'password1';
        $user->password = $secretPassword; // This should be hashed before it is stored

        $this->assertNotEquals($secretPassword, $user->password);
    }

    /**
     * A user is not allowed to reuse any of the three previous passwords
     * 
     * @return void
     * @expectedException Doctrine_Exception
     */
    public function testPasswordHistoryFailure()
    {
        Fisma::configuration()->setConfig('hash_type', 'sha1');

        $user = new User();
        $password = 'password1';

        $user->password = $password;
        $user->password = $password;
    }

    /**
     * Test password history success
     * 
     * Generate a series of passwords and then try reusing an old one
     * 
     * This isn't a great test because it is relies on the User::PASSWORD_HISTORY_LIMIT constant. It would probably be
     * better to have some API to get that value, but this is all going to change anyway so I'm not going to do that 
     * now.
     * 
     * @return void
     * @throws PHPUnit_Framework_AssertionFailedError if not able to reuse old passwords
     */
    public function testPasswordHistorySuccess()
    {
        Fisma::configuration()->setConfig('hash_type', 'sha1');

        $user = new User();

        // We use the loop counter $i as the password to generate a simple, non-repeating sequence of passwords
        for ($i = 0; $i <= User::PASSWORD_HISTORY_LIMIT; $i++) {
            $user->password = $i;
        }

        // Now we can try using 0 as the password again, it should not throw an exception
        try {
            $user->password = 0;
        } catch (Doctrine_Exception $e) {
            $this->fail('Not able to reuse old passwords');
        }
    }

    /**
     * testGetOrganizationsQueryForRoot 
     * 
     * @access public
     * @return void
     */
    public function testGetOrganizationsQueryForRoot()
    {
        $user = new User();

        $user->username = 'root';

        $this->assertEquals(" FROM Organization o ORDER BY o.lft", $user->getOrganizationsQuery()->getDql());
    }

    /**
     * testGetOrganizationsQueryForNonRootUser 
     * 
     * @access public
     * @return void
     */
    public function testGetOrganizationsQueryForNonRootUser()
    {
        $user = new User();

        $user->username = 'testuser';
        $user->id = 0;

        $expectedQuery = 'FROM Organization o, o.UserRole ur WITH ur.userid = 0 ORDER BY o.lft';
        $query = $user->getOrganizationsQuery()->getDql();
        $this->assertContains($expectedQuery, $query);
    }

    /**
     * testGetOrganizationsByPrivilegeQueryForRoot 
     * 
     * @access public
     * @return void
     */
    public function testGetOrganizationsByPrivilegeQueryForRoot()
    {
        $user = new User();

        $user->username = 'root';

        $this->assertContains(
            "FROM Organization o ORDER BY o.lft",
            $user->getOrganizationsByPrivilegeQuery('finding', 'view')->getDql()
        );
    }

    /**
     * testGetOrganizationsByPrivilegeQueryForNonRootUser 
     * 
     * @access public
     * @return void
     */
    public function testGetOrganizationsByPrivilegeQueryForNonRootUser()
    {
        $user = new User();

        $user->username = 'testuser';
        $user->id = 0;

        // include disposal system 
        $this->assertContains(
            'FROM Organization o, o.UserRole ur WITH ur.userid = 0 '
           .'LEFT JOIN ur.Role r '
           .'LEFT JOIN r.Privileges p '
           .'WHERE p.resource = ? AND p.action = ? '
           .'GROUP BY o.id ORDER BY o.nickname',
            $user->getOrganizationsByPrivilegeQuery('finding', 'view', true)->getDql()
        );

        // do not include disposal system
        $this->assertContains(
            'FROM Organization o, o.UserRole ur WITH ur.userid = 0 '
            .'LEFT JOIN ur.Role r '
            .'LEFT JOIN r.Privileges p '
            .'LEFT JOIN o.System s2 '
            .'WHERE p.resource = ? AND p.action = ? AND s2.sdlcphase <> \'disposal\' or s2.sdlcphase is NULL '
            .'GROUP BY o.id ORDER BY o.nickname',
            $user->getOrganizationsByPrivilegeQuery('finding', 'view')->getDql()
        );
    }

    /**
     * testGetSystemsQueryForRoot 
     * 
     * @access public
     * @return void
     */
    public function testGetSystemsQueryForRoot()
    {
        $user = new User();

        $user->username = 'root';

        $this->assertEquals(
            " FROM Organization o INNER JOIN o.System s ORDER BY o.lft", $user->getSystemsQuery()->getDql()
        );
    }

    /**
     * testGetSystemsQueryForNonRootUser 
     * 
     * @access public
     * @return void
     */
    public function testGetSystemsQueryForNonRootUser()
    {
        $user = new User();

        $user->username = 'testuser';

        $this->assertEquals(
            "SELECT o.* FROM Organization o, o.UserRole ur WITH ur.userid =  INNER JOIN o.System s ORDER BY o.lft",
            $user->getSystemsQuery()->getDql()
        );
    }

    /**
     * testLockAccountWithEmptyType 
     * 
     * @access public
     * @return void
     * @expectedException Fisma_Zend_Exception
     */
    public function testLockAccountWithEmptyType()
    {
        $user = new User();
        $user->lockAccount(null);
    }

    /**
     * Test lockAccount with manual type from Current User
     *
     * @return void
     */
    public function testLockAccountFromCurrentUser()
    {
        @$user = $this->getMock('User', array('save', 'invalidateAcl', 'getAuditLog'));
        $mockAuditLog = $this->getMock('Mock_Blank', array('write'));
        $mockAuditLog->expects($this->once())->method('write');
        $user->expects($this->once())->method('save');
        $user->expects($this->once())->method('invalidateAcl');
        $user->expects($this->once())->method('getAuditLog')->will($this->returnValue($mockAuditLog));

        CurrentUser::setInstance($user);
        try {
            $user->lockAccount('manual');
        } catch(Doctrine_Connection_Sqlite_Exception $e) {
        }
        CurrentUser::setInstance(null);
    }

    /**
     * Test lockAccount with manual type from unknown user
     *
     * @return void
     */
    public function testLockAccountFromUnknownUser()
    {
        @$user = $this->getMock('User', array('save', 'invalidateAcl', 'getAuditLog'));
        $mockAuditLog = $this->getMock('Mock_Blank', array('write'));
        $mockAuditLog->expects($this->once())->method('write');
        $user->expects($this->once())->method('save');
        $user->expects($this->once())->method('invalidateAcl');
        $user->expects($this->once())->method('getAuditLog')->will($this->returnValue($mockAuditLog));

        CurrentUser::setInstance(null);
        try {
            $user->lockAccount('manual');
        } catch(Doctrine_Connection_Sqlite_Exception $e) {
        }
    }

    /**
     * Test the execution of the query built by getRolesQuery()
     *
     * @return void
     * @deprecated pending on the removal of source method
     */
    public function testGetRoles()
    {
        $user = new User();
        $mockQuery = $this->getMock('Mock_Blank', array('execute'));
        $mockQuery->expects($this->once())->method('execute');
        $user->getRoles(null, $mockQuery);
    }

    /**
     * Test setLastRob()
     *
     * @return void
     */
    public function testSetLastRob()
    {
        @$user = $this->getMock('User', array('_set', 'getAuditLog'));
        $mockAuditLog = $this->getMock('Mock_Blank', array('write'));
        $mockAuditLog->expects($this->once())->method('write');
        $user->expects($this->once())->method('_set');
        $user->expects($this->once())->method('getAuditLog')->will($this->returnValue($mockAuditLog));
        $user->setLastRob(0);
    }

}
