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
 * A listener base class which enables functionality to selectively disable a particular listener
 * 
 * This class provides the public interface for controlling the listener's enabled state, but each child class is 
 * ultimately responsible for applying the state correctly. In a sense, this class is actually just a marker interface.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Record
 */
class Fisma_Doctrine_Record_Listener extends Doctrine_Record_Listener
{
    /**
     * Indicates whether the listener is enabled or not
     * 
     * @var bool
     */
    static protected $_listenerEnabled = true;
    
    /**
     * Get this listener's current enabled state
     * 
     * @return bool
     */
    static public function getEnabled()
    {
        return self::$_listenerEnabled;
    }
    
    /**
     * Set whether this listener is enabled or not
     * 
     * @param bool $enabled
     */
    static public function setEnabled($enabled)
    {
        self::$_listenerEnabled = $enabled;
    }
}
