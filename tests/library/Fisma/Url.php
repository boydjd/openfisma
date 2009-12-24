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
 * Tests for Fisma_Url
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 * @version    $Id$
 */
class Test_Library_Fisma_Url extends Test_FismaUnitTest
{
    /**
     * Back up of $_SERVER
     *
     * @var array
     */
    protected $_serverBackup;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->_serverBackup = $_SERVER;
        unset($_SERVER['HTTPS']);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $_SERVER = $this->_serverBackup;
    }

    /**
     * Test baseUrl constructor with only HTTP_HOST
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithOnlyHost()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $this->assertEquals('http://example.com', Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with only HTTP_HOST including PORT
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithOnlyHostIncludingPort()
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8000';

        $this->assertEquals('http://example.com:8000', Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with HTTP_HOST and HTTPS is on
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithHostAndHttpsOn()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTPS'] = 'on';

        $this->assertEquals('https://example.com', Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with only HTTP_HOST including PORT and HTTPS is on
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithHostIncludingPortAndHttpsOn()
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8181';
        $_SERVER['HTTPS'] = 'on';

        $this->assertEquals('https://example.com:8181', Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with HTTP_HOST, SERVER_NAME and SERVER_PORT
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithHttpHostAndServerNameAndPortSet()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['SERVER_PORT'] = 8080;

        $this->assertEquals('http://example.com', Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with no HTTP_HOST, but SERVER_NAME and SERVER_PORT
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithNoHttpHostButServerNameAndPortSet()
    {
        unset($_SERVER['HTTP_HOST']);
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['SERVER_PORT'] = 8080;

        $this->assertEquals('http://example.org:8080', Fisma_Url::baseUrl());
    }

    /**
     * Test currentUrl constructor with requestUri
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testCurrentUrlConstructorWithRequestUri()
    {
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test/this/';

        $this->assertEquals('http://example.com/test/this', Fisma_Url::currentUrl());
    }

    /**
     * Test currentUrl constructor with requestUri including params
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testCurrentUrlConstructorWithRequestUriIncludingParams()
    {
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test/this/?param=1&param2=2';

        $this->assertEquals('http://example.com/test/this', Fisma_Url::currentUrl());
    }

    /**
     * Test customUrl with requestUri start with ('/', './' and '../')
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testCustomUrlWithRequestUri()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        // The requestUri start with '/'
        $requestUri = '/test';
        $this->assertEquals('http://example.com/test', Fisma_Url::customUrl($requestUri));

        // The requestUri start with './'
        $requestUri = './test';
        $this->assertEquals('http://example.com/test', Fisma_Url::customUrl($requestUri));

        // The requestUri start with '../' 
        $requestUri = '../test';
        $this->assertEquals('http://example.com/test', Fisma_Url::customUrl($requestUri));
    }

    /**
     * Test customUrl with integer
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testCustomUrlWithInteger()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $this->assertEquals('http://example.com', Fisma_Url::customUrl(456789));
    }
}
