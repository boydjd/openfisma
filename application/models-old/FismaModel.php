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
 * @package   Model
 */
 
/**
 * The base class for models in OpenFISMA. Implements some primitive operations
 * that are useful for all business objects.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo This class should probably be abstract.
 */
class FismaModel extends Zend_Db_Table
{
    /**
     * List all entries in the table
     * If the $fields contains string other than '*', the value of returned
     * array is string.
     *
     * It's array otherwise. 
     *
     * @param fields string|array indicating fields interested in.
     * @param primary_key int|string|array primary key(s) 
     * @param order  string specify the order field default null, which means
     * the first elment of $fields
     *
     * @return array indexed by primary key(id)
     */
    public function getList ($fields = '*', $primaryKey = null, $order = null)
    {
        $primary = $this->_primary;
        $idName = array_pop($primary);
        $isPair = false;
        if ($fields != '*') {
            if (is_string($fields)) {
                $isPair = true;
                $fields = array($fields , $idName);
            } else {
                if (count($fields) == 1) {
                    $isPair = true;
                }
                assert(is_array($fields));
                $fields = array_merge($fields, array($idName));
            }
        } else {
            $fields = $this->_cols;
        }
        if (! isset($order)) {
            $order = reset($fields);
        }
        assert(in_array($order, $fields));
        $list = array();
        $query = $this->select()->distinct()
                      ->from($this->_name, $fields)->order($order);
        $result = $this->fetchAll($query);
        foreach ($result as $row) {
            if (empty($primaryKey) || in_array($row->$idName, $primaryKey)) {
                foreach ($fields as $k => $v) {
                    if ($v != $idName) {
                        if ($isPair) {
                            $list[$row->$idName] = $row->$v;
                        } else {
                            if (is_string($k)) {
                                $list[$row->$idName][$k] = $row->$k;
                            } else {
                                $list[$row->$idName][$v] = $row->$v;
                            }
                        }
                    }
                }
            }
        }
        return $list;
    }
    
    /**
     * count() - Count the total number of rows in this table
     *
     * This function should really be static, but Zend_Db_Table blows up when
     * you call static functions on it.
     *
     * @return int
     */
    function count() {
        $countQuery = $this->select()
                           ->from($this->_name,
                                  'count(*) AS count');
        $row = $this->fetchRow($countQuery);
        return $row->count;
    }
    /**
     * get the types of an enum column
     *
     * @param string $column field name
     * @param string $callback function name, use this function to rebuild indices of the array
     *          default, the indices will according to the order in database
     * @return array 
     */
    function getEnumColumns($column, $callback=null) {
        $columns = $this->_metadata;
        assert(isset($columns[$column]));
        assert(is_int(strpos($columns[$column]['DATA_TYPE'], 'enum')));
        $sTypes = substr($columns[$column]['DATA_TYPE'], 6, -2);
        $aTypes = explode("','", $sTypes);
        if ($callback !== null) {
            $aTypes = call_user_func($callback, $aTypes);
        }
        return $aTypes;
    }
}
