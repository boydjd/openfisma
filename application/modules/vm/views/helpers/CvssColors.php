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
 * A view helper to assign a CSS color code to a CVSS value
 * 
 * @uses Zend_View_Helper_Abstract
 * @package View_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Christian Smith <christian.smith@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */

class Vm_View_Helper_CvssColors extends Zend_View_Helper_Abstract
{
    private static $_baseVectorColors = array( 
        'AV' => array(
            'Local' => 'darkorange',
            'Adjacent Network' => 'darkorange',
            'Network' => 'red',
        ),
        'AC' => array(
            'High' => 'darkorange',
            'Medium' =>  'darkorange',
            'Low' => 'red',
        ),
        'Au' => array(
            'Multiple Instances' => 'darkorange',
            'Single Instance' => 'darkorange',
            'None' => 'red',
        ),
        'C' => array(
            'None' => 'green',
            'Partial' => 'darkorange',
            'Complete' =>  'red',
        ),
    
        'I' => array(
            'None' => 'green',
            'Partial' => 'darkorange',
            'Complete' => 'red',
        ),
    
        'A' => array( 
            'None' => 'green',
            'Partial' => 'darkorange',
            'Complete' => 'red',
        ),
    );

    public function baseVectorScoreColors($score)
    {
        if ($score >= 8) {
            return 'red';
        } elseif ($score >= 4) {
            return 'darkorange';
        } else {
            return 'green';
        }
    }
        
    public function cvssColors($type, $vector, $value) 
    {
        if ($type == 'base' && self::$_baseVectorColors[$vector][$value] != null) {
               return self::$_baseVectorColors[$vector][$value];
        }
        
        if ($type == 'score' && ($value >= 0 && $value <= 10)) {
           return $this->baseVectorScoreColors($value);
        }
    }
}
            

        
    
