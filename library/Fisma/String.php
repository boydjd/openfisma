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

/**
 * String functions for OpenFISMA
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_String
 */
class Fisma_String
{
    /**
     * The default character set used when generating a random string
     */
    const RANDOM_ALLOWED_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
    
    /**
     * Return a random string of the requested length
     * 
     * @param int $length The length of string to be generated
     * @param string $allowedCharacters The allowed characters during generation
     * @return string The generated random string
     */
    static function random($length, $allowedCharacters = self::RANDOM_ALLOWED_CHARACTERS)
    {
        $setSize = strlen($allowedCharacters) - 1;
        
        // Reset the seed, just in case rand has been seeded elsewhere during execution of the request.
        srand();

        $random = '';
        for ($i = 1; $i <= $length; $i++) {
            $random .= $allowedCharacters{rand(0, $setSize)};
        }
        
        return $random;
    }

    /**
     * Return lorem ipsum style text 
     * 
     * @param mixed $wordCount Number of words 
     * @param string $format Can be 'html', 'txt', or 'plain'
     * @param mixed $loremIpsum Whether or not the content should begin with 'Lorem ipsum'
     * @return string The generated lorem ipsum text
     */
    static function loremIpsum($wordCount, $format = 'txt', $loremIpsum = false)
    {
        $lorem = new Fisma_String_LoremIpsum();
        return $lorem->getContent($wordCount, $format, $loremIpsum);
    }
    
    /**
     * A helper for converting HTML to roughly equivalent plain text
     * 
     * This is *NOT* intended to satisfactorily strip malicious content from HTML. This assumes the input is safe 
     * markup which just needs to be coerced into plain text format.
     * 
     * @param string $html HTML input
     * @return string Plain text output
     */
    static function htmlToPlainText($html)
    {
        // Remove line feeds. They are replaced with spaces to prevent the next word on the next line from adjoining
        // the last word on the previous line, but consecutive spaces are culled out later.
        $html = str_replace(chr(10), ' ', $html);
        $html = str_replace(chr(13), ' ', $html);

        // Convert <p> and <br> into unix line endings
        $html = preg_replace('/<p[^>]*?>/i', "\n", $html);
        $html = preg_replace('/<\/p[^>]*?>/i', "\n", $html);
        $html = preg_replace('/<br[^>]*?>/i', "\n", $html);
        
        // Convert list tags into plain text
        $html = preg_replace('/<[uo]l[^>]>/i', '', $html);
        $html = preg_replace('/<\/[uo]l[^>]>/i', "\n", $html);
        $html = preg_replace('/<li[^>]*?>/i', "\n* ", $html);
        $html = preg_replace('/<\/li>/i', '', $html);
        
        // Remove any remaining tags and decode entities
        $html = strip_tags($html);
        $html = html_entity_decode($html);
        
        // Remove excess whitespace
        $html = preg_replace('/[ ]*(?>\r\n|\n|\x0b|\f|\r|\x85)[ ]*/', "\n", $html);
        $html = preg_replace('/^\s+/', '', $html);
        $html = preg_replace('/\s+$/', '', $html);
        $html = preg_replace('/ +/', ' ', $html);

        return $html;
    }

    /**
     * Convert plain text into a similar HTML representation 
     * 
     * @param string $plainText The plain text that needs to be marked up
     * @return string The rendered HTML snipped of the plain text 
     */
    static function textToHtml($plainText)
    {
        // Replace consecutive newlines with </p><p> and single newlines with <br>
        $search = array("\n\n", "\n");
        $replace = array('</p><p>', '<br>');

        $html = '<p>' . trim($plainText) . '</p>';
        $html = str_replace($search, $replace, $html);

        return $html;
    }
    
    /**
     * Sanitize a string for echoing into a Javascript
     * 
     * @param string $string An unsanitized string
     * @return string
     */
    static function escapeJsString($string)
    {
        // Escape quotation marks
        $string = addslashes($string);

        // Escape newlines characters
        $string = str_replace("\n", "\\n", $string);

        return $string;
    }
    
