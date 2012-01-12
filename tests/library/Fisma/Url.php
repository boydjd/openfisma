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
 * Tests for Fisma_Url
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Url extends Test_Case_Unit
{

    /**
     * Test baseUrl constructor without host_url and server info 
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithNoHostUrlAndServerInfo()
    {
        if (!isset($_SERVER)) {
            $_SERVER = array();
        }
        $_SERVER['HTTPS']       = '';
        $_SERVER['SERVER_NAME'] = '';
        $_SERVER['SERVER_PORT'] = '';
        $hostName = 'http://' . php_uname('n');
        
        $this->assertEquals($hostName, Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with HTTPS is on and port is 443
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithHttpsOnAndPortIs443()
    {
        if (!isset($_SERVER)) {
            $_SERVER = array();
        }
        $_SERVER['HTTPS']       = 'on';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '443';
        
        $this->assertEquals('https://example.com', Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with port is 80 
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithPortIs443()
    {
        if (!isset($_SERVER)) {
            $_SERVER = array();
        }
        $_SERVER['HTTPS']       = '';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        
        $this->assertEquals('http://example.com', Fisma_Url::baseUrl());
    }

    /**
     * Test baseUrl constructor with SERVER_NAME and SERVER_PORT
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithServerNameAndPortSet()
    {
        if (!isset($_SERVER)) {
            $_SERVER = array();
        }
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = 8080;
        $_SERVER['HTTPS']       = '';
        
        $this->assertEquals('http://example.com:8080', Fisma_Url::baseUrl());
    }

    /**
     * Test currentUrl constructor with requestUri
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testCurrentUrlConstructorWithRequestUri()
    {
        if (!isset($_SERVER)) {
            $_SERVER = array();
        }
        $_SERVER['REQUEST_URI'] = '/test/this/';
                
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
        Fisma::configuration()->setConfig('host_url', 'http://example.com');

        $this->assertEquals('http://example.com/test/this/', Fisma_Url::currentUrl());
    }

    /**
     * Test currentUrl constructor with requestUri including params
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testCurrentUrlConstructorWithRequestUriIncludingParams()
    {
        if (!isset($_SERVER)) {
            $_SERVER = array();
        }
        $_SERVER['REQUEST_URI'] = '/test/this/?param=1&param2=2';
        $_SERVER['HTTPS']       = '';
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['SERVER_PORT'] = '';
        
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
        Fisma::configuration()->setConfig('host_url', 'http://example.com');

        $this->assertEquals('http://example.com/test/this/?param=1&param2=2', Fisma_Url::currentUrl());
    }

    /**
     * Test customUrl with requestUri start with '/'
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testCustomUrlWithRequestUri()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
        Fisma::configuration()->setConfig('host_url', 'http://example.com');
        // The requestUri start with '/'
        $requestUri = '/test';
        $this->assertEquals('http://example.com/test', Fisma_Url::customUrl($requestUri));
    }

    /**
     * Test baseUrl constructor with host_url and server info
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testBaseUrlConstructorWithHostUrlAndServerInfo()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
        Fisma::configuration()->setConfig('host_url', 'http://test');
        $_SERVER = array();
        $_SERVER['HTTPS']       = '';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '';

        $this->assertEquals('http://test', Fisma_Url::currentUrl());
    }

    public function testCurrentUrlWithNoServerParms()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
        Fisma::configuration()->setConfig('host_url', 'http://test');
        $_SERVER = array();
        $this->assertEquals('http://test', Fisma_Url::currentUrl());
    }
}
