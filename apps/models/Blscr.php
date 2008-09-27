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
 * A business object which represents NIST baseline security controls. (See NIST
 * 800-53 for the catalog of controls.)
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo this class should be named with a capital
 */
class Blscr extends FismaModel
{
    protected $_name = 'blscrs';
    protected $_primary = 'code';
    /**
     * getList
     *
     * Overrides the parent function in order to enforce sorting on the `code`
     * column, which in most cases will be the most intuitive sort for the end
     * user.
     *
     * If the caller specifies a non-null sort order, then the sort order is
     * not changed.
     *
     * @param string|string[] $fields Which field[s] to get from the BLSCR list
     * @param string $primaryKey Which column becomes the key for the returned
     * array (defaults to the primary key of the table)
     *
     * @param string|string[] $order The column[s] to sort on
     * (defaults to `code`)
     *
     * @return array An array of arrays where the primary_key is the outer key
     * and the column names are the inner keys
     */
    public function getList ($fields = '*', $primaryKey = null, $order = null)
    {
        if ($order == null) {
            return parent::getList($fields, $primaryKey, 'code');
        } else {
            return parent::getList($fields, $primaryKey, $order);
        }
    }
}
