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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * A business object which represents a security vulnerability reported against
 * an information system.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Finding extends Poam
{
    /**
        count the summary of findings according to certain criteria

        @param $dateRange discovery time range
        @param $systems system id those findings belongs to
        @return array of counts
     */
    public function getStatusCount ($systems, $dateRange = array(), $status = null)
    {
        assert(! empty($systems) && is_array($systems));
        $criteria = array();
        if (isset($dateRange)) {
            // range follows [from, to)
            if (! empty($dateRange['from'])) {
                $criteria['created_date_begin'] = $dateRange['from'];
            }
            if (! empty($dateRange['to'])) {
                $criteria['created_date_end'] = $dateRange['to'];
            }
        }
        if (isset($status)) {
            $criteria = array_merge($criteria, array('status' => $status));
            if (is_string($status)) {
                $status = array($status);
            }
            foreach ($status as $s) {
                $ret[$s] = 0;
            }
        } else {
            $ret = array('NEW' => 0, 'OPEN' => 0, 'EN' => 0, 'EP' => 0,
                         'ES' => 0 , 'CLOSED' => 0 , 'DELETED' => 0);
        }
        $raw = $this->search($systems, array('status' => 'status',
                                'count' => 'status'), $criteria);
        foreach ($raw as $s) {
            $ret[$s['status']] = $s['count'];
        }
        return $ret;
    }
    
    /**
     * checkForDuplicate() - Compares a finding to all previous findings to see if any appear
     * to be duplicates. If so, the function updates the duplicate_poam_id field of the new finding
     * and returns the id of the original finding.
     * 
     * If there are more than 2 findings with the same description, then the function
     * returns the id of the oldest one.
     *
     * @param int $id
     * @return int The id of the original or null if no duplicates are found
     */
    public function checkForDuplicate($id) {
        $db = $this->getAdapter();
        $id = $db->quote($id);
        $results = $db->fetchAll(
            "SELECT old_poam.id
               FROM poams old_poam
         INNER JOIN poams new_poam 
                 ON old_poam.id <> new_poam.id
                AND old_poam.finding_data LIKE new_poam.finding_data
              WHERE new_poam.id = $id
           ORDER BY old_poam.id"
        );
        
        // If any rows are found, then return the id of the first (it is the oldest)
        if (isset($results[0])) {
            $duplicatePoamId = $results[0]['id'];
            $poam = new Poam();
            $poam->update(array('duplicate_poam_id' => $duplicatePoamId), "id = $id");
            return $duplicatePoamId;
        } else {
            return null;
        }
    }
}

