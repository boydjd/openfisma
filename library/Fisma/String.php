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
 * @version    $Id$
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
        $html = preg_replace('/[ ]*\R[ ]*/', "\n", $html);
        $html = preg_replace('/^\s+/', '', $html);
        $html = preg_replace('/\s+$/', '', $html);
        $html = preg_replace('/ +/', ' ', $html);

        // Character set encoding -- input charset is a guess
        $html = iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $html);

        return $html;
    }
}
