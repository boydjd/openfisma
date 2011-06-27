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
 * Migration to fix incorrect finding.currentevaluationid enteries (OFJ-1398)
 *
 * @codingStandardsIgnoreFile
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Dale Frey <dale.frey@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version114 extends Doctrine_Migration_Base
{

    /** 
    * Update the findings table such that all rows that are non-MSA, non-EA and non-NULL-currentevaluationids 
    * have NULL for their currentevaluationid
    * 
    * @return void 
    */
    public function up()
    {
        $q = Doctrine_Query::create()
            ->update('Finding f')
            ->set('f.currentEvaluationId', 'NULL')
            ->where("f.status <> 'EA'")
            ->andWhere("f.status <> 'MSA'")
            ->andWhere("f.currentEvaluationId IS NOT NULL");
        $q->execute();
    }
    
    /** 
    * Downgrade. The actions performed on the database in this migration's upgrade is undoable, but should not
    * disable the ability to downgrade further.
    * 
    * @return void 
    */
    public function down()
    {
        // this change in up() is undoable, but should not halt the abiliy to downgrade
    }
}
