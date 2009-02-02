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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * This class helps to translate the naming convention of fields from 
 * lower_case_name to camelCaseName
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */

class Table_Rowlower extends Zend_Db_Table_Row_Abstract
{
    protected function _transformColumn($columnName) { 
        if (!is_string($columnName)) { 
            require_once 'Zend/Db/Table/Row/Exception.php'; 
            throw new Zend_Db_Table_Row_Exception('Specified column is not a string'); 
        } 
        // Transform the camelCase into lower_case
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $columnName)); 
    } 
}
