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
 * Override Zend_View to provide enhanced escape() method 
 * 
 * @uses Zend_View
 * @package Fisma
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_View extends Zend_View
{
    /**
     * Escape modifier, with support for multiple types of escaping. 
     * Adopted from Smarty 3.x, and slightly modified.
     * 
     * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
     * @author Monte Ohrt <monte at ohrt dot com>
     * @param string $string input string
     * @param string $escType escape type
     * @return string escaped input string
     * @license http://www.gnu.org/licenses/lgpl-3.0.txt LGPL
     */
    public function escape($string, $escType = 'html')
    {
        switch ($escType) {
            case 'html':
                return htmlspecialchars($string, ENT_QUOTES, $this->getEncoding());

            case 'htmlall':
                return htmlentities($string, ENT_QUOTES, $this->getEncoding());

            case 'url':
                return rawurlencode($string);

            case 'urlpathinfo':
                return str_replace('%2F', '/', rawurlencode($string));

            case 'quotes':
                // escape unescaped single quotes
                return preg_replace("%(?<!\\\\)'%", "\\'", $string);

            case 'hex':
                // escape every character into hex
                $return = '';
                for ($x = 0; $x < strlen($string); $x++) {
                    $return .= '%' . bin2hex($string[$x]);
                } 
                return $return;

            case 'hexentity':
                $return = '';
                for ($x = 0; $x < strlen($string); $x++) {
                    $return .= '&#x' . bin2hex($string[$x]) . ';';
                }
                return $return;

            case 'decentity':
                $return = '';
                for ($x = 0; $x < strlen($string); $x++) {
                    $return .= '&#' . ord($string[$x]) . ';';
                }
                return $return;

            case 'javascript':
                // escape quotes and backslashes, newlines, etc.
                return strtr(
                    $string, array(
                        '\\' => '\\\\', 
                        "'" => "\\'", 
                        '"' => '\\"', 
                        "\r" => '\\r', 
                        "\n" => '\\n', 
                        '</' => '<\/'
                    )
                );

            case 'json':
                return json_encode($string);
                
            case 'mail':
                // safe way to display e-mail address on a web page
                return str_replace(array('@', '.'), array(' [AT] ', ' [DOT] '), $string);

            case 'nonstd':
                // escape non-standard chars, such as ms document quotes
                $return = '';
                for ($x = 0, $len = strlen($string); $x < $len; $x++) {
                    $ord = ord(substr($string, $x, 1));
                    // non-standard char, escape it
                    if ($ord >= 126) {
                        $return .= '&#' . $ord . ';';
                    } else {
                        $return .= substr($string, $x, 1);
                    }
                }
                return $return;

            case 'none':
                return $string;

            default:
                throw new Fisma_Zend_Exception('Requested escaping type is not available!');
        }
    }
}
