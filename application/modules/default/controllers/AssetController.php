<?php
/**
 * Copyright (c) 2009 Endeavor Systems, Inc.
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
     * Create contexts managing service tags via AJAX / JSON request
     *
     * @return void
     */
    public function init()
    {
        $this->_helper->ajaxContext()
             ->addActionContext('add-service-tag', 'json')
             ->addActionContext('rename-service-tag', 'json')
             ->addActionContext('add-service', 'html')
             ->addActionContext('edit-service', 'html')
             ->addActionContext('delete-service', 'html')
             ->addActionContext('add-service', 'json')
             ->addActionContext('edit-service', 'json')
             ->addActionContext('remove-service', 'json')
             ->initContext();

        parent::init();
    }

    /**
     * viewAction
     *
     * @return void
     *
     * @GETAllowed
     */
    public function viewAction()
    {
        $id = $this->_request->getParam('id');

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
        if ($fromSearchUrl) {
            $this->view->fromSearchUrl = $fromSearchUrl;
        }

        $asset = Doctrine::getTable('Asset')->find($id);

        if (!$asset) {
             $msg = '%s (%d) not found. Make sure a valid ID is specified.';
             throw new Fisma_Zend_Exception_User(sprintf($msg, $this->_modelName, $id));
        }

        $this->view->asset = $asset;

        $this->_acl->requirePrivilegeForObject('read', $asset);
        $this->view->canUpdate = $this->_acl->hasPrivilegeForObject('update', $asset);
        $this->view->canUpdateSystem = $this->_acl->hasPrivilegeForObject('unaffiliated', $asset);

        $this->view->toolbarButtons = $this->getToolbarButtons($asset);
        $this->view->searchButtons = $this->getSearchButtons($asset, $fromSearchParams);

        $this->view->serviceTable = new Fisma_Yui_DataTable_Local();
        $serviceTable = Doctrine::getTable('AssetService');
        $this->view->serviceTable
             ->addColumn(new Fisma_Yui_DataTable_HiddenColumn('id'))
             ->addColumn(new Fisma_Yui_DataTable_HiddenColumn('assetId'))
             ->addColumn(new Fisma_Yui_DataTable_Column(
                $serviceTable->getLogicalName('addressPort'), true, null, null, 'addressPort'))
             ->addColumn(new Fisma_Yui_DataTable_Column(
                $serviceTable->getLogicalName('protocol'), true, null, null, 'protocol'))
             ->addColumn(new Fisma_Yui_DataTable_Column(
                $serviceTable->getLogicalName('service'), true, null, null, 'service'))
             ->addColumn(new Fisma_Yui_DataTable_Column(
                $serviceTable->getLogicalName('productId'), true, null, null, 'product'));
        if ($this->_acl->hasPrivilegeForObject('update', $asset)) {
            $this->view->serviceTable->addColumn(
                new Fisma_Yui_DataTable_Column(
                    'Actions',
                    false,
                    'Fisma.TableFormat.formatActions',
                    array(
                        array(
                            'label' => 'edit',
                            'icon' => '/images/edit.png',
                            'handler' => 'Fisma.Asset.editService'
                        ),
                        array(
                            'label' => 'delete',
                            'icon' => '/images/trash_recyclebin_empty_open.png',
                            'handler' => 'Fisma.Asset.deleteService'
                        )
                    ),
                    'actions'
                )
            );
        }
        $services = array();
        foreach ($asset->AssetServices as $service) {
            $product = '';
            if (!empty($service->productId)) {
                $product = $service->Product->name;
            }
            $services[] = array(
                'id' => $service->id,
                'assetId' => $id,
                'addressPort' => $service->addressPort,
                'protocol' => empty($service->protocol) ? '' : $service->protocol,
                'service' => empty($service->service) ? '' : $service->service,
                'product' => $product,
            );
        }
        $this->view->serviceTable
             ->setData($services)
             ->setRespectOrder(false)
             ->setRegistryName('assetServiceTable');

        $addServiceUrl = $this->getHelper('url')
                              ->simple('add-service', null, null, array('id' => $id, 'format' => 'html'));
        if ($this->_acl->hasPrivilegeForObject('update', $asset)) {
            $this->view->addServiceButton = new Fisma_Yui_Form_Button(
                'addService',
                array(
                    'label' => 'Add Service',
                    'onClickFunction' => 'Fisma.Asset.addService',
                    'onClickArgument' => array('url' => $addServiceUrl),
                    'imageSrc' => '/images/create.png'
                )
            );
        } else {
            $this->view->addServiceButton = '';
        }
    }

    /**
     * updateAction
     *
     * @return void
     */
    public function updateAction()
    {
        $id = $this->_request->getParam('id');
        $asset = Doctrine::getTable('Asset')->find($id);
        if (!$asset) {
            throw new Fisma_Zend_Exception_User("Invalid Asset ID");
        }
        $newValues = $this->getRequest()->getPost();
        $this->_acl->requirePrivilegeForObject('update', $asset);
        if (isset($newValues['orgSystemId'])) {
            $this->_acl->requirePrivilegeForObject('unaffiliated', $asset);
        }

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        try {
            if (!empty($newValues)) {
                $asset->merge($newValues);
                $asset->save();
            }
        } catch (Doctrine_Validator_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }

        $this->_redirect("/asset/view/id/$id$fromSearchUrl");
    }

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

        $form->getElement('productId')->setValue($subject->productId);
        $form->getElement('product')->setValue($subject->Product->name);
        $form->getElement('serviceTag')->setValue($subject->serviceTag);

        return parent::setForm($subject, $form);
    }

    /**
     * Populating the service tag select menu
     *
     * @param String $formName Optional. Name of a specific form.
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);

        if (!isset($formName)) {
            $options = array('' => '');
            $tags = explode(',', Fisma::configuration()->getConfig('asset_service_tags'));
            foreach ($tags as $tag) {
                $options[$tag] = $tag;
            }
            $form->getElement('serviceTag')->setMultiOptions($options);
        }

        return $form;
    }

    /**
     * Hooks for manipulating and saving the values retrieved by Forms
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return Fisma_Doctrine_Record The saved record
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
        $subject->merge($values);
        $subject->save();

        return $subject;
    }

    /**
     * Customize the toolbar buttons
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $fromSearchParams = null)
    {
        if ($this->getRequest()->getActionName() == 'service-tags') {
            $buttons = array(new Fisma_Yui_Form_Button(
                'addTag',
                array(
                    'label' => 'Add',
                    'onClickFunction' => 'Fisma.Asset.addTag',
                    'imageSrc' => '/images/create.png'
                )
            ));
            return $buttons;
        }
        $buttons = parent::getToolbarButtons($record, $fromSearchParams);

        if ($this->_acl->hasPrivilegeForClass('unaffiliated', 'Asset')) {
            $button = new Fisma_Yui_Form_Button_Link(
                'importAssetsButton',
                array(
                    'value' => 'Import',
                    'href' => $this->getBaseUrl() . '/import',
                    'imageSrc' => '/images/up.png'
                )
            );
            array_unshift($buttons, $button);
        } else {
            unset($buttons['create']);
        }

        if ($record && isset($buttons['delete'])) {
            $vulnerabilities = Doctrine::getTable('Vulnerability')->findByAssetId($record->id);
            if ($vulnerabilities->count() > 0) {
                $onClickArgument = $buttons['delete']->getAttrib('onClickArgument');
                $onClickArgument['text'] = "WARNING: All {$vulnerabilities->count()} vulnerabilities associated with " .
                                           "this asset will also be deleted. Do you want to continue?";
                $buttons['delete']->setAttrib('onClickArgument', $onClickArgument);
            }
        }

        return $buttons;
    }

    /**
     * Import assets from an uploaded XML file using an import plugin
     *
     * @GETAllowed
     */
    public function importAction()
    {
        $this->_acl->requirePrivilegeForClass('create', 'Asset');
        $this->view->toolbarButtons = array(new Fisma_Yui_Form_Button(
            'upload',
            array(
                'label' => 'Submit',
                'imageSrc' => '/images/ok.png',
                'onClickFunction' => 'Fisma.Util.submitFirstForm',

            )
        ));

        $uploadForm = $this->getForm('asset_upload');

        // Configure the file select
        $uploadForm->setAttrib('enctype', 'multipart/form-data');

        $this->view->assign('uploadForm', $uploadForm);

        // Handle the file upload
        if ($postValues = $this->_request->getPost()) {
            $msgs = array();
            $err = FALSE;
            $filesReceived = ($uploadForm->selectFile->receive()) ? TRUE : FALSE;

            if (!$uploadForm->isValid($postValues)) {

                $file = $_FILES['selectFile'];
                if (Fisma_FileManager::getUploadFileError($file)) {
                    $msgs[] = array('warning' => Fisma_FileManager::getUploadFileError($file));
                } else {
                    $errorString = Fisma_Zend_Form_Manager::getErrors($uploadForm);
                    $msgs[] = array('warning' => $errorString);
                }

                $err = TRUE;
            } elseif (!$filesReceived) {
                $msgs[] = array('warning' => "File not received.");
                $err = TRUE;
            } else {
                $values = $uploadForm->getValues();
                $filePath = $uploadForm->selectFile->getTransferAdapter()->getFileName('selectFile');

                // get original file name
                $originalName = pathinfo(basename($filePath), PATHINFO_FILENAME);
                $values['filepath'] = $filePath;

                $upload = new Upload();

                $import = Fisma_Inject_Factory::create('Asset', $values);
                $import->parse(null);

                $msgs[] = $import->getMessages();

                // Add the file to storage
                $upload->instantiate(array(
                    'tmp_name' => $filePath,
                    'name' => $originalName,
                    'type' => $uploadForm->selectFile->getMimeType()
                ));

                // Need to save again after instantiate.
                $upload->save();
            }

            if ($err) {
                if (!empty($upload)) {
                    unlink($filePath);
                    $upload->delete();
                }

                if (!$msgs) {
                    $msgs[] = array('notice' => 'An unrecoverable error has occured.');
                }
            }

            $this->view->priorityMessenger($msgs);
        }
    }

    /**
     * Manage service tags
     *
     * @GETAllowed
     * @return void
     */
    public function serviceTagsAction()
    {
        $this->_acl->requirePrivilegeForClass('manage_service_tags', 'Asset');
        $data = array();
        $tags = explode(',', Fisma::configuration()->getConfig('asset_service_tags'));

        foreach ($tags as $tag) {
            $data[] = array(
                'tag' => $tag,
                'assets' => json_encode(array(
                    'displayText' =>
                        Doctrine_Query::create()->from('Asset')->where('serviceTag = ?', $tag)->count() . '', //toString
                    'url' => '/asset/list?q=/serviceTag/textExactMatch/' . $this->view->escape($tag, 'url')
                )),
                'edit' => 'javascript:Fisma.Asset.renameTag("' . $this->view->escape($tag, 'javascript') . '")',
                'delete' => '/asset/remove-service-tag/tag/' . $tag
            );
        }
        $table = new Fisma_Yui_DataTable_Local();
        $table->addColumn(new Fisma_Yui_DataTable_Column('Tag', false, 'YAHOO.widget.DataTable.formatText'))
              ->addColumn(new Fisma_Yui_DataTable_Column('Assets', false, 'Fisma.TableFormat.formatLink'))
              ->addColumn(new Fisma_Yui_DataTable_Column('Edit', false, 'Fisma.TableFormat.editControl'))
              ->addColumn(new Fisma_Yui_DataTable_Column('Delete', false, 'Fisma.TableFormat.deleteControl'))
              ->setData($data)
              ->setRegistryName('assetServiceTagTable');
        $this->view->toolbarButtons = $this->getToolbarButtons();
        $this->view->csrfToken = $this->_helper->csrf->getToken();
        $this->view->tags = $table;
    }

    /**
     * Add service tag via AJAX / JSON
     *
     * @return void
     */
    public function addServiceTagAction()
    {
        $this->view->result = new Fisma_AsyncResponse;
        $this->view->csrfToken = $this->_helper->csrf->getToken();

        if (!$this->_acl->hasPrivilegeForClass('manage_service_tags', 'Asset')) {
            $this->view->result->fail('Invalid permission');
        } else {
            $tag = $this->getRequest()->getParam('tag');
            if (!$tag) {
                $this->view->result->fail('Empty tag');
            } else {
                $tags = explode(',', Fisma::configuration()->getConfig('asset_service_tags'));
                if (in_array($tag, $tags)) {
                    $this->view->result->succeed('Tag already defined.');
                } else {
                    $tags[] = $tag;
                    Fisma::configuration()->setConfig('asset_service_tags', implode(',', $tags));
                    $this->view->result->succeed();
                }
            }
        }
    }

    /**
     * Rename service tag via AJAX / JSON
     *
     * @return void
     */
    public function renameServiceTagAction()
    {
        $this->view->result = new Fisma_AsyncResponse;
        $this->view->csrfToken = $this->_helper->csrf->getToken();

        if (!$this->_acl->hasPrivilegeForClass('manage_service_tags', 'Asset')) {
            $this->view->result->fail('Invalid permission');
        } else {
            $oldTag = $this->getRequest()->getParam('oldTag');
            $newTag = $this->getRequest()->getParam('newTag');
            if (!$oldTag || !$newTag) {
                $this->view->result->fail('Empty tag(s)');
            } else {
                $tags = explode(',', Fisma::configuration()->getConfig('asset_service_tags'));
                $key = array_search($oldTag, $tags);
                if ($key >= 0) {
                    $tags[$key] = $newTag;

                    try {
                        Doctrine_Manager::connection()->beginTransaction();

                        $assets = Doctrine::getTable('Asset')->findByServiceTag($oldTag);
                        foreach ($assets as $asset) {
                            $asset->serviceTag = $newTag;
                        }
                        $assets->save();

                        Fisma::configuration()->setConfig('asset_service_tags', implode(',', $tags));

                        Doctrine_Manager::connection()->commit();
                        $this->view->result->succeed();
                    } catch (Doctrine_Exception $e) {
                        Doctrine_Manager::connection()->rollback();
                        $this->view->result->fail($e->getMessage(), $e);
                    }
                } else {
                    $this->view->result->fail('Tag not found.');
                }
            }
        }
    }

    /**
     * Delete service tag via HTML POST
     *
     * @return void
     */
    public function removeServiceTagAction()
    {
        $this->_acl->requirePrivilegeForClass('manage_service_tags', 'Asset');

        $tag = $this->getRequest()->getParam('tag');
        if (!$tag) {
            throw new Fisma_Zend_Exception_User('Empty tag');
        } else {
            $tags = explode(',', Fisma::configuration()->getConfig('asset_service_tags'));
            $key = array_search($tag, $tags);
            if ($key >= 0) {
                unset($tags[$key]);

                try {
                    Doctrine_Manager::connection()->beginTransaction();

                    $assets = Doctrine::getTable('Asset')->findByServiceTag($tag);
                    foreach ($assets as $asset) {
                        $asset->serviceTag = '';
                    }
                    $assets->save();

                    Fisma::configuration()->setConfig('asset_service_tags', implode(',', $tags));

                    Doctrine_Manager::connection()->commit();
                    $this->_redirect('/asset/service-tags');
                } catch (Doctrine_Exception $e) {
                    throw $e;
                }
            } else {
                throw new Fisma_Zend_Exception_User('Tag not found.');
            }
        }
    }

    /**
     * addServiceAction
     *
     * @return void
     *
     * @GETAllowed
     */
    public function addServiceAction()
    {
        $id = $this->getRequest()->getParam("id");
        $asset = Doctrine::getTable('Asset')->find($id);
        $this->_acl->requirePrivilegeForObject('update', $asset);
        $form = $this->getForm('asset_service');
        $form->setAction($this->getHelper('url')->simple('add-service', null, null, array('id' => $id)));

        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            $this->view->post = $this->getRequest()->getPost();
            if ($form->isValid($this->getRequest()->getPost())) {
                $assetService = new AssetService();
                $assetService->assetId = $id;
                $assetService->merge($form->getValues());
                $assetService->save();
                $serviceArray = $assetService->toArray();
                if (!empty($assetService->productId)) {
                    $serviceArray['product'] = $assetService->Product->name;
                }
                $this->view->newService = $serviceArray;
            } else {
                $this->view->errors = Fisma_Zend_Form_Manager::getErrors($form);
            }
        }
    }

    /**
     * editServiceAction
     *
     * @return void
     *
     * @GETAllowed
     */
    public function editServiceAction()
    {
        $id = $this->getRequest()->getParam("id");
        $form = $this->getForm('asset_service');
        $form->setAction($this->getHelper('url')->simple('edit-service', null, null, array('id' => $id)));
        $service = Doctrine::getTable('AssetService')->find($id);
        $this->_acl->requirePrivilegeForObject('update', $service->Asset);
        $serviceArray = $service->toArray();
        if (!empty($service->productId)) {
            $serviceArray['product'] = $service->Product->name;
        }
        $form->setDefaults($serviceArray);

        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            $this->view->post = $this->getRequest()->getPost();
            if ($form->isValid($this->getRequest()->getPost())) {
                $service->merge($form->getValues());
                $service->save();
                $serviceArray = $service->toArray();
                if (!empty($service->productId)) {
                    $serviceArray['product'] = $service->Product->name;
                }
                $this->view->service = $serviceArray;
            } else {
                $this->view->errors = Fisma_Zend_Form_Manager::getErrors($form);
            }
        }
    }

    /**
     * removeServiceAction
     *
     * @return void
     */
    public function removeServiceAction()
    {
        $id = $this->getRequest()->getParam("id");
        $service = Doctrine::getTable('AssetService')->find($id);
        $this->_acl->requirePrivilegeForObject('update', $service->Asset);
        try {
            if (!empty($service)) {
                $service->delete();
            } else {
                $this->view->errors = "Service does not exist.";
            }
        } catch(Exception $e) {
            $this->view->errors = 'Error deleting service.';
        }
    }
}
