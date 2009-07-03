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
 * @author    chris.chen <chriszero@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package
 */

require_once(realpath(dirname(__FILE__) . '/../FismaSeleniumTest.php'));

/**
 * Selenium Rc test for finding source
 *
 * Make sure that database was initial, simple data loaded and index created
 * Modified selenium.conf to suit for system conditions
 * Selenium service was running in the background
 *
 */
class Test_Selenium_SourceTest extends Test_FismaSeleniumTest
{
    /**
     * Test data string
     *
     */
    private $_sourceName = "source name 1";
    private $_sourceNickname = "source nickname 1";
    private $_sourceDescription = "source description 1";

    public function setUp()
    {
        parent::setUp();
        parent::login();
    }

    public function tearDown()
    {
        $this->stop();
    }

    /**
     * Create Source
     *
     */
    public function testCreation()
    {
        // Open creation page
        // There is a js error, when menubar loaded
        echo $this->getAlert();
        $this->open("/panel/source/sub/create");
        $this->waitForPageToLoad();
        //check message without data
        sleep(5);
        $this->type("name", "");
        $this->type("nickname", "");
        $this->type("tinymce", "");
        //submit
        $this->click("save-button");
        $this->waitForPageToLoad();
        $this->assertRegExp("/Source Name: Value is empty," .
            " but a non-empty value is required/",
            $this->getText("msgbar"));
        $this->assertRegExp("/Source Nickname: Value is empty," .
            " but a non-empty value is required/",
            $this->getText("msgbar"));
        //check message with data input
        $this->type("name", $this->_sourceName);
        $this->type("nickname", $this->_sourceNickname);
        $this->type("tinymce", $this->_sourceDescription);
        //submit
        $this->click("save-button");
        $this->waitForPageToLoad();
        $this->assertRegExp("/The Source is created/",
            $this->getText("msgbar"));
        $this->assertEquals($this->_sourceName,
            $this->getValue("name"));
        $this->assertEquals($this->_sourceNickname,
            $this->getValue("nickname"));
        $this->assertEquals($this->_sourceDescription,
            $this->getText("tinymce"));
    }

    /**
     *  Search and view
     *
     */
    public function testSearch()
    {
        //open list page
        echo $this->getAlert();
        $this->open("/panel/source/sub/list");
        $this->waitForPageToLoad();
        $this->type("keywords", $this->_sourceName);
        $this->click("yui-gen0-button");
        $this->waitForPageToLoad();
        echo $this->getAlert();
        sleep(5);
        // Click the first row on search result list
        $this->click("yui-rec0");
        $this->waitForPageToLoad();
        $this->assertEquals($this->_sourceName,
            $this->getValue("name"));
        $this->assertEquals($this->_sourceNickname,
            $this->getValue("nickname"));
        $this->assertEquals($this->_sourceDescription,
            $this->getText("xpath=//div[@class='formValue']//p"));
    }

    /**
     *  Edit source
     *
     */
    public function testEdit()
    {
        //open list page
        echo $this->getAlert();
        $this->open("/panel/source/sub/list");
        $this->waitForPageToLoad();
        $this->type("keywords", $this->_sourceName);
        $this->click("yui-gen0-button");
        $this->waitForPageToLoad();
        echo $this->getAlert();
        sleep(5);
        $this->click("yui-rec0");
        $this->waitForPageToLoad();
        $this->click("xpath=//a[text()='Edit']");
        $this->waitForPageToLoad();
        sleep(5);
        echo $this->getAlert();
        $this->_sourceName .= ' changed';
        $this->_sourceNickname .= ' changed';
        $this->_sourceDescription .= ' changed';
        $this->type("name", $this->_sourceName);
        $this->type("nickname", $this->_sourceNickname);
        $this->type("tinymce", $this->_sourceDescription);
        $this->click("save-button");
        $this->waitForPageToLoad();
        $this->assertRegExp("/The Source is updated/",
            $this->getText("msgbar"));
        $this->assertEquals($this->_sourceName,
            $this->getValue("name"));
        $this->assertEquals($this->_sourceNickname,
            $this->getValue("nickname"));
        $this->assertEquals($this->_sourceDescription,
            $this->getText("tinymce"));
    }

    /**
     *  Delete source
     *
     */
    public function testDelete()
    {
        //open list page
        echo $this->getAlert();
        $this->open("/panel/source/sub/list");
        $this->waitForPageToLoad();
        $this->type("keywords", $this->_sourceName);
        $this->click("yui-gen0-button");
        $this->waitForPageToLoad();
        echo $this->getAlert();
        sleep(5);
        $this->click("yui-rec0");
        $this->waitForPageToLoad();
        $this->click("xpath=//a[text()='Delete']");
        $this->waitForPageToLoad();
        sleep(5);
        $this->assertRegExp("/Source is deleted successfully/",
            $this->getText("msgbar"));
    }

}
