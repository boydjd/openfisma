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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Represents the report of an information security incident
 * 
 * @package Model
 */
class Incident extends BaseIncident
{
    /**
     * Pre-insert hook
     * 
     * @param Doctrine_Event $event
     */
    public function preInsert(Doctrine_Event $event)
    {
        $this->sourceIp = $_SERVER['REMOTE_ADDR'];

        $this->reportTs = date('Y-m-d H:i:s');
        $this->reportTz = date('T');
        
        $this->status = 'new';
    }
    
    /**
     * When setting a user as the incident reporter, then unset all of the reporter fields
     * 
     * @param User $user
     */
    public function setReportingUser($user)
    {
        die('it worked!');
        unset($this->reporterTitle);
        unset($this->reporterFirstName);
        unset($this->reporterLastName);
        unset($this->reporterOrganization);
        unset($this->reporterAddress1);
        unset($this->reporterAddress2);
        unset($this->reporterCity);
        unset($this->reporterState);
        unset($this->reporterZip);
        unset($this->reporterPhone);
        unset($this->reporterFax);
        unset($this->reporterEmail);
    }
}