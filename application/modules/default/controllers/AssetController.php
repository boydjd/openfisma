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
 * The asset controller deals with creating, updating, and managing assets on the system.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * 
 * @see        Zend_View_Helper_Abstract
 */
class AssetController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'Asset';

    /**
     * Invokes a contract with Fisma_Zend_Controller_Action_Object regarding privileges.
     * 
     * @var string
     * @link http://jira.openfisma.org/browse/OFJ-24
     */
    protected $_organizations = '*';
    
    /**
     * Hooks for manipulating the values before setting to a form
     * 
     * @param Doctrine_Record $subject The specified subject model
     * @param Zend_Form $form The specified form
     * @return Zend_Form The manipulated form
     */
    protected function setForm($subject, $form)
    {
        $product = $subject->Product;

        if ($this->getRequest()->getParam('sub') != 'edit') 
            $form->getElement('product')->setAttrib('readonly', true);

        $form->getElement('product')->setValue($subject->productId)
                                    ->setDisplayText($subject->Product->name);

        return parent::setForm($subject, $form);
    }

    /**
     * Hooks for manipulating and saving the values retrieved by Forms
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return integer Asset ID
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } elseif (!$subject instanceof Doctrine_Record) {
            throw new Fisma_Zend_Exception('Invalid parameter: Expected a Doctrine_Record');
        }

        $values = $form->getValues();

        $productIdField = $form->getElement('product')->getAttrib('hiddenField');
        $values['productId'] = $this->getRequest()->getParam($productIdField);

        if (empty($values['productId'])) {
            unset($values['productId']);
        }

        $subject->merge($values);
        $subject->save();
        return $subject->id;
    }

    /**
     * Customize the toolbar buttons
     *
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons()
    {
        $buttons = parent::getToolbarButtons();

        if ($this->_acl->hasPrivilegeForClass('create', 'Asset')) {
            $buttons[] = new Fisma_Yui_Form_Button_Link(
                'importAssetsButton',
                array(
                    'value' => 'Import Assets',
                    'href' => $this->getBaseUrl() . '/import'
                )
            );
        }

        return $buttons;
    }

    /**
     * Import assets from an uploaded XML file using an import plugin
     */
    public function importAction()
    {
        $this->_acl->requirePrivilegeForClass('create', 'Asset');

        $uploadForm = $this->getForm('asset_upload');

        // Configure the file select
        $uploadForm->setAttrib('enctype', 'multipart/form-data');
        $uploadForm->selectFile->setDestination(Fisma::getPath('data') . '/uploads/scanreports');

        $this->view->assign('uploadForm', $uploadForm);

        // Handle the file upload
        if ($postValues = $this->_request->getPost()) {
            $msgs = array();
            $err = FALSE;
            $filesReceived = ($uploadForm->selectFile->receive()) ? TRUE: FALSE;

            if (!$uploadForm->isValid($postValues)) {
                $msgs[] = array('warning' => Fisma_Zend_Form_Manager::getErrors($uploadForm));
                $err = TRUE;
            } elseif (!$filesReceived) {
                $msgs[] = array('warning' => "File not received.");
                $err = TRUE;
            } else {
                $values = $uploadForm->getValues();
                $filePath = $uploadForm->selectFile->getTransferAdapter()->getFileName('selectFile');

                // get original file name
                $originalName = pathinfo(basename($filePath), PATHINFO_FILENAME);
                // get current time and set to a format like '20090504_112202'
                $dateTime = Zend_Date::now()->toString(Fisma_Date::FORMAT_FILENAME_DATETIMESTAMP);
                // define new file name
                $newName = str_replace($originalName, $originalName . '_' . $dateTime, basename($filePath));
                rename($filePath, $filePath = dirname($filePath) . '/' . $newName);

                $values['filePath'] = $filePath;

                $upload = new Upload();
                $upload->userId = $this->_me->id;
                $upload->fileName = basename($filePath);
                $upload->save();
                    
                $import = Fisma_Import_Factory::create('asset', $values);
                $success = $import->parse();

                if (!$success) {
                    foreach ($import->getErrors() as $error)
                        $msgs[] = array('warning' => $error);

                    $err = TRUE;
                } else {
                    $numCreated = $import->getNumImported();
                    $numSuppressed = $import->getNumSuppressed();
                    $msgs[] = array('notice' => "{$numCreated} asset(s) were imported successfully.");
                    $msgs[] = array('notice' => "{$numSuppressed} asset(s) were not imported.");
                }
            }

            if ($err) {
                if (!empty($upload)) {
                    unlink($filePath);
                    $upload->delete();
                }

                if (!$msgs) 
                    $msgs[] = array('notice' => 'An unrecoverable error has occured.');
            }

            $this->view->priorityMessenger($msgs);
        }
    }

    protected function _isDeletable()
    {
        return false;
    }
}
