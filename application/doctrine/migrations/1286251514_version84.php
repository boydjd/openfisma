<?php
// @codingStandardsIgnoreFile
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
 * Add denormalized status column and residual risk column to finding model, rename incident category privileges
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version84 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->addColumn('finding', 'denormalizedstatus', 'string', '255', array(
             'comment' => 'A denormalized status field that is used for search engine indexing',
             ));
		$this->addColumn('finding', 'residualrisk', 'enum', '', array(
             'values' =>
             array(
              0 => 'LOW',
              1 => 'MODERATE',
              2 => 'HIGH',
             ),
             'extra' =>
             array(
              'auditLog' => '1',
              'logicalName' => 'Residual Risk',
             ),
             'comment' => 'The risk that remains after combining the threat level with countermeasures effectiveness',
             ));
    }

    public function postUp()
    {
        // Update the "denormalized status field" that was just created
        $updateStatusQuery = Doctrine_Query::create()
                             ->update('Finding')
                             ->set("denormalizedStatus", "status")
                             ->whereIn('status', array('PEND', 'NEW', 'DRAFT', 'EN', 'CLOSED'));

        $updateStatusQuery->execute();

        $evaluations = Doctrine::getTable('Evaluation')->findAll();

        foreach ($evaluations as $evaluation) {
            $updateEvaluationStatusQuery = Doctrine_Query::create()
                                           ->update('Finding')
                                           ->set('denormalizedStatus', '?', $evaluation->nickname)
                                           ->where('currentEvaluationId = ?', $evaluation->id);

            $updateEvaluationStatusQuery->execute();
        }

        // Update the residual risk field that was just created
        $highRiskQuery = Doctrine_Query::create()
                         ->update('Finding')
                         ->set('residualRisk', '?', 'HIGH')
                         ->where("threatLevel = 'HIGH' AND countermeasuresEffectiveness ='LOW'");

        $highRiskQuery->execute();

        $medRiskQuery = Doctrine_Query::create()
                         ->update('Finding')
                         ->set('residualRisk', '?', 'MODERATE')
                         ->where("(threatLevel = 'HIGH' AND countermeasuresEffectiveness = 'MODERATE')
                               OR (threatLevel = 'MODERATE' AND countermeasuresEffectiveness IN ('LOW', 'MODERATE'))");

        $medRiskQuery->execute();

        $lowRiskQuery = Doctrine_Query::create()
                         ->update('Finding')
                         ->set('residualRisk', '?', 'LOW')
                         ->where("threatLevel = 'LOW' OR countermeasuresEffectiveness = 'HIGH'");

        $lowRiskQuery->execute();

        $nullCmeasuresRiskQuery = Doctrine_Query::create()
                                  ->update('Finding')
                                  ->set('residualRisk', 'threatLevel')
                                  ->where("countermeasuresEffectiveness IS NULL");

        $nullCmeasuresRiskQuery->execute();
        
        // Rename the ir_category privileges to ir_sub_category (since categories cannot be edited at all, but
        // subcategories can be edited by privileged users)
        $renameIrCategoryPrivilgesQuery = Doctrine_Query::create()
                                          ->update('Privilege')
                                          ->set('resource', '?', 'ir_sub_category')
                                          ->where("resource LIKE 'ir_category'");
        
        $renameIrCategoryPrivilgesQuery->execute();                                  
    }

    public function down()
    {
		$this->removeColumn('finding', 'denormalizedstatus');
		$this->removeColumn('finding', 'residualrisk');
		
        // Rename the ir_sub_category privileges to ir_category
        $renameIrCategoryPrivilgesQuery = Doctrine_Query::create()
                                          ->update('Privilege')
                                          ->set('resource', '?', 'ir_category')
                                          ->where("resource LIKE 'ir_sub_category'");
        
        $renameIrCategoryPrivilgesQuery->execute();                                  
    }
}
