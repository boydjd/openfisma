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
 * Handles CRUD for system documentation objects.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class SystemDocumentController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'SystemDocument';

    /**
     * All privileges to system documents are based on the related Organization objects
     */
    protected $_aclResource = 'Organization';

    /**
     * View detail information of the subject model
     *
     * @return void
     */
    public function viewAction()
    {
        $document = Doctrine::getTable('SystemDocument')->find($this->getRequest()->getParam('id'));
        $organization = $document->System->Organization;

        // There are no access control privileges for system documents, access is based on the associated organization
        $this->_acl->requirePrivilegeForObject('read', $organization);

        $historyQuery = Doctrine_Query::create()
                        ->from('SystemDocumentVersion v')
                        ->where('id = ?', $document->id)
                        ->orderBy('v.version desc');
        $versionHistory = $historyQuery->execute();

        $this->view->document = $document;
        $this->view->versionHistory = $versionHistory;
        $this->view->toolbarButtons = $this->getToolbarButtons();
    }

    /**
     * Download the specified system document
     *
     * @return void
     * @throws Fisma_Zend_Exception if requested file doesn`t exist
     */
    public function downloadAction()
    {
        $id = $this->getRequest()->getParam('id');
        $version = $this->getRequest()->getParam('version');
        $document = Doctrine::getTable('SystemDocument')->find($id);

        // Documents don't have their own privileges, access control is based on the associated organization
        $this->_acl->requirePrivilegeForObject('read', $document->System->Organization);

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $document = Doctrine::getTable('SystemDocument')->find($id);

        if (isset($version)) {
            $versionInfo = $document->getAuditLog()->getVersion($document, $version);
            // This is awkward. Doctrine's Versionable returns versions as arrays, not objects.
            // So we have to create a temporary object in order to execute the required logic.
            $document = new SystemDocument();
            $document->merge($versionInfo[0]);
        }

        if (is_null($document)) {
            throw new Fisma_Zend_Exception("Requested file does not exist.");
        }

        // Stream file to user's browser. Unset cache headers to false to avoid IE7/SSL errors.
        $this->getResponse()
             ->setHeader('Content-Type', $document->mimeType)
             ->setHeader('Cache-Control', null, true)
             ->setHeader('Pragma', null, true)
             ->setHeader('Content-Disposition', "attachment; filename=\"$document->fileName\"");

        $path = $document->getPath();

        $result = readfile($path);

        // Notice that 0 is an acceptable result, while FALSE is not, so use === instead of ==.
        if (false === $result) {
            throw new Fisma_Zend_Exception("Unable to read file $path");
        }
    }

    /**
     * Subclasses should override this if they want to use different buttons
     *
     * Default buttons are (subject to ACL):
     *
     * 1) List All <model name>s
     * 2) Create New <model name>
     *
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons()
    {
        $buttons = parent::getToolbarButtons();

        // Remove the "Create" button, since that function is accessed through the system artifacts screen
        unset($buttons['create']);

        return $buttons;
    }

    /**
     * Override parent to provide proper human-readable name for SystemDocument class
     */
    public function getSingularModelName()
    {
        return 'System Document';
    }

    /**
     * Override parent to provide proper human-readable name for SystemDocument class
     */
    public function getPluralModelName()
    {
        return 'System Documents';
    }

    /**
     * Override to indicate that this model is not deletable. (Since its versioned, we never delete a document.)
     * 
     * @return bool
     */
    protected function _isDeletable()
    {
        return false;
    }
}
