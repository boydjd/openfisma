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
        $fname = '_escape' . ucfirst($escType);

        if (method_exists($this, $fname)) {
            return $this->$fname($string);
        } else {
            throw new Fisma_Zend_Exception('Requested escaping type is not available!');
        }
    }

    /**
     *  Use htmlspecialchars to escape string
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeHtml($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, $this->getEncoding());
    }

    /**
     *  Use htmlentities to escape string
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeHtmlall($string)
    {
        return htmlentities($string, ENT_QUOTES, $this->getEncoding());
    }

    /**
     *  Use rawurlencode to escape url
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeUrl($string)
    {
        return rawurlencode($string);
    }

    /**
     *  Use rawurlencode and replace %2F with / to escape url
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeUrlpathinfo($string)
    {
        return str_replace('%2F', '/', rawurlencode($string));
    }

    /**
     * Escape unescaped single quotes
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeQuotes($string)
    {
        return preg_replace("%(?<!\\\\)'%", "\\'", $string);
    }

    /**
     * Escape every character into hex
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeHex($string)
    {
        $return = '';
        for ($x = 0; $x < strlen($string); $x++) {
            $return .= '%' . bin2hex($string[$x]);
        } 

        return $return;
    }

    /**
     * Escape every characster into hex entity
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeHexentity($string)
    {
        $return = '';
        for ($x = 0; $x < strlen($string); $x++) {
            $return .= '&#x' . bin2hex($string[$x]) . ';';
        }

        return $return;
    }

    /**
     * Escape every characster into Dec entity
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeDecentity($string)
    {
        $return = '';
        for ($x = 0; $x < strlen($string); $x++) {
            $return .= '&#' . ord($string[$x]) . ';';
        }

        return $return;
    }

    /**
     * Escape quotes and backslashes, newlines, etc.
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeJavascript($string)
    {
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
    }

    /**
     * Use json_encode to escape string  
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeJson($string)
    {
        return json_encode($string);
    }            
     
    /**
     * Safe way to display e-mail address on a web page
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeMail($string)
    {
        return str_replace(array('@', '.'), array(' [AT] ', ' [DOT] '), $string);
    }
     
    /**
     * Escape non-standard chars, such as ms document quotes
     * 
     * @param string The string that needs to be escaped 
     * @return string 
     */
    protected function _escapeNonstd($string)
    {
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
    }

    /**
     * Do not escape
     * 
     * @param mixed
     * @return mixed 
     */
    protected function _escapeNone($string)
    {
        return $string;
    }
}
