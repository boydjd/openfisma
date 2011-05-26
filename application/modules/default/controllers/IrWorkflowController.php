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
 * CRUD behavior for incident workflows
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class IRWorkflowController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'IrWorkflowDef';

    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }
    }

    /**
     * Override to provide a better singular name
     */
    public function getSingularModelName()
    {
        return 'Incident Workflow';
    }

    /**
     * Override the parent to add special logic for saving the incident workflow's steps
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $workflowDefinition The specified subject model
     * @return integer ID of the object saved.
     */
    protected function saveValue($form, $workflowDefinition = null)
    {
        Doctrine_Manager::connection()->beginTransaction();

        $workflowId = parent::saveValue($form, $workflowDefinition);

        // Handle special cases of merging workflow steps
        $post = $this->getRequest()->getPost();

        // Get existing steps
        $stepsQuery = Doctrine_Query::create()
                      ->from('IrStep')
                      ->where('workflowId = ?', $workflowId)
                      ->orderBy('cardinality');

        $steps = $stepsQuery->execute();

        if (isset($post['stepName']) && is_array($post['stepName'])) {
            $currentStepNumber = 1;

            // Loop over posted steps' data
            foreach ($post['stepName'] as $index => $postedStepName) {

                // Skip a blank step
                $stepDescription = trim(strip_tags($post['stepDescription'][$index]));
                if (empty($postedStepName) && empty($post['stepRole'][$index]) && empty($stepDescription)) continue;

                // If the user posts more steps than the workflow has, then create new steps
                if (isset($steps[$index])) {
                    $currentStep = $steps[$index];
                    
                    $steps->remove($index);
                } else {
                    $currentStep = new IrStep();
                }

                // Merge in posted data
                $currentStep->cardinality = $currentStepNumber;
                $currentStepNumber++;

                $currentStep->name = $postedStepName;
                $currentStep->roleId = !empty($post['stepRole'][$index]) ? $post['stepRole'][$index] : null;
                $currentStep->description = !empty($post['stepDescription'][$index])
                                          ? $post['stepDescription'][$index]
                                          : null;
                $currentStep->workflowId = $workflowDefinition->id;

                $currentStep->save();
            }

            // If $steps still contains records, then these are extraneous records that should be deleted
            $steps->delete();

            // Deep-refresh the workflow object instance in case somebody else wants to use it (and we've mucked with
            // it's relations in the loops above)
            $workflowDefinition->refresh(true);
        } else if (count($steps) > 0) {
            $steps->delete();
        }

        Doctrine_Manager::connection()->commit();

        return $workflowId;
    }

    /**
     * Add the workflow steps to this form
     *
     * @param Doctrine_Record $workflowDef The workflow object
     * @param Zend_Form $form The specified form
     * @return Zend_Form The manipulated form
     */
    protected function setForm($workflowDef, $form)
    {
        $actionName = $this->getRequest()->getActionName();

        $parentForm = parent::setForm($workflowDef, $form);

        // Get roles
        $rolesQuery = Doctrine_Query::create()
                      ->from('Role')
                      ->orderBy('nickname');

        $roles = $rolesQuery->execute()->toKeyValueArray('id', 'nickname');

        // Get workflow steps
        $stepsQuery = Doctrine_Query::create()
                      ->from('IrStep')
                      ->where('workflowId = ?', $workflowDef->id)
                      ->orderBy('cardinality');

        $steps = $stepsQuery->execute();

        // If no steps exist, create a blank step
        if (0 === count($steps) && 'edit' == $actionName) {
            $defaultStep = new IrStep();
            $defaultStep->cardinality = 1;

            $steps->add($defaultStep);
        }

        // Add steps to form
        $displayGroup = $parentForm->getDisplayGroup('irworkflowdef');

        foreach ($steps as $step) {
            $stepElement = new Fisma_Zend_Form_Element_IncidentWorkflowStep("workflowStep$step->cardinality");

            $stepElement->setLabel("Step $step->cardinality");
            $stepElement->setValue($step);
            $stepElement->setRoles($roles);
            $stepElement->setDefaultRole($step->roleId);

            /**
             * @todo Kludge... the readonly attribute of the form isn't getting carried down to the step elements.
             * I dont' have time to fix it, so I'm going to set it directly when the action is 'view'. This is bad.
             */
            if ('view' == $actionName) {
                $stepElement->readOnly = true;
            }

            $displayGroup->addElement($stepElement);
        }

        return $parentForm;
    }
    
    protected function _isDeletable()
    {
        return false;
    }
}
