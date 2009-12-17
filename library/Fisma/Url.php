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
 * @version    $Id$
 */
class Fisma_Url
{
    /**
     * Return the current page URL (optionally with request URI) and only used in web, not in CLI.
     * 
     * @param  string|boolean $requestUri  [optional] if true, the request URI found in $_SERVER will be appended
     *                                     as a path. If a string is given, it will be appended as a path. Default
     *                                     is to not append any path.
     * @return string url string
     */
    static function currentPageUrl($requestUri = null)
    {
        // Get the scheme http or https
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] === true)) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }

        // Get the http host
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'])) {
            $name = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];

            if (($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443)) {
                $host = $name;
            } else {
                $host = $name . ':' . $port;
            }
        }

        // Get the uri
        if ($requestUri === true) {
            $uri = $_SERVER['REQUEST_URI'];
        } else if (is_string($requestUri)) {
            $uri = $requestUri;
        } else {
            $uri = '';
        }

        $url = $scheme . '://' . $host . $uri;
        return $url;
    }
}
