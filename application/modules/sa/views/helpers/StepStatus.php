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
 * A view helper to display whether Security Authorization steps have been completed.
 *
 * @uses Zend_View_Helper_Abstract
 * @package View_Helper
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Christian Smith <christian.smith@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Sa_View_Helper_StepStatus extends Zend_View_Helper_Abstract
{
    /**
     * Set the steps (tabs) all to true first so that incomplete
     * steps will fall through the switch statement and set to 0
     *
     * @var array Array of steps
     */
    public $_status = array('Categorize' => 1,
                            'Select' => 1,
                            'Implement' => 1,
                            'Assessment' => 1,
                            'Authorization' => 1,
    );

    /**
     * The status determines the steps that have been completed and the ones that
     * are incomplete.
     */
    public function stepStatus($step)
    {
        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);
        switch ($sa->status) {
            case 'Categorize':
            case 'Select':
                $this->_status['Implement'] = 0;
            case 'Implement':
                $this->_status['Select'] = 0;
                $this->_status['Assessment'] = 0;
            case 'Assessment Plan':
            case 'Assessment':
                $this->_status['Authorization'] = 0;
            case 'Authorization':
            case 'Active':
            case 'Retired':
                break;
            default:
                throw new Fisma_Zend_Exception('Unknown SA status encountered.');
        }

        $steps = array();

        foreach ($this->_status as $name => $value) {
            if ($name == $step) {
                break;
            }
            if ($value == 0) {
                $steps[] = $name;
            }
        }

        if(!empty($steps)) {
            $message = 'Please complete steps: ' . implode(", ", $steps);
            $severity = 'notice';

            return $this->message($message, $severity, 1);
        }
        else {
            return '';
        }
    }

    /**
     * Setup the PriorityMessenger code via Javascript since the page will not be refreshed
     *
     * @param null $message PriorityMessenger message
     * @param null $severity The severity level (for color)
     * @param null $clear If the messenger should be cleared first
     * @return string Javascript to be sent to the view
     */
    public function message($message = null, $severity = null, $clear = null)
    {
        $messenger = '<script type="text/javascript">'
                     . $this->view->escape('window.message("', 'none')
                     . $this->view->escape($message, 'javascript')
                     . $this->view->escape('", "', 'none')
                     . $this->view->escape($severity, 'javascript')
                     . $this->view->escape('", "', 'none')
                     . $this->view->escape($clear, 'javascript')
                     . $this->view->escape("\");\n", 'none')
                     . '</script>';

        return $messenger;
    }
}
