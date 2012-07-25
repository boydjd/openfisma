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
     * Overriding associated model information
     */
    protected $_associatedModel = 'Organization';
    protected $_associatedPlural = 'Organizations';

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
     * Override to disable mass deletion
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

        $maxSize = Fisma_String::convertFilesizeToInteger(Fisma::configuration()->getConfig('max_file_upload_size'));
        $maxFilesizeEle = new Zend_Form_Element_Hidden("MAX_UPLOAD_FILE_SIZE");
        $maxFilesizeEle->setValue($maxSize);
        $form->addElement($maxFilesizeEle);

        if (!$formName) {
            $icons = Doctrine_Query::create()
                     ->from('Icon i')
                     ->select("i.id, CONCAT('/icon/get/id/', i.id) as url")
                     ->execute()
                     ->toKeyValueArray('id', 'url');

            $form->getElement('iconId')
                 ->setImageUrls($icons)
                 // ->setImageManagementUrl("/icon/list") // Don't have time to implement this in this release
                 ->setImageUploadUrl("/icon/upload/format/json");
        }

        return $form;
    }

    /**
     * Customize the toolbar buttons
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @param array $fromSearchParams The array for "Previous" and "Next" button null if not
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $fromSearchParams = null)
    {
        $buttons = parent::getToolbarButtons($record, $fromSearchParams);

        if (
            $this->_acl->hasPrivilegeForClass('delete', 'OrganizationType') &&
            $this->getRequest()->getActionName() == 'view'
        ) {
            $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
            $args = array(null, $this->getBaseUrl() . '/delete' . $fromSearchUrl, $record['id']);

            $buttons['delete'] = new Fisma_Yui_Form_Button(
                'deleteOrganizationTypeButton',
                array(
                    'label' => 'Delete',
                    'onClickFunction' => 'Fisma.Util.showConfirmDialog',
                    'onClickArgument' => array(
                        'args' => $args,
                        'text' => "WARNING: You are about to delete this organization type. This action cannot be "
                                . "undone. Do you want to continue?",
                        'func' => 'Fisma.Util.formPostAction'
                    ),
                    'imageSrc' => '/images/trash_recyclebin_empty_closed.png'
                )
            );
        }

        return $buttons;
    }

    /**
     * Delete a system type
     *
     * @return void
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilegeForClass('delete', 'OrganizationType');

        $id = $this->getRequest()->getParam('id');
        $organizationType = Doctrine_Query::create()
            ->from('OrganizationType ot')
            ->leftJoin('ot.Organizations')
            ->where('ot.id = ?', $id)
            ->execute()
            ->getFirst();
        if (!$organizationType) {
           throw new Fisma_Zend_Exception("No organization type found with id ($id).");
        }

        $count = $organizationType->Organizations->count();
        if ($count > 0) {
            $searchLink = '/organization/list?q=/orgType/textExactMatch/'
                        . $this->view->escape($organizationType->nickname, 'url');
            $this->view->priorityMessenger(
                "This Organization Type is associated with <a href='$searchLink'>$count organization(s)</a>.<br/>" .
                "Please associate them with other organization types, or click " .
                "<a href='#' onclick='Fisma.Util.triggerButton(\"toolbarReassociateButton\");return false;'>here</a> " .
                "to quickly migrate all of them to another organization type and try again.",
                "warning"
            );

            $fromSearchParams = $this->_getFromSearchParams($this->_request);
            $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

            $this->_redirect($this->getBaseUrl() . '/view/id/' . $id . $fromSearchUrl);
        } else {
            $organizationType->delete();

            $this->view->priorityMessenger("Organization Type deleted successfully");

            $this->_redirect($this->getBaseUrl() . '/list');
        }
    }
}
