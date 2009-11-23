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
 * <http://www.gnu.org/licenses/>.
 */

require_once(realpath(dirname(__FILE__) . '/../../FismaUnitTest.php'));

/**
 * This test is for fisma cookie testing.
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Test
 * @version    $Id$
 */
class Test_Fisma_Cookie extends Test_FismaUnitTest
{
    /**
     * testPrepareSecureCookie 
     *
     * @dataProvider prepareProvider
     *
     * @param string $name 
     * @param string $value 
     * @param boolean $secure
     * @param array $expected 
     * @access public
     * @return void
     */
    public function testPrepareCookie($name, $value, $secure, array $expected)
    {
        $cookie = Fisma_Cookie::prepare($name, $value, $secure);
        $this->assertEquals($expected, $cookie);
    }

    /**
     * testGetCookie 
     *
     * @dataProvider cookieProvider
     *
     * @param array $cookie 
     * @param string $key 
     * @param string $expected 
     * @access public
     * @return void
     */
    public function testGetCookie(array $cookie, $key, $expected)
    {
        $cookie = Fisma_Cookie::get($cookie, $key);
        $this->assertEquals($expected, $cookie);
    }

    /**
     * testGetUnavailableCookie 
     *
     * @dataProvider badCookieProvider
     * @expectedException Fisma_Exception
     *
     * @param array $cookie 
     * @param mixed $key 
     * @param mixed $expected 
     * @access public
     * @return void
     */
    public function testGetUnavailableCookie(array $cookie, $key, $expected)
    {
        $cookie = Fisma_Cookie::get($cookie, $key);
    }

    /**
     * secureProvider 
     *
     * @static
     * @access public
     * @return array
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
     * cookieProvider 
     * 
     * @static
     * @access public
     * @return void
     */
    static public function cookieProvider()
    {
        return array(
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie1', 'randomData'),
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie2', 'moreRandomData')
        );
    }

    /**
     * badCookieProvider 
     * 
     * @static
     * @access public
     * @return void
     */
    static public function badCookieProvider()
    {
        return array(
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie3', 'randomData'),
            array(array('cookie1' => 'randomData', 'cookie2' => 'moreRandomData'), 'cookie4', 'moreRandomData')
        );
    }

}
