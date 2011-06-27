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
 * Test_Fisma_Cookie_Test 
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 * 
 * @uses       Test_FismaUnitTest
 */
class Test_Library_Fisma_Cookie extends Test_FismaUnitTest
{
    /**
     * To test the method PrapareCookie
     * 
     * @param string $name The specified cookie name
     * @param string $value The specified cookie value
     * @param boolean $secure Indicates if needs to secure cookie
     * @param array $expected The expected result
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     * @dataProvider prepareProvider
     */
    public function testPrepareCookie($name, $value, $secure, array $expected)
    {
        $cookie = Fisma_Cookie::prepare($name, $value, $secure);
        $this->assertEquals($expected, $cookie);
    }

    /**
     * To test the method GetCookie
     * 
     * @param array $cookie The cookie array to search
     * @param string $key The specified cookie key to found
     * @param string $expected The expected result
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     * @dataProvider cookieProvider
     */
    public function testGetCookie(array $cookie, $key, $expected)
    {
        $cookie = Fisma_Cookie::get($cookie, $key);
        $this->assertEquals($expected, $cookie);
    }

    /**
     * To test the method GetUnavailableCookie
     * 
     * @param array $cookie The cookie array to search
     * @param string $key The specified cookie key to found
     * @param string $expected The expected result
     * @return void
     * @dataProvider badCookieProvider
     * @expectedException Fisma_Zend_Exception
     */
    public function testGetUnavailableCookie(array $cookie, $key, $expected)
    {
        $cookie = Fisma_Cookie::get($cookie, $key);
    }

    /**
     * testPrepareCookieWithoutSecureOptions 
     * 
     * @param mixed $name 
     * @param mixed $value 
     * @param mixed $secure 
     * @param array $expected 
     * @access public
     * @return void
     * @dataProvider prepareProvider
     */
    public function testPrepareCookieWithoutSecureOptions($name, $value, $secure, array $expected)
    {
        if (Zend_Session::getOptions('cookie_secure')) {
            $this->markTestSkipped(
                'Secure cookies are enabled, so this test cannot be run.'
            );
        }
        $cookie = Fisma_Cookie::prepare($name, $value);
        $this->assertFalse((boolean) $cookie['secure']);
    }

    /**
     * testSet 
     * 
     * @access public
     * @return void
     */
    public function testSet()
    {
        Fisma_Cookie::set('hi', 'there');
    }

    /**
     * testSetInWebAppMode 
     * 
     * @access public
     * @return void
     */
    public function testSetInWebAppMode()
    {
        Fisma::initialize(Fisma::RUN_MODE_WEB_APP);
        Fisma_Cookie::set('hi', 'there');
        Fisma::initialize(Fisma::RUN_MODE_TEST);
    }

    /**
     * Create cookie data in array
     * 
     * @return array The cookie array which provides test data to test method
     */
    static public function prepareProvider()
    {
        return array(
            array('cookie1', 'value of cookie 1', true,
                array('name' => 'cookie1', 
                    'value' => 'value of cookie 1', 'expire' => false, 
                    'path' => '/', 'domain' => '', 'secure' => true
                )
            ),
            array('cookie2', 'value of cookie 2', false,
                array('name' => 'cookie2', 
                    'value' => 'value of cookie 2', 'expire' => false, 
                    'path' => '/', 'domain' => '', 'secure' => false 
                )
            )
        );
    }

    /**
     * Create cookie data in array
     * 
     * @return array The cookie array which provides test data to test method
     */
    static public function cookieProvider()
    {
        return array(
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie1', 'randomData'),
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie2', 'moreRandomData')
        );
    }

    /**
     * Create bad cookie data in array
     * 
     * @return array The bad cookie array which provides test data to test method
     */
    static public function badCookieProvider()
    {
        return array(
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie3', 'randomData'),
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie4', 'moreRandomData')
        );
    }

}
