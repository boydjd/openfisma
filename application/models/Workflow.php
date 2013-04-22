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
 * Workflow schema for all modules
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Workflow extends BaseWorkflow
{
    /**
     * This model uses a combined "manage" privilege in place of usual CRUD
     *
     * @var bool
     */
    const IS_MANAGED = true;

    /**
     * Set custom mutators
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasMutator('isDefault', 'setIsDefault');
    }

    /**
     * Ensuring there is only one default workflow per module
     *
     * @param boolean $value
     * @return void
     */
    public function setIsDefault($value)
    {
        if ($value && !$this->isDefault) {
            $defaultWorkflows = Doctrine_Query::create()
                ->from('Workflow w')
                ->where('w.isDefault = ?', true)
                ->andWhere('w.module = ?', $this->module)
                ->execute();
            foreach ($defaultWorkflows as $workflow) {
                $workflow->isDefault = false;
            }
            $defaultWorkflows->save();
        }
        $this->_set('isDefault', $value);
    }

    /**
     * Return the first step of the workflow
     *
     * @return mixed WorkflowStep object or null if none found.
     */
    public function getFirstStep()
    {
        foreach ($this->WorkflowSteps as $step) {
            if ((int)$step->cardinality == 1) {
                return $step;
            }
        }
        return null;
    }
}
