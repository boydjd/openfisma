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
  * Fisma_Cookie
  *
  * Fisma_Cookie manages cookies for OpenFISMA.
  * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cookie
 * @version    $Id$
 */
class Fisma_Cookie
{
   /**
    * get - Returns the specified cookie from the specified array of cookies
    * 
    * @param array $cookie The $_COOKIE array, or some other collection of cookies
    * @param string $key The name of the cookie to retrieve from $cookie
    * @static
    * @access public
    * @return string The value of the requested cookie
    */
    public static function get(array $cookie, $key) 
    {
        // If the cookie is available, return it. Otherwise,  throw an 
        // exception to be handled by the caller.
        if (isset($cookie[$key])) {
            return $cookie[$key];
        } else {
            throw new Fisma_Exception("Cookie $key not found.");
        }
    }

   /**
    * prepare - Prepares a cookie for sending to the client. 
    * 
    * @param string $name 
    * @param string $value
    * @param boolean $secure
    * @static
    * @access public
    * @return array 
    */
    public static function prepare($name, $value, $secure = null) 
    {
        if (is_null($secure)) {
            $secure = Zend_Session::getOptions('cookie_secure');
        }

        // Create an array containing the arguments to be passed to setcookie()
        // by the caller. Expire is set to false, so that the cookie expires
        // with the session, pursuant to United States federal law. 
        $cookie = array('name' => $name, 'value' => $value, 'expire' => false, 
                        'path' => '/', 'domain' => '', 'secure' => $secure
                       ); 

        return $cookie;
    }

   /**
    * set - Sets a cookie by calling prepare to build the cookie.
    *
    * @param string $name
    * @param string $value
    * @static
    * @access public
    * @return void
    */
    public static function set($name, $value) 
    {
        call_user_func_array("setcookie", self::prepare($name, $value));
    }
}
