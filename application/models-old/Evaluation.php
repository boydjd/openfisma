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
 * A business object which represents a reviewer's evaluation of a piece of
 * evidence supporting a particular remediation.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Evaluation extends FismaModel
{
    protected $_name = 'evaluations';
    protected $_primary = 'id';

    /**
     * @todo english
     * Get evaluation List
     * @param string $group Evaluation group
     * @return array $ret 
     */
    public function getEvalList ($group) {
        if (!in_array($group, array('EVIDENCE', 'ACTION'))) {
            throw new Fisma_Exception_General('Make sure a valid GROUP is inputed');
        }
        $query = $this->_db->select()
                      ->from(array('ev'=>'evaluations'), array('ev.*'))
                      ->join(array('f'=>'functions'), 'ev.function_id = f.id',
                          array('function'=>'action'))
                      ->where('ev.group = ?', $group)
                      ->order('ev.precedence_id');
        $ret = $this->_db->fetchAll($query);
        return $ret;
    }
}

