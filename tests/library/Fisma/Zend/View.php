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
 * Class description
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Zend_View extends Test_Case_Unit
{
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

        $this->setExpectedException('Fisma_Zend_Exception', 'Requested escaping type is not available!');
        $testView->escape($originalString, 'a sample non-supported type');
    }
}
