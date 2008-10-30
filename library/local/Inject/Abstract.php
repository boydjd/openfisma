<?php 
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 *
 */

/**
 * An abstract class for creating injection plug-ins
 *
 * @package   Inject
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
abstract class Inject_Abstract
{
    protected $_file;
    protected $_networkId;
    protected $_systemId;
    protected $_findingSourceId;
    
    /**
     * __construct() - Create a new plug-in instance for the specified file
     *
     * @param string $file
     */
    public function __construct($file, $networkId, $systemId, $findingSourceId) 
    {
        $this->_file = $file;
        $this->_networkId = $networkId;
        $this->_systemId = $systemId;
        $this->_findingSourceId = $findingSourceId;
    }
    
    /** 
     * parse() - Parse all the data from the specified file, and load it into the database.
     *
     * Throws an exception if the file is an invalid format.
     *
     * @param string $file The file to load with this plugin.
     * @return Return the number of findings created.
     */
    abstract public function parse();
}
