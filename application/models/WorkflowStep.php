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
 * Workflow step metadata
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class WorkflowStep extends BaseWorkflowStep
{
    public function moveFirst()
    {
        if ($this->cardinality > 1 && !empty($this->workflowId)) {
            $previousSteps = Doctrine_Query::create()
                ->from('WorkflowStep ws')
                ->where('ws.cardinality < ?', $this->cardinality)
                ->andWhere('ws.workflowId = ?', $this->workflowId)
                ->execute();
            foreach ($previousSteps as $step) {
                $step->cardinality = $step->cardinality + 1;
            }

            try {
                Doctrine_Manager::connection()->beginTransaction();

                $previousSteps->save();
                $this->cardinality = 1;
                $this->save();

                Doctrine_Manager::connection()->commit();
            } catch (Exception $e) {
                Doctrine_Manager::connection()->rollback();
                throw $e;
            }
        }
    }

    public function moveUp()
    {
        if ($this->cardinality > 1 && !empty($this->workflowId)) {
            $previousSteps = Doctrine_Query::create()
                ->from('WorkflowStep ws')
                ->where('ws.cardinality = ?', $this->cardinality - 1)
                ->andWhere('ws.workflowId = ?', $this->workflowId)
                ->execute();
            foreach ($previousSteps as $step) {
                $step->cardinality = $step->cardinality + 1;
            }

            try {
                Doctrine_Manager::connection()->beginTransaction();

                $previousSteps->save();
                $this->cardinality = $this->cardinality - 1;
                $this->save();

                Doctrine_Manager::connection()->commit();
            } catch (Exception $e) {
                Doctrine_Manager::connection()->rollback();
                throw $e;
            }
        }
    }

    public function moveLast()
    {
        if (!empty($this->workflowId)) {
            $maxCardinality = Doctrine_Query::create()
            ->from('WorkflowStep ws')
            ->where('ws.workflowId = ?', $this->workflowId)
            ->count();
            if ($this->cardinality < $maxCardinality) {
                $comingSteps = Doctrine_Query::create()
                    ->from('WorkflowStep ws')
                    ->where('ws.cardinality > ?', $this->cardinality)
                    ->andWhere('ws.workflowId = ?', $this->workflowId)
                    ->execute();

                foreach ($comingSteps as $step) {
                    $step->cardinality = $step->cardinality - 1;
                }

                try {
                    Doctrine_Manager::connection()->beginTransaction();

                    $comingSteps->save();
                    $this->cardinality = $maxCardinality;
                    $this->save();

                    Doctrine_Manager::connection()->commit();
                } catch (Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    throw $e;
                }
            }
        }
    }

    public function moveDown()
    {
        if (!empty($this->workflowId)) {
            $maxCardinality = Doctrine_Query::create()
            ->from('WorkflowStep ws')
            ->where('ws.workflowId = ?', $this->workflowId)
            ->count();
            if ($this->cardinality < $maxCardinality) {
                $comingSteps = Doctrine_Query::create()
                    ->from('WorkflowStep ws')
                    ->where('ws.cardinality = ?', $this->cardinality + 1)
                    ->andWhere('ws.workflowId = ?', $this->workflowId)
                    ->execute();
                foreach ($comingSteps as $step) {
                    $step->cardinality = $step->cardinality - 1;
                }

                try {
                    Doctrine_Manager::connection()->beginTransaction();

                    $comingSteps->save();
                    $this->cardinality = $this->cardinality + 1;
                    $this->save();

                    Doctrine_Manager::connection()->commit();
                } catch (Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    throw $e;
                }
            }
        }
    }

    /**
     * Return a transition by its name
     *
     * @param string $transitionName
     * @return mixed Associate array or null if none found
     */
    public function findTransitionByName($transitionName)
    {
        foreach ($this->transitions as $transition) {
            if ($transition['name'] === $transitionName) {
                return $transition;
            }
        }
        die (print_r($this->transitions));
        return null;
    }

    public function getNext()
    {
        if (!empty($this->workflowId)) {
            return Doctrine_Query::create()
                ->from('WorkflowStep ws')
                ->where('ws.cardinality = ?', $this->cardinality + 1)
                ->andWhere('ws.workflowId = ?', $this->workflowId)
                ->fetchOne();
        }
        return null;
    }

    public function getPrevious()
    {
        if (!empty($this->workflowId)) {
            return Doctrine_Query::create()
                ->from('WorkflowStep ws')
                ->where('ws.cardinality = ?', $this->cardinality - 1)
                ->andWhere('ws.workflowId = ?', $this->workflowId)
                ->fetchOne();
        }
        return null;
    }

    public function validateObject($object, $duedate = null)
    {
        $missingFields = array();

        foreach ((array)$this->prerequisites as $field) {
            if (empty($object->$field)) {
                $missingFields[] = $field;
            }
        }

        if ($this->allottedTime === 'custom' && empty($duedate)) {
            $missingFields[] = 'Expiration Date';
        }

        return $missingFields;
    }

    public function getNextStep($transitionName)
    {
        $transition = $this->findTransitionByName($transitionName);
        $nextStep = null;
        $destination = $transition['destination'];
        switch ($destination) {
            case 'next':
                $nextStep = $this->getNext();
                break;
            case 'back':
                $nextStep = $this->getPrevious();
                break;
            case 'custom':
                $nextStep = Doctrine::getTable('WorkflowStep')->find($transition['customDestination']);
                break;
        }
        return $nextStep;
    }

    /**
     * Complete the current step of an object
     *
     * @param mixed     $object         Must have isResolved, closedTs, Organization, CurrentStep, completedSteps
     * @param String    $transitionName Can be arbitrary if $destinationId provided
     * @param String    $comment        Comment / explanation / justification
     * @param int       $userId         ID of the User completing the step
     * @param int       $expirationDate Number of days until the new step expires
     * @param int       $destinationId  Optional. Override $transitionName for auto-transition.
     * @return void
     */
    public static function completeOnObject
        ($object, $transitionName, $comment, $userId, $expirationDate, $destinationId = null)
    {
        if (empty($comment)) {
            throw new Fisma_Zend_Exception_User('Comment cannot be enmpty.');
        }

        $step = $object->CurrentStep;
        if (!$step) {
            throw new Fisma_Zend_Exception_User('Object is not currently assigned to any workflow step.');
        }

        if ($destinationId) {
            $nextStep = Doctrine::getTable('WorkflowStep')->find($destinationId);
        } else {
            $transition = $step->findTransitionByName($transitionName);
            if (!$transition) {
                throw new Fisma_Zend_Exception_User('Invalid transition provided (' . $transitionName . ').');
            }

            $currentRoles = $object->Organization->getPocs()->fetchAllPositions($userId);
            $transitionRoles = array_intersect(
                $currentRoles,
                Zend_Json::decode($transition['roles'])
            );
            if (count($transitionRoles) < 1) {
                throw new Fisma_Zend_Exception_User(
                    'Current user does not have the required role for this transition (' . $transitionName . ').'
                );
            }

            $nextStep = $step->getNextStep($transitionName);
        }
        if (!$nextStep) {
            throw new Fisma_Zend_Exception_User('Invalid destination provided (' . $transition['destination'] . ').');
        }

        $missingFields = $nextStep->validateObject($object, $expirationDate);
        if (count($missingFields) > 0) {
            throw new Fisma_Zend_Exception_User(
                '"' . $nextStep->name . '" workflow step requires the following field(s): '
                . implode(', ', $missingFields)
            );
        }

        $completedSteps = $object->completedSteps;
        $completedSteps[] = array(
            'workflow' => array(
                'name' => $object->CurrentStep->Workflow->name,
                'description' => $object->CurrentStep->Workflow->description
            ),
            'step' => array(
                'name' => $object->CurrentStep->name,
                'label' => $object->CurrentStep->label,
                'description' => $object->CurrentStep->description
            ),
            'transitionName' => $transitionName,
            'comment' => $comment,
            'expirationDate' => $expirationDate,
            'userId' => $userId,
            'timestamp' => Fisma::now()
        );
        $object->completedSteps = $completedSteps;

        $object->CurrentStep = $nextStep;

        $object->isResolved = $object->CurrentStep->isResolved;
        $object->closedTs = ($object->isResolved) ? Fisma::now() : null;

        switch ($object->CurrentStep->allottedTime) {
            case 'days':
                $object->nextDueDate = Zend_Date::now()
                    ->addDay($object->CurrentStep->allottedDays)
                    ->toString(Fisma_Date::FORMAT_DATE);
                break;
            case 'custom':
                $object->nextDueDate = Zend_Date::now()
                    ->addDay($expirationDate)
                    ->toString(Fisma_Date::FORMAT_DATE);
                break;
            case 'unlimited':
            case 'ecd':
            default:
                $object->nextDueDate = null;
        }

        $object->save();
    }
}
