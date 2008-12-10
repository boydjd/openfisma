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
            $ret = array('NEW' => 0, 'DRAFT' => 0, 'MSA'=>0, 'EN' => 0,
                         'EP' => 0, 'CLOSED' => 0 , 'DELETED' => 0);
        }
        $raw = $this->search($systems, array('status' => 'status',
                                'count' => 'status'), $criteria);
        foreach ($raw as $s) {
            $ret[$s['status']] = $s['count'];
        }
        return $ret;
    }
}

