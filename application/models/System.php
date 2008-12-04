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
     * calculate Security categorization.
     *
     * @param string $confidentiality confidentiality
     * @param string $integrity integrity
     * @param string $availability availability
     * @return string security_categorization
     */
    public function calcSecurityCategory($confidentiality, $integrity, $availability)
    {
        $array = $this->getEnumColumns('confidentiality');
        assert(in_array($confidentiality, $array));
        $confidentiality = array_search($confidentiality, $array);
        
        $array = $this->getEnumColumns('integrity');
        assert(in_array($integrity, $array));
        $integrity = array_search($integrity, $array);
        
        $array = $this->getEnumColumns('availability');
        assert(in_array($availability, $array));
        $availability = array_search($availability, $array);

        $index = max((int)$confidentiality, (int)$integrity, (int)$availability);
        return $array[$index];
    }

    /**
     * Calculate min level
     *
     * @param string $levelA
     * @param string $levelB
     * @param return string min of $levelA and $levelB
     */
    public function calcMin($levelA, $levelB)
    {
        $cloumns = $this->getEnumColumns('availability');
        assert(in_array($levelA, $cloumns));
        assert(in_array($levelB, $cloumns));
        $senseMap = array_flip($cloumns);
        $ret = min($senseMap[$levelA], $senseMap[$levelB]);
        return $cloumns[$ret];
    }
    
    /**
     * Calcuate overall threat level
     *
     * @param string $threat threat level
     * @param string $countermeasure countermeasure level
     * @return string overall threat
     */
    public function calcThreat($threat, $countermeasure)
    {
        $cloumns = $this->getEnumColumns('availability');
        assert(in_array($threat, $cloumns));
        assert(in_array($countermeasure, $cloumns));
        $cloumnsMap = array_flip($cloumns);
        $max = max(count($cloumnsMap) - $cloumnsMap[$threat], $cloumnsMap[$countermeasure]);
        $ret = count($cloumns) - $max;
        return $cloumns[$ret];
    }
}
