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
 * Fisma_Symfony_Components_Autoloader 
 * 
 * @package Fisma_Symfony_Components 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @author Loïc Frering <loic.frering@gmail.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Symfony_Components_Autoloader
{
  /**
   * Registers sfServiceContainerAutoloader as an SPL autoloader.
   * 
   * @static
   * @return void
   */
  static public function register()
  {
    ini_set('unserialize_callback_func', 'spl_autoload_call');
    spl_autoload_register(array(new self, 'autoload'));
  }

  /**
   * Handles autoloading of classes.
   * 
   * @param string $class A class name.
   * @return boolean Returns true if the class has been loaded 
   */
  static public function autoload($class)
  {
    if (0 !== strpos($class, 'sf')) {
      return false;
    }

    require 'Symfony/Components/'.$class.'.php';

    return true;
  }
}
