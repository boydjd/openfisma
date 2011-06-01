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
 * Provide helper actions for the AttachArtifacts behavior
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class ArtifactController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set JSON context for the upload-progress action
     */
    public function init()
    {
        parent::init();
        
        $this->_helper->contextSwitch
                      ->setActionContext('upload-progress', 'json')
                      ->initContext();
    }

    /**
     * Display the artifact upload form
     */
    public function uploadFormAction()
    {
        // The view is rendered into a panel, so it doesn't need a layout
        $this->_helper->layout->disableLayout();
        
        // The upload form can be specified as a parameter. If not specified, load the default form.
        $formName = $this->getRequest()->getParam('form');

        if (!$formName) {
            $formName = 'upload_artifact';
        }

        $form = Fisma_Zend_Form_Manager::loadForm($formName);

        // Check that the form includes a few required elements to function correctly
        $fileElement = $form->getElement('file');

        if (is_null($fileElement) || !($fileElement instanceof Zend_Form_Element_File)) {
            throw new Fisma_Zend_Exception('Upload forms require a Zend_Form_Element_File named "file"');
        }

        $uploadElement = $form->getElement('uploadButton');

        if (is_null($uploadElement) || !($uploadElement instanceof Zend_Form_Element_Submit)) {
            throw new Fisma_Zend_Exception('Upload forms require a Zend_Form_Element_Submit named "uploadButton"');
        }
        
        // Select elements can be loaded from a table
        foreach ($form->getElements() as $element) {
            if ($element instanceof Zend_Form_Element_Select && $element->sourceTable) {
                $this->_loadSelectElementFromTable($element);
            }
        }
        
        // Set additional form attributes
        $form->setMethod("post");
        $form->setName("uploadArtifactForm");
        $form->setEnctype("multipart/form-data");
        $form->setAttrib('onsubmit', "return Fisma.AttachArtifacts.trackUploadProgress()");
                        
        $this->view->form = $form;
        $this->view->maxFileSize = ini_get('upload_max_filesize');
    }
    
    /**
     * Load a zend select element from a source table
     */
    private function _loadSelectElementFromTable(Zend_Form_Element_Select $select)
    {
        $table      = Inspekt::getAlnum($select->sourceTable);
        $indexField = Inspekt::getAlnum($select->indexField);
        $labelField = Inspekt::getAlnum($select->labelField);
        
        /*
         * This query uses interpolated parameters because there is no parameter binding in the select() or
         * from() methods. These parameters are safe because they are loaded from a form definition file which
         * is stored outside of the webroot.
         */
        $query = Doctrine_Query::create()
                 ->from("$table t INDEXBY $indexField")
                 ->select("$labelField")
                 ->orderBy("$labelField")
                 ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $selectOptions = $query->execute();
        
        foreach ($selectOptions as $id => $selectOption) {
            $select->addMultiOption($id, $selectOption['name']);
        }
    }
    
    /**
     * Check upload progress if APC is available
     */
    public function uploadProgressAction()
    {
        $apcId = $this->getRequest()->getParam('id');

        // Sanity check the apc id
        if (!preg_match('/^[0-9A-Za-z]+$/', $apcId)) {
            throw new Fisma_Zend_Exception("Invalid APC upload progress ID");
        }

        // Default return object. Indicates that upload progress is not an available feature on this server.
        $progress = array(
            'available' => false
        );
        
        // If APC exists, then add current progress info into return object
        if (function_exists('apc_fetch') && ini_get('apc.rfc1867')) {
            $progress = apc_fetch(ini_get('apc.rfc1867_prefix') . $apcId);
        }

        $this->view->progress = $progress;
    }
}
