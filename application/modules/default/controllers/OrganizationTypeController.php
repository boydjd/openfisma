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
 * CRUD behavior for organization type
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class OrganizationTypeController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'OrganizationType';

    /**
     * Override to return a human-friendly name
     */
    public function getSingularModelName()
    {
        return 'Organization Type';
    }

    /**
     * Override parent to provide proper human-readable name for OrganizationType class
     */
    public function getPluralModelName()
    {
        return 'Organization Types';
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
