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
     * Store catalogId to update counters after deleting an information data type
     *
     * @var int
     */
    protected $_catalogId = null;

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
        $custom = $this->getRequest()->getParam('custom');
        return new Sa_InformationDataTypeForm($custom);
    }

    /**
     * Override PreCreate hook to handle "custom" catalog
     *
     * @GETAllowed
     * @return void
     */
    public function _preCreateHook()
    {
        $custom = $this->getRequest()->getParam('custom');
        if ($custom) {
            $this->_acl->requirePrivilegeForClass('custom', 'InformationDataType');
            $this->_enforceAcl = false;
        }
    }

    /**
     * Override PreView hook to handle "custom" catalog
     *
     * @GETAllowed
     * @return void
     */
    public function _preViewHook()
    {
        $id = $this->_request->getParam('id');
        $subject = $this->_getSubject($id);
        $custom = $subject->Catalog->name === 'Custom';
        $this->_request->setParam('custom', $custom);
        if (
            $custom &&
            $this->_acl->hasPrivilegeForClass('custom', 'InformationDataType') &&
            $subject->creatorId === CurrentUser::getAttribute('id')
        ) {
            $this->_enforceAcl = false;
        }
    }

    /**
     * Override PreDelete hook to handle "custom" catalog
     *
     * @return void
     */
    public function _preDeleteHook()
    {
        $id = $this->_request->getParam('id');
        $subject = $this->_getSubject($id);
        $custom = $subject->Catalog->name === 'Custom';
        $this->_request->setParam('custom', $custom);
        if (
            $custom &&
            $this->_acl->hasPrivilegeForClass('custom', 'InformationDataType') &&
            $subject->creatorId === CurrentUser::getAttribute('id')
        ) {
            $this->_enforceAcl = false;
        }
        $this->_catalogId = $subject->Catalog->id;
    }

    /**
     * Override PostDelete hook to update counters
     */
    protected function _postDeleteHook()
    {
        $catalog = Doctrine::getTable('InformationDataTypeCatalog')->find($this->_catalogId);
        $catalog->updateDenormalizedCounters();
        $catalog->save();
    }

    /**
     * Override saveValue hook to update catalog counters
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return Fisma_Doctrine_Record The saved object.
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject = null)
    {
        $subject = parent::saveValue($form, $subject);
        if (!$subject->creatorId) {
            $subject->creatorId = CurrentUser::getAttribute('id');
            $subject->save();
        }
        $subject->loadReference('Catalog');
        $subject->Catalog->updateDenormalizedCounters();
        $subject->Catalog->save();

        return $subject;
    }
}
