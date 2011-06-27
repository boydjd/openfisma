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
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Change NONE in ThreatLevel and CountermeasuresEffectiveness fields in Findings to empty strings 
 *
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version10 extends Doctrine_Migration_Base
{
    /**
     * Convert from NONE to empty
     * 
     * @return void
     */
    public function up()
    {
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', true);

        $threatLevel = Doctrine_Query::create()
                       ->update('Finding')
                       ->set('threatLevel', '?', '')
                       ->where('threatLevel = ?', 'NONE')
                       ->execute();

        $cmEff = Doctrine_Query::create()
                 ->update('Finding')
                 ->set('countermeasuresEffectiveness', '?', '')
                 ->where('countermeasuresEffectiveness = ?', 'NONE')
                 ->execute();

        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', false);
    }
    
    /**
     * Convert from empty to NONE
     * 
     * @return void
     */
    public function down()
    {
        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', true);

        $threatLevel = Doctrine_Query::create()
                       ->update('Finding')
                       ->set('threatLevel', '?', 'NONE')
                       ->where('threatLevel = ?', '')
                       ->execute();

        $cmEff = Doctrine_Query::create()
                 ->update('Finding')
                 ->set('countermeasuresEffectiveness', '?', 'NONE')
                 ->where('countermeasuresEffectiveness = ?', '')
                 ->execute();

        Doctrine::getTable('Finding')->getRecordListener()->setOption('disabled', false);

    }
}
