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
            ->addActionContext('sel', 'html')
            ->addActionContext('add-type', 'json')
            ->addActionContext('remove-type', 'json')
            ->addActionContext('refresh-type', 'json')
            ->addActionContext('add-control', 'json')
            ->addActionContext('remove-control', 'json')
            ->addActionContext('set-common-control', 'json')
            ->addActionContext('import-baseline-control', 'json')
            ->addActionContext('get-control-enhancements', 'html')
            ->addActionContext('save-control-enhancements', 'json')
            ->addActionContext('get-common-controls', 'html')
            ->initContext();

        parent::init();
    }

    /**
     * Validate $id and fetch $this->view->system
     *
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $format = $this->getRequest()->getParam('format');
        $this->view->editable = !($this->getRequest()->getParam('readonly'));
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

        if (!empty($message)) {
            if ($this->view->isJson) {
                $this->view->err = $message;
            } else {
                throw new Fisma_Zend_Exception_User($message);
            }
        }
    }

    /**
     * Default view page
     *
     * @GETAllowed
     */
    public function viewAction()
    {
        $system = $this->view->system;
        $id = $this->view->system->id;

        $tabView = new Fisma_Yui_TabView('SecurityAuthorization', $id);

        $tabView->addTab("{$system->name} ($system->nickname)", "/system/system/id/$id/readonly/true");
        $tabView->addTab("1. Information Data Types", "/sa/security-authorization/cat/id/$id/format/html");
        $tabView->addTab("2. Security Controls", "/sa/security-authorization/sel/id/$id/format/html");

        $this->view->tabView = $tabView;
    }

    /**
     * Step 1. Categorization
     *
     * @GETAllowed
     */
    public function catAction()
    {
        $this->view->assignedTypes = Doctrine_Query::create()
            ->from('SystemInformationDataType')
            ->where('systemId = ?', $this->view->system->id)
            ->execute();

        $availableQuery = Doctrine_Query::create()
            ->from('InformationDataType idt')
            ->where('idt.published = ?', true);

        if ($this->view->assignedTypes->count() > 0) {
            $availableQuery->andWhereNotIn('idt.id',
                array_keys($this->view->assignedTypes->toKeyValueArray('informationDataTypeId', 'systemId')));
        }

        $this->view->availableTypes = $availableQuery->execute();
        $this->view->toolbarButtons = $this->getToolbarButtons();
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

        if ($action === 'cat') {
            $buttons = array(
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
                    'icon' => 'trash',
                    'onClickFunction' => 'Fisma.Sa.removeAllDataType'
                ))
            );
        }

        if ($action === 'sel') {
            $buttons = array(
                new Fisma_Yui_Form_Button('add', array(
                    'label' => 'Add',
                    'icon' => 'plus',
                    'onClickFunction' => 'Fisma.Sa.addControl'
                )),
                new Fisma_Yui_Form_Button('importBaseline', array(
                    'label' => 'Import Baseline Controls',
                    'icon' => 'download-alt',
                    'onClickFunction' => 'Fisma.Sa.importBaselineControl'
                )),
                new Fisma_Yui_Form_Button('importCommon', array(
                    'label' => 'Import Common Controls',
                    'icon' => 'download',
                    'tooltip' => Doctrine::getTable('SystemSecurityControl')->getComment('imported'),
                    'onClickFunction' => 'Fisma.Sa.importCommonControl'
                )),
                new Fisma_Yui_Form_Button('removeAll', array(
                    'label' => 'Remove All',
                    'icon' => 'trash',
                    'onClickFunction' => 'Fisma.Sa.removeAllSecurityControl'
                ))
            );
        }
        return $buttons;
    }

    /**
     * Assign an information data type to a system via AJAX
     */
    public function addTypeAction()
    {
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
                    $denormalizedDataType = $dataType->toArray();
                    $denormalizedDataType['catalog'] = $dataType->Catalog->name;
                    $sidt->denormalizedDataType = $denormalizedDataType;
                    $sidt->save();

                    $this->view->system->refreshFips();
                    $this->view->fipsCategory = $this->view->system->fipsCategory;
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message)) {
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
                        ->fetchOne();
                    if ($sidt) {
                        $this->view->dataType = $sidt->denormalizedDataType;
                        $sidt->delete();
                        $this->view->system->refreshFips();
                        $this->view->fipsCategory = $this->view->system->fipsCategory;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message)) {
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
                        ->fetchOne();
                    if ($sidt) {
                        $denormalizedDataType = $dataType->toArray();
                        $denormalizedDataType['catalog'] = $dataType->Catalog->name;
                        $sidt->denormalizedDataType = $denormalizedDataType;
                        $sidt->save();
                        $this->view->dataType = $sidt->denormalizedDataType;
                        $this->view->system->refreshFips();
                        $this->view->fipsCategory = $this->view->system->fipsCategory;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message)) {
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
        Doctrine_Query::create()
            ->delete()
            ->from('SystemInformationDataType')
            ->where('systemId = ?', $this->view->system->id)
            ->execute();

        $this->view->system->refreshFips();
        $this->view->priorityMessenger('All information data types removed successfully.', 'success');
        $this->_redirect("/sa/security-authorization/view/id/{$this->view->system->id}");
    }

    /**
     * Refresh a system's denormalized information data type
     */
    public function refreshAllTypeAction()
    {
        $assignedTypes = Doctrine_Query::create()
            ->from('SystemInformationDataType')
            ->where('systemId = ?', $this->view->system->id)
            ->execute();

        foreach ($assignedTypes as &$sidt) {
            $dataType = Doctrine::getTable('InformationDataType')->find($sidt->informationDataTypeId);
            $denormalizedDataType = $dataType->toArray();
            $denormalizedDataType['catalog'] = $dataType->Catalog->name;
            $sidt->denormalizedDataType = $denormalizedDataType;
        }

        $assignedTypes->save();
        $this->view->system->refreshFips();

        $this->view->priorityMessenger('All information data types refreshed successfully.', 'success');
        $this->_redirect("/sa/security-authorization/view/id/{$this->view->system->id}");
    }

    /**
     * Step 2. Selection
     *
     * @GETAllowed
     */
    public function selAction()
    {
        $this->view->selectedControls = Doctrine_Query::create()
            ->from('SystemSecurityControl')
            ->where('systemId = ?', $this->view->system->id)
            ->andWhere('imported <> ?', true)
            ->execute();

        $this->view->importedControls = Doctrine_Query::create()
            ->from('SystemSecurityControl')
            ->where('systemId = ?', $this->view->system->id)
            ->andWhere('imported = ?', true)
            ->execute();

        $availableQuery = Doctrine_Query::create()
            ->from('SecurityControl sc')
            ->leftJoin('sc.Catalog scc')
            ->where('scc.published = ?', true);

        if ($this->view->selectedControls->count() > 0) {
            $availableQuery->andWhereNotIn('sc.id',
                array_keys($this->view->selectedControls->toKeyValueArray('securityControlId', 'systemId')));
        }

        if ($this->view->importedControls->count() > 0) {
            $availableQuery->andWhereNotIn('sc.id',
                array_keys($this->view->importedControls->toKeyValueArray('securityControlId', 'systemId')));
        }

        $this->view->availableControls = $availableQuery->execute();
        $this->view->toolbarButtons = $this->getToolbarButtons();
    }

    /**
     * Select a security control for a system via AJAX
     */
    public function addControlAction()
    {
        $controlId = $this->getRequest()->getParam('dataTypeId');
        if (!$controlId) {
            $message = 'Please provide Security Control ID.';
        } else {
            $securityControl = Doctrine::getTable('SecurityControl')->find($controlId);
            if (!$securityControl) {
                $message = 'Invalid Security Control ID provided.';
            } else {
                try {
                    $ssc = new SystemSecurityControl();
                    $ssc->systemId = $this->view->system->id;
                    $ssc->securityControlId = $securityControl->id;
                    $ssc->save();
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message)) {
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
     * Deselect a security control from a system via AJAX
     */
    public function removeControlAction()
    {
        $controlId = $this->getRequest()->getParam('dataTypeId');
        if (!$controlId) {
            $message = 'Please provide Security Control ID.';
        } else {
            $securityControl = Doctrine::getTable('SecurityControl')->find($controlId);
            if (!$securityControl) {
                $message = 'Invalid Security Control ID provided.';
            } else {
                try {
                    $ssc = Doctrine_Query::create()
                        ->from('SystemSecurityControl')
                        ->where('systemId = ?', $this->view->system->id)
                        ->andWhere('securityControlId = ?', $securityControl->id)
                        ->fetchOne();
                    if ($ssc) {
                        $this->view->securityControl = $securityControl->toArray();
                        $ssc->delete();
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message)) {
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
     * Remove all security controls from a system
     */
    public function removeAllControlAction()
    {
        Doctrine_Query::create()
            ->delete()
            ->from('SystemSecurityControl')
            ->where('systemId = ?', $this->view->system->id)
            ->execute();

        $this->view->priorityMessenger('All security controls removed successfully.', 'success');
        $this->_redirect("/sa/security-authorization/view/id/{$this->view->system->id}");
    }

    /**
     * Toggle the "common" flag for a system's security control
     */
    public function setCommonControlAction()
    {
        $controlId = $this->getRequest()->getParam('dataTypeId');
        if (!$controlId) {
            $message = 'Please provide Security Control ID.';
        } else {
            $securityControl = Doctrine::getTable('SecurityControl')->find($controlId);
            if (!$securityControl) {
                $message = 'Invalid Security Control ID provided.';
            } else {
                try {
                    $ssc = Doctrine_Query::create()
                        ->from('SystemSecurityControl')
                        ->where('systemId = ?', $this->view->system->id)
                        ->andWhere('securityControlId = ?', $securityControl->id)
                        ->fetchOne();
                    if ($ssc) {

                        //@TODO: check if the control has been implemented (related to step 3)

                        $ssc->common = !($ssc->common);
                        $ssc->save();
                        $this->view->common = $ssc->common;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message)) {
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
     * Import baseline security controls into a system
     */
    public function importBaselineControlAction()
    {
        try {
            Doctrine_Manager::connection()->beginTransaction();

            $baselines = array('LOW', 'MODERATE', 'HIGH');
            switch ($this->view->system->fipsCategory) {
                case 'LOW':
                    array_pop($baselines);
                case 'MODERATE':
                    array_pop($baselines);
                case 'HIGH':
                    break;
            }

            $selectedControls = Doctrine_Query::create()
                ->from('SystemSecurityControl')
                ->where('systemId = ?', $this->view->system->id)
                ->execute();

            $controlQuery = Doctrine_Query::create()
                ->from('SecurityControl sc')
                ->leftJoin('sc.Catalog scc')
                ->leftJoin('sc.Systems s')
                ->where('scc.published = ?', true)
                ->andWhereIn('sc.controlLevel', $baselines);

            if ($selectedControls->count() > 0) {
                $controlQuery->whereNotIn('sc.id',
                    array_keys($selectedControls->toKeyValueArray('securityControlId', 'systemId')));
            }

            $controls = $controlQuery->execute();

            foreach ($controls as $securityControl) {
                $ssc = new SystemSecurityControl();
                $ssc->systemId = $this->view->system->id;
                $ssc->securityControlId = $securityControl->id;
                $ssc->save();
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollback();
            throw $e;
        }

        $this->view->priorityMessenger('Baseline security controls imported successfully.', 'success');
        $this->_redirect("/sa/security-authorization/view/id/{$this->view->system->id}");
    }

    /**
     * Return a list of enhancements to be rendered in a popup
     *
     * @GETAllowed
     */
    public function getControlEnhancementsAction()
    {
        $controlId = $this->getRequest()->getParam('dataTypeId');
        if (!$controlId) {
            $message = 'Please provide Security Control ID.';
        } else {
            $securityControl = Doctrine::getTable('SecurityControl')->find($controlId);
            if (!$securityControl) {
                $message = 'Invalid Security Control ID provided.';
            } else {
                $ssc = Doctrine_Query::create()
                    ->from('SystemSecurityControl')
                    ->where('systemId = ?', $this->view->system->id)
                    ->andWhere('securityControlId = ?', $securityControl->id)
                    ->fetchOne();
                if ($ssc) {
                    $this->view->selectedEnhancements = (array)$ssc->enhancements;
                }
                $this->view->enhancements = $securityControl->Enhancements;
            }
        }

        if (!empty($message)) {
            throw new Fisma_Zend_Exception_User($message);
        }
    }

    /**
     * Save the list of selected enhancements for a system's security control via AJAX
     */
    public function saveControlEnhancementsAction()
    {
        $controlId = $this->getRequest()->getParam('dataTypeId');
        if (!$controlId) {
            $message = 'Please provide Security Control ID.';
        } else {
            $securityControl = Doctrine::getTable('SecurityControl')->find($controlId);
            if (!$securityControl) {
                $message = 'Invalid Security Control ID provided.';
            } else {
                try {
                    $ssc = Doctrine_Query::create()
                        ->from('SystemSecurityControl')
                        ->where('systemId = ?', $this->view->system->id)
                        ->andWhere('securityControlId = ?', $securityControl->id)
                        ->fetchOne();
                    if ($ssc) {
                        $ssc->enhancements = Zend_Json::decode($this->getRequest()->getParam('selectedEnhancements'));
                        $ssc->save();
                    } else {
                        $message = 'Security Control not selected for this system.';
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                }
            }
        }

        if (!empty($message)) {
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
     * Return a list of systems with common controls to be rendered in a popup
     *
     * @GETAllowed
     */
    public function getCommonControlsAction()
    {
        $ssc = Doctrine_Query::create()
            ->select('systemId as id, COUNT(securityControlId) as controls')
            ->from('SystemSecurityControl')
            ->groupBy('systemId')
            ->where('systemId <> ?', $this->view->system->id)
            ->andWhere('common = ?', true)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();

        foreach ($ssc as &$system) {
            if ($system['id']) {
                $systemObject = Doctrine::getTable('System')->find($system['id']);
                $system['name'] = $systemObject->Organization->name;
                $system['nickname'] = $systemObject->Organization->nickname;
                $system['fipsCategory'] = $systemObject->fipsCategory;
            }
        }
        $this->view->systems = $ssc;

        if (!empty($message)) {
            throw new Fisma_Zend_Exception_User($message);
        }
    }

    /**
     * Import common controls into a system
     */
    public function importCommonControlAction()
    {
        $destinationId = $this->getRequest()->getParam('dataTypeId');
        if (!$destinationId) {
            $message = 'Please provide Source System ID.';
        } else {
            $securityControl = Doctrine::getTable('System')->find($destinationId);
            if (!$securityControl) {
                $message = 'Invalid Source System ID provided.';
            } else {
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    $selectedControls = Doctrine_Query::create()
                        ->from('SystemSecurityControl')
                        ->where('systemId = ?', $this->view->system->id)
                        ->execute();

                    $controlQuery = Doctrine_Query::create()
                        ->from('SystemSecurityControl')
                        ->where('systemId = ?', $destinationId)
                        ->andWhere('common = ?', true);

                    if ($selectedControls->count() > 0) {
                        $controlQuery->whereNotIn('securityControlId',
                            array_keys($selectedControls->toKeyValueArray('securityControlId', 'systemId')));
                    }

                    $controls = $controlQuery->execute();

                    foreach ($controls as $securityControlSelection) {
                        $ssc = new SystemSecurityControl();
                        $ssc->systemId = $this->view->system->id;
                        $ssc->securityControlId = $securityControlSelection->securityControlId;
                        $ssc->enhancements = $securityControlSelection->enhancements;
                        $ssc->imported = true;
                        $ssc->save();
                    }

                    Doctrine_Manager::connection()->commit();
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $stackTrace = $e->getTraceAsString();
                    Doctrine_Manager::connection()->rollback();
                }
            }
        }

        if (!empty($message)) {
            throw new Fisma_Zend_Exception_User($message);
        }

        $this->view->priorityMessenger('Common security controls imported successfully.', 'success');
        $this->_redirect("/sa/security-authorization/view/id/{$this->view->system->id}");
    }
}
