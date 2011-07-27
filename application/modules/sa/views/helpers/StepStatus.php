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
     * @var array
     */
    public $_status = array('Select' => 1,
                            'Implement' => 1,
                            'Assessment' => 1,
                            'Authorization' => 1,
    );


    public $_string;
    /**
     * @return void
     */
    public function stepStatus($step)
    {
        $sa = Doctrine::getTable('SecurityAuthorization')->find($this->view->id);
        switch ($sa->status) {
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
            return 'Please complete steps: ' . implode(", ", $steps);
        }
        else {
            return '';
        }
    }
}