    /**
     * Convert a string into a valid javascript variable name
     * 
     * @see http://www.w3schools.com/js/js_variables.asp
     * 
     * @param $string $name
     */
    static function convertToJavascriptName($name)
    {
        // Javascript variables cannot start with numbers, so prepend an underscore if necessary
        if (is_numeric($name{0})) {
            $name = '_' . $name;
        }

        // Replace any illegal characters with an underscore
        $name = preg_replace('/[^A-Za-z0-9]/', '_', $name);

        return $name;        
    }

    /**
     * Change the first character of a string to lowercase 
     * 
     * @param mixed $string 
     * @return string 
     */
    static function lcfirst($string)
    {
        $string[0] = strtolower($string[0]);  
        return $string;
    }

    /**
     * Replace invalid characters with valid ones 
     * 
     * @param mixed $string 
     * @return string 
     */
    static public function replaceInvalidChars($string)
    {
        $search = array(chr(0xe2) . chr(0x80) . chr(0x98),  // '
                chr(0xe2) . chr(0x80) . chr(0x99),  // '
                chr(0xe2) . chr(0x80) . chr(0x9c),  // "
                chr(0xe2) . chr(0x80) . chr(0x9d),  // "
                chr(0xe2) . chr(0x80) . chr(0x93),  // em dash
                chr(0xe2) . chr(0x80) . chr(0x94),  // en dash
                chr(0xe2) . chr(0x80) . chr(0xa6)); // ...

        $replace = array(
                '\'',
                '\'',
                '"',
                '"',
                '-',
                '-',
                '...');

        return str_replace($search, $replace, $string);
    }

    /**
     * A helper for converting plaintext to text suitable for use in the PDF and excel generator.
     * 
     * @param string $text plain text input
     * @return string PDF and excel text output
     */
    static function plainTextToReportText($text)
    {
        // Remove excess whitespace
        $text = preg_replace('/[ ]*(?>\r\n|\n|\x0b|\f|\r|\x85)[ ]*/', "\n", $text);
        $text = preg_replace('/\s+$/', '', $text);
        $text = preg_replace('/\b +/', ' ', $text);

        return $text;
    }

    /**
     * A helper for converting string from UTF-8 to ISO-8859-1//TRANSLIT.
     * 
     * @param UTF-8 encoded string $text
     * @return ISO-8859-1//TRANSLIT encoded string 
     */
    static function convertToLatin1($text)
    {
        return iconv("UTF-8", "ISO-8859-1//TRANSLIT", $text);
    }

    /*
    * A helper for converting HTML to text suitable for use in the PDF generator.
    * 
    * This is *NOT* intended to satisfactorily strip malicious content from HTML. This assumes the input is safe 
    * markup which just needs to be coerced into plain text format.
    *
    * This function behaves in a similar manner to htmlToPlainText(), but allows HTML entities as well
    * as I, B and U formatting tags.
    * 
    * @param string $html HTML input
    * @return string PDF text output
    */
    static function htmlToPdfText($html)
    {
        // Remove line feeds. They are replaced with spaces to prevent the next word on the next line from adjoining
        // the last word on the previous line, but consecutive spaces are culled out later.
        $html = str_replace(chr(10), ' ', $html);
        $html = str_replace(chr(13), ' ', $html);
 
        // Convert <p> and <br> into unix line endings
        $html = preg_replace('/<p[^>]*?>/i', "\n", $html);
        $html = preg_replace('/<\/p[^>]*?>/i', "\n", $html);
        $html = preg_replace('/<br[^>]*?>/i', "\n", $html);

        // Convert list tags into plain text
        $html = preg_replace('/<[uo]l[^>]>/i', '', $html);
        $html = preg_replace('/<\/[uo]l[^>]>/i', "\n", $html);
        $html = preg_replace('/<li[^>]*?>/i', "\n* ", $html);
        $html = preg_replace('/<\/li>/i', '', $html);

        // Remove any remaining tags, except for I, B and U
        $html = strip_tags($html, '<i><b><u>');

        // Remove excess whitespace
        $html = preg_replace('/[ ]*\R[ ]*/', "\n", $html);
        $html = preg_replace('/^\s+/', '', $html);
        $html = preg_replace('/\s+$/', '', $html);
        $html = preg_replace('/ +/', ' ', $html);

        $html = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $html);

        return $html;
    }   
}
