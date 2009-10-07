<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 
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
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 * @package   Test
 */

require_once(realpath(dirname(__FILE__) . '/../../FismaUnitTest.php'));

/**
 * Test_Fisma_Cookie_Test 
 * 
 * @uses Test
 * @uses _FismaUnitTest
 * @package Test 
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com})
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license {@link http://www.openfisma.org/content/license}
 */
class Test_Fisma_Cookie_Test extends Test_FismaUnitTest
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
