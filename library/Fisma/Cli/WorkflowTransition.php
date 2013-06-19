<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * Auto-transition for workflow steps
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */

class Fisma_Cli_WorkflowTransition extends Fisma_Cli_Abstract
{
    /**
     * Run the check on duedate
     */
    protected function _run()
    {
        $table = Doctrine::getTable('WorkflowStep');
        $root = Doctrine::getTable('User')->findByUsername('root');
        $workflowSteps = $table->findByAutoTransition(true);
        $today = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE);
        foreach ($workflowSteps as $step) {
            $relation = ucfirst($step->Workflow->module) . "Collection";

            $progressBar = $this->_getProgressBar($step->$relation->count());
            $progressBar->update($counter = 0, $step->Workflow->name . ' - ' . $step->name);

            foreach ($step->$relation as $object) {
                try {
                    $progressBar->update(++$counter);
                    if ($object->nextDueDate) {
                        $date = new Zend_Date($object->nextDueDate, Fisma_Date::FORMAT_DATE);
                        $compare = $date->isEarlier(new Zend_Date($today, Fisma_Date::FORMAT_DATE));
                        if ($compare) {
                            WorkflowStep::completeOnObject(
                                $object,
                                'Expired',
                                $table->getLogicalName('autoTransition'),
                                $root->id,
                                0,
                                $step->autoTransitionDestination
                            );
                        }
                    }
                } catch (Fisma_Zend_Exception_User $e) {
                    $this->getLog()->debug($e);
                }
            }
            print "\n";
        }
    }
}
