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
 * @author    ???
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 *
 * @todo This file doesn't fit into the OpenFISMA coding standards. It should be
 * refactored into a more fitting class, or else the comments should be
 * improved.
 */

/**
 * A business object which represents an information system.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class System extends FismaModel
{
    protected $_name = 'systems';
    protected $_primary = 'id';
    /**
     * getList
     *
     * The system class overrides getList in order to format the system list in
     * a specific way.
     *
     * The system list should be ordered by system nickname and should display
     * the nickname in parentheses and then the system name, e.g.:
     *
     * (SN) System Name
     *
     * TODO temporarily this function patches through to the 
     * parent implementation
     *
     * when the parameters are set to anything but the default values.
     * ideally it wouldn't do this, but for backwards compatibility this is the
     * quickest way to implement it without breaking numerous other pieces.
     */
    public function getList ($fields = '*', $primaryKey = null, $order = null)
    {
        if (($fields === '*') && ($primaryKey === null) && ($order === null)) {
            $systemList = array();
            $query = $this->select(array($this->_primary, 'nickname', 'name'))
                          ->distinct()->from($this->_name)->order('nickname');
            $result = $this->fetchAll($query);
            foreach ($result as $row) {
                $systemList[$row->id] = array('name' =>
                    ('(' . $row->nickname . ') ' . $row->name));
            }
            return $systemList;
        } else {
            return parent::getList($fields, $primaryKey, $order);
        }
    }
    
    /**
     * Inserts a new system row.
     *
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     * @see calculateSecurity()
     */
    public function insert(array $data)
    {
        $this->calculateSecurity($data);
        return parent::insert($data);
    }
    
    /**
     * Updates existing system rows.
     *
     * @param  array        $data  Column-value pairs.
     * @param  array|string $where An SQL WHERE clause, or an array of SQL WHERE clauses.
     * @return int          The number of rows updated.
     * @see calculateSecurity()
     */
    public function update(array $data, $where)
    {
        $this->calculateSecurity($data);
        return parent::update($data, $where);
    }
    
    /**
     * calculate Security categorization.
     *
     * @param  array        $data  Column-value pairs.
     */
    private function calculateSecurity(array &$data)
    {
        if (!isset($data['confidentiality'])) {
            assert(false);
            $data['confidentiality'] = 'NONE';
        }
        if (!isset($data['integrity'])) {
            assert(false);
            $data['integrity'] = 'NONE';
        }
        if (!isset($data['availability'])) {
            assert(false);
            $data['availability'] = 'NONE';
        }
        
        $array = $this->getEnumColumns('confidentiality');
        $confidentiality = array_search($data['confidentiality'], $array);
        
        $array = $this->getEnumColumns('integrity');
        $integrity = array_search($data['integrity'], $array);
        
        $array = $this->getEnumColumns('availability');
        $availability = array_search($data['availability'], $array);

        $index = max((int)$confidentiality, (int)$integrity, (int)$availability);
                     
        $array = $this->getEnumColumns('security_categorization');
        $data['security_categorization'] = $array[$index];
    }
}
