<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Ryan <ryan.yang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Test_Model
 */

require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

/**
 * Unit tests for the User model
 *
 * @package Test
 */
class Test_Model_User extends Test_FismaUnitTest
{
    /**
     * Lock an account and then unlock it. Verify all flags are set or cleared correctly.
     */
    public function testAccountManualLock()
    {
        $user = new User();
        
        $user->lockAccount(User::LOCK_TYPE_MANUAL);
        $this->assertEquals($user->locked, true);
        $this->assertNotNull($user->lockTs);                
        $this->assertEquals($user->lockType, User::LOCK_TYPE_MANUAL);
        
        $user->unlockAccount();
        $this->assertEquals($user->locked, false);
        $this->assertNull($user->lockTs);                
        $this->assertNull($user->lockType);        
    }
}
