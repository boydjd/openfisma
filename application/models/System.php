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
 * @package   Model
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
     * Defines the way counter measure effectiveness and threat level combine to produce the threat likelihood. This
     * array is indexed as: $_threatLikelihoodMatrix[THREAT_LEVEL][COUNTERMEASURE_EFFECTIVENESS] == THREAT_LIKELIHOOD
     *
     * @see _initThreatLikelihoodMatrix()
     */
    private $_threatLikelihoodMatrix;
    
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
     * Calculate Security categorization.
     *
     * The calculation over enumeration fields {LOW, MODERATE, HIGH} is tricky here. The algorithm 
     * is up to their mapping value, which is decided by the appear consequence in TABLE definition.
     * For example, in case `confidentiality` ENUM('NA','LOW','MODERATE','HIGH') it turns out the 
     * mapping value: LOW=0, MODERATE=1, HIGH=2. The value calculated is the maximun of C, I, A. And 
     * is transferred back to enumeration name again.
     *
     * As the C(Confidentiality) has the additional value 'NA', which is absent from the other two
     * I,A, it's necessary to remove it before calculating the security categorization. Due to the
     * design, we have to hard code it, say array_shift($confidentiality).
     * 
     * @param string $confidentiality confidentiality
     * @param string $integrity integrity
     * @param string $availability availability
     * @return string security_categorization
     */
    public function calcSecurityCategory($confidentiality, $integrity, $availability)
    {
        if (NULL == $confidentiality || NULL == $integrity || NULL == $availability) {
            return NULL;
        }
        $array = $this->getEnumColumns('confidentiality');
        assert(in_array($confidentiality, $array));
        array_shift($array);
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
     * @see calcSecurityCategory
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
     * @see calcSecurityCategory
     *
     * @param string $threat threat level
     * @param string $countermeasure countermeasure level
     * @return string overall threat
     */
    public function calculateThreatLikelihood($threat, $countermeasure)
    {
        // Initialize the threat likelihood matrix if necessary
        if (!$this->_threatLikelihoodMatrix) {
            $this->_initThreatLikelihoodMatrix();
        }
        
        // Map the parameters into the matrix and return the mapped value
        return $this->_threatLikelihoodMatrix[$threat][$countermeasure];
    }
    
    /**
     * Initializes the threat likelihood matrix. This is hardcoded because these values are defined in NIST SP 800-30
     * and are not likely to change very often.
     *
     * @link http://csrc.nist.gov/publications/nistpubs/800-30/sp800-30.pdf
     */
    private function _initThreatLikelihoodMatrix()
    {
        $this->_threatLikelihoodMatrix['HIGH']['LOW']      = 'HIGH';
        $this->_threatLikelihoodMatrix['HIGH']['MODERATE'] = 'MODERATE';
        $this->_threatLikelihoodMatrix['HIGH']['HIGH']     = 'LOW';
        
        $this->_threatLikelihoodMatrix['MODERATE']['LOW']      = 'MODERATE';
        $this->_threatLikelihoodMatrix['MODERATE']['MODERATE'] = 'MODERATE';
        $this->_threatLikelihoodMatrix['MODERATE']['HIGH']     = 'LOW';

        $this->_threatLikelihoodMatrix['LOW']['LOW']      = 'LOW';
        $this->_threatLikelihoodMatrix['LOW']['MODERATE'] = 'LOW';
        $this->_threatLikelihoodMatrix['LOW']['HIGH']     = 'LOW';
    }
}
