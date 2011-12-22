<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * Tests for Fisma_Zend_View
 * 
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Case_Unit
 */
class Test_Library_Fisma_Zend_View extends Test_Case_Unit
{
    /**
     * Test _escapeHtml()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testHtmlEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "Jane & 'Tarzan'";
        $this->assertEquals('Jane &amp; &#039;Tarzan&#039;', $view->escape($str, 'html'));
    }

    /**
     * Test _escapeHtmlall()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testHtmlallEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "A 'quote' is <b>bold</b>";
        $this->assertEquals('A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;', $view->escape($str, 'htmlall'));

        // Output empty string with using ENT_QUOTES and UTF-8 parameters in htmlentities()  
        $str = "\x8F!!!";
        $this->assertEquals('', $view->escape($str, 'htmlall'));
    }

    /**
     * Test _escapeUrl()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testUrlEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "foo @+%/";
        $this->assertEquals('foo%20%40%2B%25%2F', $view->escape($str, 'url'));
    }

    /**
     * Test _escapeUrlpathinfo()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testUrlpathinfoEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "foo @+%/";
        $this->assertEquals('foo%20%40%2B%25/', $view->escape($str, 'urlpathinfo'));
    }

    /**
     * Test _escapeQuotes()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testQuotesEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "single'";
        $this->assertEquals("single\'", $view->escape($str, 'quotes'));
    }

    /**
     * Test _escapeHex()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testHexEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "hexchar";
        $this->assertEquals("%68%65%78%63%68%61%72", $view->escape($str, 'hex'));
    }

    /**
     * Test _escapeHexentity()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testHexentityEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "hexchar";
        $this->assertEquals('&#x68;&#x65;&#x78;&#x63;&#x68;&#x61;&#x72;', $view->escape($str, 'hexentity'));
    }

    /**
     * Test _escapeDecentity()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testDecentityEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "decentity";
        $this->assertEquals('&#100;&#101;&#99;&#101;&#110;&#116;&#105;&#116;&#121;', $view->escape($str, 'decentity'));
    }

    /**
     * Test _escapeJavascript()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testJavascriptEscape()
    {
        $view = new Fisma_Zend_View();
        $str = "'\r\n</";
        $this->assertEquals("\'\\r\\n<\/", $view->escape($str, 'javascript'));
    }

    /**
     * Test _escapeJson()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testJsonEscape()
    {
        $view = new Fisma_Zend_View();
        $arr = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);
        $this->assertEquals('{"a":1,"b":2,"c":3,"d":4,"e":5}', $view->escape($arr, 'json'));
    }

    /**
     * Test _escapeMail()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testMailEscape()
    {
        $view = new Fisma_Zend_View();
        $str = '@.';
        $this->assertEquals(' [AT]  [DOT] ', $view->escape($str, 'mail'));
    }

    /**
     * Test _escapeNonstd()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testNonstdEscape()
    {
        $view = new Fisma_Zend_View();
        $str = 'non standard “ ”';
        $this->assertEquals('non standard &#226;&#128;&#156; &#226;&#128;&#157;', $view->escape($str, 'nonstd'));
    }

    /**
     * Test _escapeNone()
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testNoneEscape()
    {
        $view = new Fisma_Zend_View();
        $str = 'noescape';
        $this->assertEquals('noescape', $view->escape($str, 'none'));
    }

    /**
     * test escaping, assuming default UTF-8 encoding
     * @return void
     */
    public function testEscape()
    {
        $testView = new Fisma_Zend_View();

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '&lt;a&amp;b+c&gt;/&#039;d&#039;';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'html'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '&lt;a&amp;b+c&gt;/&#039;d&#039;';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'htmlall'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '%3Ca%26b%2Bc%3E%2F%27d%27';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'url'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '%3Ca%26b%2Bc%3E/%27d%27';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'urlpathinfo'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '<a&b+c>/\\\'d\\\'';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'quotes'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '%3c%61%26%62%2b%63%3e%2f%27%64%27';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'hex'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '&#x3c;&#x61;&#x26;&#x62;&#x2b;&#x63;&#x3e;&#x2f;&#x27;&#x64;&#x27;';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'hexentity'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '&#60;&#97;&#38;&#98;&#43;&#99;&#62;&#47;&#39;&#100;&#39;';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'decentity'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '<a&b+c>/\\\'d\\\'';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'javascript'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = '"<a&b+c>\/\'d\'"';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'json'));

        $originalString = 'ac.count@do.ma.in';
        $escapedString = 'ac [DOT] count [AT] do [DOT] ma [DOT] in';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'mail'));

        $originalString = '你好!';
        $escapedString = '&#228;&#189;&#160;&#229;&#165;&#189;!';
        $this->assertEquals($escapedString, $testView->escape($originalString, 'nonstd'));

        $originalString = '<a&b+c>/\'d\'';
        $escapedString = $originalString;
        $this->assertEquals($escapedString, $testView->escape($originalString, 'none'));
    }

    /**
     * Test with non-supported escape type
     * 
     * @return void
     */
    public function testEscapeWithNonSupportedType()
    {
        $this->setExpectedException('Fisma_Zend_Exception', 'Requested escaping type is not available!');
        $testView = new Fisma_Zend_View();
        $testView->escape($originalString, 'a sample non-supported type');
    }
}
