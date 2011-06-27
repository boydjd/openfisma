<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Make evaluation.daysuntildue have a default value of 7 and force all current null values on this table to 7
 *
 * @codingStandardsIgnoreFile
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Dale Frey <dale.frey@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version115 extends Doctrine_Migration_Base
{
    /**
     * Make evaluation.daysuntildue have a default value of 7.
     * Force all current null values of evaluation.daysuntildue to 7.
     *
     * @return void
     */
    public function up()
    {
        // Force all current null values on this table to 7
        $q = Doctrine_Query::create()
            ->update('Evaluation')
            ->set('daysUntilDue', 7)
            ->where('daysUntilDue IS NULL');
        $q->execute();
        
        // Make evaluation.daysuntildue have a default value of 7
        $this->changeColumn(
            'evaluation',
            'daysuntildue',
            null,
            'int',
            array(
                'default' => 7,
                'notnull' => true
            )
        );
    }
    
    /**
     * Undo the default-change done in up().
     * The evaluation.daysuntildue value change(s) are not undo-able.
     *
     * @return void
     */
    public function down()
    {
        // Undo the default-change done in up()
        $this->changeColumn('evaluation', 'daysuntildue', null, 'int', array('notnull' => false));
    }
}
