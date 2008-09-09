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
 * The base class for models in OpenFISMA. Implements some primitive operations
 * that are useful for all business objects.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo This class should probably be abstract.
 */
class Fisma_Model extends Zend_Db_Table
{
    /**
     * List all entries in the table
     * If the $fields contains string other than '*', the value of returned array is string.
     * It's array otherwise. 
     *
     * @param fields string|array indicating fields interested in.
     * @param primary_key int|string|array primary key(s) 
     * @param order  string specify the order field default null, which means the first elment of $fields
     * @return array indexed by primary key(id)
     */
    public function getList ($fields = '*', $primary_key = null, $order = null)
    {
        $primary = $this->_primary;
        $id_name = array_pop($primary);
        $is_pair = false;
        if ($fields != '*') {
            if (is_string($fields)) {
                $is_pair = true;
                $fields = array($fields , $id_name);
            } else {
                if (count($fields) == 1) {
                    $is_pair = true;
                }
                assert(is_array($fields));
                $fields = array_merge($fields, array($id_name));
            }
        } else {
            $fields = $this->_cols;
        }
        if (! isset($order)) {
            $order = reset($fields);
        }
        assert(in_array($order, $fields));
        $list = array();
        $query = $this->select()->distinct()->from($this->_name, $fields)->order($order);
        $result = $this->fetchAll($query);
        foreach ($result as $row) {
            if (empty($primary_key) || in_array($row->$id_name, $primary_key)) {
                foreach ($fields as $k => $v) {
                    if ($v != $id_name) {
                        if ($is_pair) {
                            $list[$row->$id_name] = $row->$v;
                        } else {
                            if (is_string($k)) {
                                $list[$row->$id_name][$k] = $row->$k;
                            } else {
                                $list[$row->$id_name][$v] = $row->$v;
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
}
