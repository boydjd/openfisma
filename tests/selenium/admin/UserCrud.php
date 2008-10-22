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

require_once '../../FismaSeleniumTest.php';

/**
 * Test the create, read, update, delete of user objects
 *
 * @package    Test
 * @subpackage Test_Admin
 * @version    $Id:$
 */
class Test_Selenium_Admin_UserCrud extends Test_FismaSeleniumTest
{
    /**
     * testCrud() - Test the create, read, update, and delete for user objects
     */
    public function testCrud()
    {
        $this->createDefaultUser('ADMIN');
        $this->login();
        $this->screenshot('Dashboard');
        
        // Create user
        $this->click("link=Users");
        $this->waitForPageToLoad();
        $this->click("add_user");
        $this->waitForPageToLoad();
        $this->type("account", "john.doe");
        $this->type("password", "ASDFasdf1234");
        $this->type("confirmPassword", "ASDFasdf1234");
        $this->type("name_first", "John");
        $this->type("name_last", "Doe");
        $this->type("email", "email.address@agency.gov");
        $this->select("role", "label=Authorizing Official");
        $this->screenshot('Create User');
        $this->click("submit");
        $this->waitForPageToLoad();
        
        // Review creation data on user list
        $this->click("user_list");
        $this->waitForPageToLoad();
        $this->assertTextPresent("John");
        $this->assertTextPresent("Doe");
        $this->assertTextPresent("email.address@agency.gov");
        $this->assertTextPresent("john.doe");
        $this->assertTextPresent("AO");
        $this->screenshot('User List');
        
        // Update user
        $this->click("link=Users");
        $this->waitForPageToLoad();
        $this->click("//div[@id='detail']/table[2]/tbody/tr[3]/td[8]/a/img");
        $this->waitForPageToLoad();
        $this->type("account", "updated.john.doe");
        $this->type("name_first", "UpdatedJohn");
        $this->type("name_last", "UpdatedDoe");
        $this->type("email", "updated.email.address@agency.gov");
        $this->select("role", "label=Certification Agent");
        $this->click("submit");
        $this->waitForPageToLoad();
        
        // Review updated data
        $this->click("user_list");
        $this->waitForPageToLoad();
        $this->assertTextPresent("updated.john.doe");
        $this->assertTextPresent("UpdatedJohn");
        $this->assertTextPresent("UpdatedDoe");
        $this->assertTextPresent("updated.email.address@agency.gov");
        $this->assertTextPresent("CERTAGENT");

        // Delete user
        $this->click("//div[@id='detail']/table[2]/tbody/tr[3]/td[10]/a/img");
        $this->getConfirmation(); // Throw away result
        $this->waitForPageToLoad();
        
        // Verify user was deleted
        $this->click("user_list");
        $this->waitForPageToLoad();
        $this->assertTextNotPresent("updated.john.doe");
        
        // Done
        $this->stop();
    }
}
