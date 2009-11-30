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
 * The base class for interaction with Yahoo! User Interface Library (YUI)
 * 
 * Implemented as a static class in order to make the calling syntax simple.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 * @version    $Id$
 */
class Fisma_Yui
{
    /**
     * @var array The YUI libraries to include, used as a set
     */
    static private $_includes = array();

    /**
     * Prevent instantiation
     */
    function __construct() 
    {
        throw new Fisma_Exception('This is a static class; do not create instances of it.');
    }

    /**
     * Adds the specified YUI library into the include list. 
     * 
     * The include list is a set, meaning that
     * each item can only occur in the list once. To achieve this, the array key holds the name of the
     * library, and the value is just a placeholder.
     * 
     * @param string $library Name of YUI library, e.g. "yahoo-dom-event"
     */
    static function includeLibrary($library) 
    {
        if ('yahoo-dom-event' != $library || !Fisma::debug()) {
            self::$_includes[$library] = true;
        } else {
            // Special case: in debug mode, convert the "yahoo-dom-event" library request into
            // its 3 component libraries: yahoo, dom, & event. This improves readability when
            // debugging.
            self::$_includes['yahoo'] = true;
            self::$_includes['dom'] = true;
            self::$_includes['event'] = true;
        }
    }
    
    /**
     * Renders script tags to include the specified YUI libraries
     * 
     * @return string
     */
    static function printIncludes() 
    {
        $render = '';
        $yuiPath = Fisma::getPath('yui');
       
        foreach (array_keys(self::$_includes) as $include) {
            // Use the debug version of the file when in debugging mode, if possible. In production
            // mode, use the compressed ("-min") version if available.
            if (!Fisma::debug() && file_exists("$yuiPath/$include/$include-min.js")) {
                $source = "/yui/$include/$include-min.js";
            } elseif (Fisma::debug() && file_exists("$yuiPath/$include/$include-debug.js")) {
                $source = "/yui/$include/$include-debug.js";
            } elseif (file_exists("$yuiPath/$include/$include.js")) {
                $source = "/yui/$include/$include.js";
            } else {
                print "YUI cannot find this library: \"$include\"";
                /** @todo the following line doesn't work... why? */
                //throw new Exception("YUI cannot find this library: \"$include\"");
            }
            $render .= "<script type=\"text/javascript\" src=\"$source\">";
            $render .= "</script>\n";
        }
        
        return $render;
    }
}
