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
 * Handles SA / Information Data Type
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Form
 */
class Sa_InformationDataTypeController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * Specify model name
     *
     * @var string
     */
    protected $_modelName = 'InformationDataType';

    /**
     * Return user-friendly name for the model
     *
     * @return string
     */
    public function getSingularModelName()
    {
        return 'Information Data Type';
    }

    /**
     * Return the form for the CRUD controller
     *
     * @return Fisma_Zend_Form_Default
     */
    public function getForm($formName = null)
    {
        return new Sa_InformationDataTypeForm();
    }
}
