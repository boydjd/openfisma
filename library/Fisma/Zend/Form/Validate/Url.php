<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Validates the url
 * 
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 * 
 * @uses       Zend_Validate_Abstract
 */
class Fisma_Zend_Form_Validate_Url extends Zend_Validate_Abstract
{
    /**
     * Constant error message key 'invalidurl'
     */
    const INVALIDURL = 'invalidurl';

    /**
     * Error message templates
     * 
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALIDURL => "Not a valid URL."
    );

    /**
     * Validates the URL 
     * 
     * @param string $value 
     * @return bool 
     */
    public function isValid($value)
    {
        if (empty($value)) {
            return TRUE;
        } 
       
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->_error(self::INVALIDURL);
            return FALSE;
        }
    
        return TRUE;
    }
}
