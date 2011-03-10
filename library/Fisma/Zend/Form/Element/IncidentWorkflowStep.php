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
 * Represents a step associated with an incident workflow
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 */
class Fisma_Zend_Form_Element_IncidentWorkflowStep extends Zend_Form_Element
{
    /**
     * The step object represented by this form element
     * 
     * @var IrStep
     */
    private $_step;
    
    /**
     * An array of roles displayed by this element
     */
    private $_roles;
    
    /**
     * The key of the role which is selected by default
     */
    private $_defaultRole;

    /**
     * 
     */
    public $readOnly = false;

    /**
     * Render the form element
     * 
     * @param Zend_View_Interface $view Provided for compatibility
     * @return string The rendered element
     */
    function render(Zend_View_Interface $view = null) 
    {
        $label = $this->getLabel();
        $step = $this->_step;
        $stepName = $this->getView()->escape($step->name);

        if ($this->readOnly) {
            $render = '<tr class="incidentStep"><td>'
                    . (empty($label) ? '&nbsp;' : "$label:")
                    . '</td><td><p>Name:&nbsp;'
                    . $stepName
                    . '</p><p>'
                    . 'Role:&nbsp;'
                    . (isset($this->_roles[$this->_defaultRole]) ?
                        $this->getView()->escape($this->_roles[$this->_defaultRole]) : '')
                    . '</p><p>Description:</p><p>'
                    . $step->description
                    . '</td></tr>';
        } else {
            // Zend_View_Helper_FormSelect can't be used here because we need to name a single select with [] on the
            // end, and ZVHFS doesn't support that.
            $roleSelect = '<select name="stepRole[]"><option value=""></option>';
            
            foreach ($this->_roles as $id => $nickname) {
                $nickname = $this->getView()->escape($nickname);
                $selected = ($id == $this->_defaultRole) ? 'selected="selected"' : '';

                $roleSelect .= "<option value='$id' $selected>$nickname</option>";
            }
            
            $roleSelect .= '</select>';

            // Render the entire control
            $render = '<tr class="incidentStep"><td>'
                    . (empty($label) ? '&nbsp;' : "$label:")
                    . "</td><td><p>"
                    . "Name:&nbsp;<input size=\"80\" name=\"stepName[]\" type=\"text\" value=\"$stepName\"></p>"
                    . "<p>Role:&nbsp;$roleSelect"
                    . "<p>Description:&nbsp;"
                    . "<textarea name=\"stepDescription[]\" rows=8 cols=100>$step->description</textarea></p>"
                    . "<p><button onclick='return Fisma.Incident.addIncidentStepAbove.call(Fisma.Incident, this);'>"
                    . "Add Step Above</button>&nbsp;"
                    . "<button onclick='return Fisma.Incident.addIncidentStepBelow.call(Fisma.Incident, this);'>"
                    . "Add Step Below</button>&nbsp;"
                    . "<button onclick='return Fisma.Incident.removeIncidentStep.call(Fisma.Incident, this);'>"
                    . "Delete Step</button>"
                    . "</p></div></td></tr>";
        }
        
        return $render;
    }
    
    /**
     * Set element value
     *
     * @param mixed $step
     * @return Zend_Form_Element
     */
    public function setValue($step)
    {
        // Sanity check on paramter. We can't use type hinting because this is inherited from Zend_Form_Element.
        if (!($step instanceof IrStep)) {
            $message = "The parameter to this method must be an instance of IrStep";

            throw new Fisma_Zend_Exception($message);
        }
        
        $this->_step = $step;

        return $this;
    }

    /**
     * Set the roles displayed by this element
     *
     * @param array $roles
     * @return Zend_Form_Element
     */
    public function setRoles($roles)
    {
        $this->_roles = $roles;
    }
    
    /**
     * Set the default role displayed by this element
     * 
     * @param string $key The key (in the roles array passed to setRoles()) of the default role
     */
    public function setDefaultRole($key)
    {
        $this->_defaultRole = $key;
    }
    
    /**
     * Override isValid to prevent the validator from overwriting the value of this constant field
     * 
     * @param mixed $ignored This parameter is not used
     * @return boolean Always returns true
     */
    public function isValid($ignored) 
    {
        return true;
    }
}
