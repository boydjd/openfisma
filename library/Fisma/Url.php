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
 * Url functions for OpenFISMA
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Url
 */
class Fisma_Url
{
    /**
     * Return the base URL.
     * 
     * @return string baseUrl string
     */    
    static function baseUrl()
    {
        if (isset($_SERVER) && 
            array_key_exists('SERVER_NAME', $_SERVER) && 
            array_key_exists('SERVER_PORT', $_SERVER) && 
            array_key_exists('HTTP_HOST', $_SERVER)) {
            // Get the scheme http or https
            $scheme = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
            // Get the http host
            $name = (!empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : null;
            $port = (!empty($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : null;
            $port = ($port != 80 || 443) ? ':' . $port : null;
            $host = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : $name . $port;
            return $scheme . '://' . $host;
        } else {
            return Fisma::configuration()->getConfig('host_url');
        }
    }
    
    /**
     * Return the current page URL.
     * 
     * @return string currentUrl string
     */
    static function currentUrl()
    {
        if (isset($_SERVER) && array_key_exists('REQUEST_URI', $_SERVER)) {
            $uri = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;
            return self::baseUrl() . $uri;
        } else {
            return self::baseUrl();
        }
    }
    
    /**
     * Return the custom URL.
     * like http://site.com/test, the $requestUri is /test.
     * 
     * @param  string $requestUri  The relatively request path
     * @return string customUrl string
     */
    static function customUrl($requestUri)
    {
        // If the string of requestUri includes '/', './' or '../' will be cut off at the outset. 
        if (!empty($requestUri) && is_string($requestUri)) {
            $path = preg_replace('/^\.{0,2}\//', '', $requestUri);
        }
        
        return self::baseUrl() . '/' . $path;
    }
}
