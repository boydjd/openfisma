<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * SaInformationTypeSystem 
 * 
 * @uses BaseSaInformationTypeSystem
 * @package 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class SaInformationTypeSystem extends BaseSaInformationTypeSystem
{
    /**
     * postSave 
     * 
     * @param Doctrine_Event $event 
     * @return void
     */
    public function postSave($event)
    {
        $this->_updateSystem($event);
    }

    /**
     * postDelete 
     * 
     * @param Doctrine_Event $event 
     * @return void
     */
    public function postDelete($event)
    {
        $this->_updateSystem($event);
    }

    /**
     * Update the system's CIA attributes whenever an information type is added or removed from it 
     * 
     * @param Doctrine_Event $event 
     * @return void
     */
    private function _updateSystem($event)
    {
        $system = $event->getInvoker()->System;

        /**
         * These next three queries are a hack around MySQL bug #45300. These queries could be made a lot simpler
         * by using MAX(), however, MySQL evaluates ENUM fields provided to MAX() as strings rather than as integers.
         * If we were to cast the ENUM to an integer, we could get the correct integer, however, converting that 
         * integer back to the enum field requires regex mind tricks. The performance impact of using an orderBy
         * and limit combo is negligible, the only difference is that the orderBy/limit requires the use of a filesort,
         * while MAX() does not.
         *
         * TODO: Combine these 3 separate queries into a single query.
         */
        $system->confidentiality = Doctrine_Query::create()
                                   ->select('sit.confidentiality')
                                   ->from('SaInformationType sit, SaInformationTypeSystem sits')
                                   ->orderBy('sit.confidentiality desc')
                                   ->where('sits.systemid = ?', $system->id)
                                   ->andWhere('sits.sainformationtypeid = sit.id')
                                   ->limit(1)
                                   ->fetchOne()
                                   ->confidentiality;

        $system->availability = Doctrine_Query::create()
                                   ->select('sit.availability')
                                   ->from('SaInformationType sit, SaInformationTypeSystem sits')
                                   ->orderBy('sit.availability desc')
                                   ->where('sits.systemid = ?', $system->id)
                                   ->andWhere('sits.sainformationtypeid = sit.id')
                                   ->limit(1)
                                   ->fetchOne()
                                   ->availability;

        $system->integrity = Doctrine_Query::create()
                                   ->select('sit.integrity')
                                   ->from('SaInformationType sit, SaInformationTypeSystem sits')
                                   ->orderBy('sit.integrity desc')
                                   ->where('sits.systemid = ?', $system->id)
                                   ->andWhere('sits.sainformationtypeid = sit.id')
                                   ->limit(1)
                                   ->fetchOne()
                                   ->integrity;
        $system->replace();
    }
}
