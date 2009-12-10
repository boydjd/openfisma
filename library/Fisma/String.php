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
}
