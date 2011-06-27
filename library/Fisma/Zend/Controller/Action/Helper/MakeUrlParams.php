<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Translate the criteria to a string which can be used in an URL
 * OpenFISMA.
 *
 * @author     Woody Lee <woody712@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Controller
 */
class Fisma_Zend_Controller_Action_Helper_MakeUrlParams extends Zend_Controller_Action_Helper_Abstract
{
/**
     * Translate the criteria to a string which can be used in an URL
     *
     * The string can be parsed by the application to form the criteria again later.
     *
     * @param array $criteria The criteria array to transform
     * @return string The string which includes corresponding criterias and can be used in an URL
     */
    public function makeUrlParams($criteria)
    {
        $urlPart = '';
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                if ($value instanceof Zend_Date) {
                    $urlPart .= '/' . $key . '/' . $value->toString(Fisma_Date::FORMAT_DATE) . '';
                } else {
                    $urlPart .= '/' . $key . '/' . urlencode($value) . '';
                }
            }
        }
        return $urlPart;
    }
    
    /**
     * Perform helper when called as $this->_helper->makeUrlParams() from an action controller
     * 
     * @param array $list all overdue records
     */
    public function direct($criteria)
    {
        return $this->makeUrlParams($criteria);
    }
}
