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
 * Tests for the user model
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Model
 * @version    $Id$
 */
class Test_Model_User extends Test_FismaUnitTest
{
    /**
     * If salt and hash type are undefined, setting the password should define them automatically
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testSaltAndHashAreDefinedIfPasswordIsDefined()
    {
        $user = new User();
        
        $this->assertNull($user->passwordSalt);
        $this->assertNull($user->hashType);
        
        $user->password = 'password1'; // Nobody will ever guess this password!
        
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
        $user = new User();
        $secretPassword = 'password1';
        print "XXXXXXXXX\n";
        $user->password = $secretPassword; // This should be hashed before it is stored
        print "XXXXXXXXX\n";
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
}
