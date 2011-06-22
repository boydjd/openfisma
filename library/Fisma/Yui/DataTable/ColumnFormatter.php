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
 * Represents a YUI column formatter function
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_DataTable_ColumnFormatter
{
    /**
     * The name of the formatter function, e.g. "Fisma.TableFormat.myFormat" refers to a function called 
     * "myFormat()" in the Fisma.TableFormat javascript namespace.
     * 
     * @var string
     */    
    private $_name;
    
    /**
     * Constructor
     * 
     * @param string $name The name of the formatter function.
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }
    
    /**
     * Accessor for function name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
}
