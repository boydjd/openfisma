<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * InsertUpdatedTsIntoFindingsFromAuditLog 
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Migration
 * @version    $Id$
 * 
 * @uses       Doctrine_Migration_Base
 */
class InsertUpdatedTsIntoFindingsFromAuditLog extends Doctrine_Migration_Base
{
    /**
     * up - Insert correct timestamps from auditlog into findings 
     * 
     * @access public
     * @return void
     */
    public function up()
    {
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', true);
        $this->_insertLastUpdateTimes();
    }

    /**
     * _insertLastUpdateTimes - Load data into modifiedts column from auditlog 
     * 
     * @access private
     * @return void
     */
    private function _insertLastUpdateTimes()
    {
        $lastUpdateTimes = Doctrine_Query::create()
                           ->select('findingId, createdTs')
                           ->from('AuditLog')
                           ->orderBy('createdTs desc')
                           ->groupBy('findingId')
                           ->execute();

        foreach ($lastUpdateTimes as $lastUpdate) {
            $q = Doctrine_Query::create()
                 ->update('Finding')
                 ->set('modifiedTs', '?', $lastUpdate->createdTs)
                 ->where('id = ?', $lastUpdate->findingId)
                 ->execute();
        }
    }
    
    /**
     * down - Set modifiedTs to null 
     * 
     * @access public
     * @return void
     */
    public function down()
    {
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', true);
        $q = Doctrine_Query::create()
             ->update('Finding')
             ->set('modifiedTs', '?', 'null')
             ->execute();
    }
}
