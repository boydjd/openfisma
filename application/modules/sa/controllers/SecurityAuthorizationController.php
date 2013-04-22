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
class Sa_SecurityAuthorizationController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set up AJAX actions
     */
    public function init()
    {
        $this->_helper->ajaxContext()
            ->addActionContext('cat', 'html')
            ->addActionContext('add-type', 'json')
            ->addActionContext('remove-type', 'json')
            ->addActionContext('refresh-type', 'json')
            ->initContext();

        parent::init();
    }

    /**
     * Default view page
     *
     * @GETAllowed
     */
    public function viewAction()
    {
        $this->_prepare();
        $system = $this->view->system;
        $id = $this->view->system->id;

        $tabView = new Fisma_Yui_TabView('SecurityAuthorization', $id);

        $tabView->addTab("{$system->name} ($system->nickname)", "/system/system/id/$id/readonly/true");
        $tabView->addTab("1. Categorization", "/sa/security-authorization/cat/id/$id/format/html");

        $this->view->tabView = $tabView;
    }

    /**
     * Step 1. Categorization
     *
     * @GETAllowed
     */
    public function catAction()
    {
        $this->_prepare();
        $this->view->assignedTypes = Doctrine_Query::create()
            ->from('SystemInformationDataType')
            ->where('systemId = ?', $this->view->system->id)
            ->execute();

        $this->view->availableTypes = Doctrine_Query::create()
            ->from('InformationDataType idt')
            ->leftJoin('idt.Catalog idtc')
            ->leftJoin('idt.Systems s')
            ->where('s.id <> ?', $this->view->system->id)
            ->andWhere('idtc.published = ?', true)
            ->orWhere('s.id is NULL')
            ->andWhere('idtc.published = ?', true)
            ->execute();

        $this->view->toolbarButtons = array(
            new Fisma_Yui_Form_Button('add', array(
                'label' => 'Add',
                'icon' => 'plus',
                'onClickFunction' => 'Fisma.Sa.addDataType'
            )),
            new Fisma_Yui_Form_Button('refreshAll', array(
                'label' => 'Refresh All',
                'icon' => 'refresh',
                'onClickFunction' => 'Fisma.Sa.refreshAllDataType'
            )),
            new Fisma_Yui_Form_Button('removeAll', array(
                'label' => 'Remove All',
                'icon' => 'remove',
                'onClickFunction' => 'Fisma.Sa.removeAllDataType'
            ))
        );
    }

    /**
     * Validate $id and fetch $this->view->system
     */
    protected function _prepare()
    {
        $format = $this->getRequest()->getParam('format');
        $this->view->isJson = ($format === 'json');
        $message = '';

        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            $message = 'Please provide System ID.';
        } else {
            $this->view->system = Doctrine::getTable('System')->find($id);
            if (!$this->view->system) {
                $message = 'Invalid System ID provided.';
            } else {
                if (!$this->_acl->hasPrivilegeForObject('sa', $this->view->system->Organization)) {
                    $message = "User does not have privilege 'sa' for this object.";
                } else {
                    $this->view->toolbarButtons = $this->getToolbarButtons($this->view->system);
                }
            }
        }

        if (!empty($message))
        {
            if ($this->view->isJson) {
                $this->view->err = $message;
            } else {
                throw new Fisma_Zend_Exception_User($message);
            }
        }
    }

    /**
     * Add more buttons to the toolbar
     *
     * @param Fisma_Doctrine_Record $subject Optional. The object currently loaded for the view.
     */
    public function getToolbarButtons($subject = null)
    {
        $action = $this->getRequest()->getActionName();
        $buttons = array();

        if ($action === 'view' && $subject) {
            $detailButton = new Fisma_Yui_Form_Button_Link(
                'detailButton',
                array(
                    'value' => 'System Details',
                    'icon' => 'th-list',
                    'href' => "/system/view/id/{$subject->id}"
                )
            );
            $buttons['detailButton'] = $detailButton;
        }

        return $buttons;
    }

    /**
     * Assign an information data type to a system via AJAX
     */
    public function addTypeAction()
    {
        $this->_prepare();
        $dataTypeId = $this->getRequest()->getParam('dataTypeId');
        if (!$dataTypeId) {
            $message = 'Please provide Information Data Type ID.';
        } else {
            $dataType = Doctrine::getTable('InformationDataType')->find($dataTypeId);
            if (!$dataType) {
                $message = 'Invalid Information Data Type ID provided.';
            } else {
                try {
                    $sidt = new SystemInformationDataType();
                    $sidt->systemId = $this->view->system->id;
                    $sidt->informationDataTypeId = $dataType->id;
                    $sidt->denormalizedDataType = $dataType->toArray();
                    $sidt->save();
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message))
        {
            if ($this->view->isJson) {
                $this->view->err = $message;
                if ($stackTrace && Fisma::debug()) {
                    $this->view->errStackTrace = $stackTrace;
                }
            } else {
                throw new Fisma_Zend_Exception_User($message);
            }
        } else {
            if ($this->view->isJson) {
                $this->view->success = true;
            }
        }
    }

    /**
     * Unassign an information data type from a system via AJAX
     */
    public function removeTypeAction()
    {
        $this->_prepare();
        $dataTypeId = $this->getRequest()->getParam('dataTypeId');
        if (!$dataTypeId) {
            $message = 'Please provide Information Data Type ID.';
        } else {
            $dataType = Doctrine::getTable('InformationDataType')->find($dataTypeId);
            if (!$dataType) {
                $message = 'Invalid Information Data Type ID provided.';
            } else {
                try {
                    $sidt = Doctrine_Query::create()
                        ->delete()
                        ->from('SystemInformationDataType')
                        ->where('systemId = ?', $this->view->system->id)
                        ->andWhere('informationDataTypeId = ?', $dataType->id)
                        ->execute();
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message))
        {
            if ($this->view->isJson) {
                $this->view->err = $message;
                if ($stackTrace && Fisma::debug()) {
                    $this->view->errStackTrace = $stackTrace;
                }
            } else {
                throw new Fisma_Zend_Exception_User($message);
            }
        } else {
            if ($this->view->isJson) {
                $this->view->success = true;
            }
        }
    }

    /**
     * Refresh a system's denormalized information data type via AJAX
     */
    public function refreshTypeAction()
    {
        $this->_prepare();
        $dataTypeId = $this->getRequest()->getParam('dataTypeId');
        if (!$dataTypeId) {
            $message = 'Please provide Information Data Type ID.';
        } else {
            $dataType = Doctrine::getTable('InformationDataType')->find($dataTypeId);
            if (!$dataType) {
                $message = 'Invalid Information Data Type ID provided.';
            } else {
                try {
                    $sidt = Doctrine_Query::create()
                        ->from('SystemInformationDataType')
                        ->where('systemId = ?', $this->view->system->id)
                        ->andWhere('informationDataTypeId = ?', $dataType->id)
                        ->execute();
                    if ($sidt) {
                        $sidt->denormalizedDataType = $dataType->toArray();
                        //$sidt->save();
                        $this->view->dataType = $sidt->denormalizedDataType;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message))
        {
            if ($this->view->isJson) {
                $this->view->err = $message;
                if ($stackTrace && Fisma::debug()) {
                    $this->view->errStackTrace = $stackTrace;
                }
            } else {
                throw new Fisma_Zend_Exception_User($message);
            }
        } else {
            if ($this->view->isJson) {
                $this->view->success = true;
            }
        }
    }

    /**
     * Remove all information data type from a system
     */
    public function removeAllTypeAction()
    {
        $this->_prepare();

        Doctrine_Query::create()
            ->delete()
            ->from('SystemInformationDataType')
            ->where('systemId = ?', $this->view->system->id)
            ->execute();

        $this->view->priorityMessenger('All information data types removed successfully.', 'success');
        $this->_redirect("/sa/security-authorization/view/id/{$this->view->system->id}");
    }

    /**
     * Refresh a system's denormalized information data type
     */
    public function refreshAllTypeAction()
    {
        $this->_prepare();

        $assignedTypes = Doctrine_Query::create()
            ->from('SystemInformationDataType')
            ->where('systemId = ?', $this->view->system->id)
            ->execute();

        foreach ($assignedTypes as &$sdit) {
            $dataType = Doctrine::getTable('InformationDataType')->find($sdit->informationDataTypeId);
            $sdit->denormalizedDataType = $dataType->toArray();
        }

        $assignedTypes->save();

        $this->view->priorityMessenger('All information data types refreshed successfully.', 'success');
        $this->_redirect("/sa/security-authorization/view/id/{$this->view->system->id}");
    }
}
