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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id:$
 */

//require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
require_once 'FismaSeleniumTest.php';

/**
 * Test the create, read, update, delete of user objects
 *
 * @package    Test
 * @subpackage Test_Admin
 * @version    $Id:$
 */
class Test_Selenium_Admin_UserCrud extends Test_FismaSeleniumTest {

    /**
     * testCreate() - Create a user object
     */
    public function testCreate() {
        $this->truncateTables(array('users', 'user_roles'));

        // Create an admin user for this test
        $userTable = new User($this->_db);
        $adminUserId = $userTable->insert(
            array(
                'account' => self::USER_NAME,
                'password' => md5(self::PASSWORD),
                'is_active' => 1,
                'last_rob' => new Zend_Db_Expr('now()')
            )
        );
        
        // Give the new user an admin role
        $grantRole = $this->_db->prepare(
            "INSERT INTO user_roles
                  SELECT $adminUserId,
                         r.id
                    FROM roles r
                   WHERE r.nickname like 'ADMIN'"
        );
        $grantRole->execute();
        
        // Begin test: login
        $this->open('/user/logout');
        $this->type('username', self::USER_NAME);
        $this->type('userpass', self::PASSWORD);
        $this->click("//input[@value='Login']");
        $this->waitForPageToLoad();
        
        // Create user
        $this->assertTextPresent(self::USER_NAME . ' is currently logged in');
        $this->click("link=Users");
        $this->waitForPageToLoad();
        $this->click("add_user");
        $this->waitForPageToLoad();
        $this->type("account", "john.doe");
        $this->type("password", "test_password");
        $this->type("confirm_password", "test_password");
        $this->type("name_first", "John");
        $this->type("name_last", "Doe");
        $this->type("email", "email.address@agency.gov");
        $this->select("role", "label=Authorizing Official");
        $this->click("submit");
        $this->waitForPageToLoad();
        $this->assertTextPresent("User (john.doe) added");
        
        // Review creation data on user list
        $this->click("user_list");
        $this->waitForPageToLoad();
        $this->assertTextPresent("John");
        $this->assertTextPresent("Doe");
        $this->assertTextPresent("email.address@agency.gov");
        $this->assertTextPresent("john.doe");
        $this->assertTextPresent("AO");
        
        // Review creation data on user detail
        $this->click("//div[@id='detail']/table[2]/tbody/tr[3]/td[9]/a/img");
        $this->waitForPageToLoad();
        
        // Done
        $this->stop();
    }
}