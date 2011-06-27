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
 * Tests for Fisma_String
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_String extends Test_FismaUnitTest
{
    /**
     * Test randomness of string generation
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testRandomStringRandomness()
    {
        $this->assertNotEquals(Fisma_String::random(10), Fisma_String::random(10));
    }

    /**
     * Test random string only uses default allowed characters
     * 
     * @return void
     * @throws PHPUnit_Util_InvalidArgumentHelper if the specified regular expression or random string argument fails
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testRandomStringDefaultAllowedCharacters()
    {
        $this->assertRegExp('([A-Z,a-z,0-9]*)', Fisma_String::random(10));
    }

    /**
     * Test random string only uses allow characters
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testRandomStringAllowedCharacters()
    {
        $this->assertEquals(Fisma_String::random(2, 'AA'), 'AA');
    }

    /**
     * Test random string is the requested length
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testRandomStringLength()
    {
        $this->assertEquals(strlen(Fisma_String::random(22)), 22);
    }
    
    /**
     * Test HTML to plaintext converts paragraphs and line breaks
     */
    public function testHtmlToPlainTextParagraphsAndLineBreaks()
    {
        $html = " <p attrib='value'> First \r\n paragraph.</p>  <p>This &amp; that.</p> More <br> line <br /> breaks";
        $plaintext = "First paragraph.\n\nThis & that.\nMore\nline\nbreaks";
        
        $this->assertEquals($plaintext, Fisma_String::htmlToPlainText($html));
    }
    
    /**
     * Test handling of list items in the HTML to plain text converter
     */
    public function testHtmlToPlainTextListItems()
    {
        $html = "<p>I will now list things:</p> <ul><li class='stuff' />Item 1 <li> Item 2 </li> <li> Item 3 </li>";
        $plaintext = "I will now list things:\n\n* Item 1\n* Item 2\n* Item 3";
        
        $this->assertEquals($plaintext, Fisma_String::htmlToPlainText($html));
    }
    
    /**
     * Test white space in between tags should be preserved
     */
    public function testHtmlToPlainTextWhitespaceBetweenTags()
    {
        $html = "<p> Spaces <b>between</b> <i>tagged</i> words.</p>";
        $plaintext = "Spaces between tagged words.";
        
        $this->assertEquals($plaintext, Fisma_String::htmlToPlainText($html));
    }
   
    /**
     * Test handling of consecutive line breaks in the text to HTML converter
     */
    public function testTextToHtmlConsecutiveLineBreaksChangedToParagraphs()
    {
        $plainText = "Lorem ipsum.\n\nHello.\n\nAnother paragraph.";
        $html      = "<p>Lorem ipsum.</p><p>Hello.</p><p>Another paragraph.</p>";

        $this->assertEquals($html, Fisma_String::textToHtml($plainText));
    }

    /**
     * Test handling of single line breaks in the text to HTML converter
     */
    public function testTextToHtmlSingleLineBreaksChangedToBrTag()
    {
        $plainText = "Lorem ipsum.\nHello.\nAnother line.";
        $html      = "<p>Lorem ipsum.<br>Hello.<br>Another line.</p>";

        $this->assertEquals($html, Fisma_String::textToHtml($plainText));
    }

    /**
     * Test handling of a combination of consecutive and single line breaks in the text to HTML converter
     */
    public function testTextToHtmlParagraphsAndBrTags()
    {
        $plainText = "Lorem ipsum.\n\nHello.\nA line.";
        $html      = "<p>Lorem ipsum.</p><p>Hello.<br>A line.</p>";

        $this->assertEquals($html, Fisma_String::textToHtml($plainText));
    }
    
    /**
     * Test javascript string escaping
     */
    public function testEscapeJsString()
    {
        $unescaped = "This string contains ' quotes \" and \n newlines";
        $expected  = "This string contains \' quotes \\\" and \\n newlines";
        
        $this->assertEquals($expected, Fisma_String::escapeJsString($unescaped));
    }
    
    /**
     * Convert an arbitrary string that contains illegal javascript characters to a valid javascript variable name
     */
    public function testConvertToJavascriptName()
    {
        $original = '0 Day Exploits!';
        $expected = '_0_Day_Exploits_';
        
        $this->assertEquals($expected, Fisma_String::convertToJavascriptName($original));
    }

    /**
     * Convert first character of string to lower case 
     */
    public function testLowercaseFirstCharacterOfString()
    {
        $this->assertEquals('hello', Fisma_String::lcfirst('Hello'));
    }

    /**
     * testLoremIpsum 
     */
    public function testLoremIpsum()
    {
        $this->assertGreaterThan(1, strlen(Fisma_String::loremIpsum(1)));
    }

    /**
     * testReplaceInvalidChars 
     * 
     * @access public
     * @return void
     */
    public function testReplaceInvalidChars()
    {
        $this->assertEquals("'", Fisma_String::replaceInvalidChars('‘'));
        $this->assertEquals("'", Fisma_String::replaceInvalidChars('’'));
        $this->assertEquals('"', Fisma_String::replaceInvalidChars('“'));
        $this->assertEquals('"', Fisma_String::replaceInvalidChars('”'));
        $this->assertEquals('-', Fisma_String::replaceInvalidChars('–'));
        $this->assertEquals('-', Fisma_String::replaceInvalidChars('—'));
        $this->assertEquals('...', Fisma_String::replaceInvalidChars('…'));
    }

    /**
     * Test plaintextToReportText reserve the leading white spaces 
     * and remove excess white spaces.
     */
    public function testPlainTextToReportTextReserveLeadingWhiteSpaces()
    {
        $text = "    First     line.    ";
        $reporttext = "    First line.";
        
        $this->assertEquals($reporttext, Fisma_String::plainTextToReportText($text));
    }

    /**
     * Test convertUTF8ToISOTRANSLIT convert UTF-8 encoded string to ISO-8859-1//TRANSLIT 
     */
    public function testConvertToLatin1()
    {
        $text = "This is the Euro symbol '€'";
        $translitText = "This is the Euro symbol 'EUR'"; 
        $this->assertEquals($translitText, Fisma_String::convertToLatin1($text));
    }

    /*
    * Test HTML to PDF text converts paragraphs and line breaks
    */
    public function testHtmlToPdfTextParagraphsAndLineBreaks()
    {
        $html = " <p attrib='value'> First \r\n paragraph.</p>  <p>This and that.</p> More <br> line <br /> breaks";
        $pdftext = "First paragraph.\n\nThis and that.\nMore\nline\nbreaks";
        $this->assertEquals($pdftext, Fisma_String::htmlToPdfText($html));
    }

    /**
    * Test handling of list items in the HTML to pdf text converter
    */
    public function testHtmlToPdfTextListItems()
    {
        $html = "<p>I will now list things:</p> <ul><li class='stuff' />Item 1 <li> Item 2 </li> <li> Item 3 </li>";
        $pdftext = "I will now list things:\n\n* Item 1\n* Item 2\n* Item 3";
        $this->assertEquals($pdftext, Fisma_String::htmlToPdfText($html));
    }
    
    /**
    * Test white space in between tags should be preserved
    */
    public function testHtmlToPdfTextWhitespaceBetweenTags()
    {
        $html = "<p> Spaces <span>between</span> <span>tagged</span> words.</p>";
        $pdftext = "Spaces between tagged words.";
        $this->assertEquals($pdftext, Fisma_String::htmlToPdfText($html));
    }

    /**
    * Test to ensure allowed formatting tags are preserved in PDF.
    */
    public function testHtmlToPdfAllowedFormatting()
    {
        $html = "<p> Style <b>some</b> <i>neat</i> words.</p>";
        $pdftext = "Style <b>some</b> <i>neat</i> words.";
        $this->assertEquals($pdftext, Fisma_String::htmlToPdfText($html));
    }
}
