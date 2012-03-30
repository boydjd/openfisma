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
 * Handles system types.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 */
class SystemTypeController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The name of the model managed by this object controller.
     *
     * @var string
     */
    protected $_modelName = 'SystemType';

    /**
     * Override to return a human-friendly name
     */
    public function getSingularModelName()
    {
        return 'System Type';
    }

    /**
     * Override to indicate that this model is not deletable.
     *
     * @return bool
     */
    protected function _isDeletable()
    {
        return false;
    }

    /**
     * Override parent to set up the image picker.
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);

        $icons = Doctrine_Query::create()
                 ->from('Icon i')
                 ->select("i.id, CONCAT('/icon/get/id/', i.id) as url")
                 ->execute()
                 ->toKeyValueArray('id', 'url');

        $form->getElement('iconId')
             ->setImageUrls($icons)
             // ->setImageManagementUrl("/icon/list") // Don't have time to implement this in this release
             ->setImageUploadUrl("/icon/upload/format/json");

        return $form;
    }
}
