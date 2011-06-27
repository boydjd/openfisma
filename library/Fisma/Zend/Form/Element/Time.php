<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */

/**
 * An element which represents a time value, including hours, minutes, AM/PM, and timezone
 *
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */
class Fisma_Zend_Form_Element_Time extends Zend_Form_Element
{
    /**
     * Contains valid values for AM and PM
     * 
     * @var array
     */
    private $_ampmList = array(
        'AM' => 'AM', 
        'PM' => 'PM'
    );
    
    /**
     * Holds the hours
     * 
     * @var int
     */
    private $_hour;
    
    /**
     * Holds the minutes
     *
     * @var int
     */
    private $_minute;
    
    /**
     * Holds the AM/PM
     */
    private $_ampm;
    
    /**
     * Override the parent in order to parse out the hour, minute, and AM/PM
     * 
     * @param string $value
     */
    public function setValue($value)
    {
        parent::setValue($value);

        $parts = explode(':', $value);
        $this->_hour = $parts[0];
        if (!empty($this->_hour)) {
            if ($this->_hour > 12) {
                $this->_hour -= 12;
                $this->_ampm = 'PM';
            } else {
                $this->_ampm = 'AM';
            }
        }

        $this->_minute = isset($parts[1]) ? $parts[1] : '';
    }

    /**
     * Render the form element
     *
     * @param Zend_View_Interface $view Not used but required because of parent's render() signature
     * @return string The rendered element
     */
    public function render(Zend_View_Interface $view = null) 
    {
        $label = $this->getLabel();
        
        $hour = $this->_getHour();
        $minute = $this->_getMinute();
        $ampm = $this->_getAmpm();
        $hidden = $this->_getHidden();
        
        $render = '<tr><td>'
                . (empty($label) ? '&nbsp;' : "$label:")
                . "</td><td>$hour&nbsp;:&nbsp;$minute&nbsp;$ampm$hidden</td></tr>";
        
        return $render;
    }

    /**
     * Render the hour element
     * 
     * @return string
     */
    private function _getHour()
    {
        $render = "<select onchange='updateTimeField(\"{$this->_name}\")'"
                . " name='{$this->_name}Hour' id='{$this->_name}Hour'><option value=''></option>";
        
        for ($hour = 1; $hour <= 12; $hour++) {
            $selected = ($this->_hour == $hour) ? ' selected' : '';
            $render .= "<option value='$hour'$selected>$hour</option>";
        }
        
        $render .= '</select>';
        
        return $render;
    }

    /**
     * Render the minute element
     * 
     * @return string
     */
    private function _getMinute()
    {
        $render = "<select onchange='updateTimeField(\"{$this->_name}\")'"
                . " name='{$this->_name}Minute' id='{$this->_name}Minute'><option value=''></option>";

        for ($minute = 0; $minute <= 55; $minute += 5) {
            $minuteStr = str_pad($minute, 2, '0', STR_PAD_LEFT);
            $selected = ($this->_minute === $minuteStr) ? ' selected' : '';
            $render .= "<option value='$minuteStr'$selected>$minuteStr</option>";
        }
        
        $render .= '</select>';

        return $render;
    }

    /**
     * Render the AMPM element
     * 
     * @return string
     */
    private function _getAmpm()
    {
        $render = "<select onchange='updateTimeField(\"{$this->_name}\")'"
                . " name='{$this->_name}Ampm' id='{$this->_name}Ampm'><option value=''></option>";
        
        foreach ($this->_ampmList as $ampm) {
            $selected = ($this->_ampm == $ampm) ? ' selected' : '';
            $render .= "<option value='$ampm'$selected>$ampm</option>";
        }
        
        $render .= '</select>';
        
        return $render;
    }

    /**
     * Render the hidden element
     * 
     * @return string
     */
    private function _getHidden()
    {
        $render = "<input type='hidden' name='{$this->_name}'  id='{$this->_name}' value='{$this->_value}'>";
        
        return $render;
    }
}
