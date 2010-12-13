<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Sa_AssessmentPlanEntryController
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    SA
 */
class Sa_AssessmentPlanEntryController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'AssessmentPlanEntry';

    /**
     * Return array of the collection.
     * 
     * @param Doctrine_Collections $rows The spepcific Doctrine_Collections object
     * @return array The array representation of the specified Doctrine_Collections object
     */
    public function handleCollection($rows)
    {
       $result = $rows->toArray();
       foreach ($rows as $key => $record) {
           $sasca = $record->SaSecurityControlAggregate;
           if ($sasca instanceof SaSecurityControl) {
               $result[$key]['code'] = $sasca->SecurityControl->code;
           } else if ($sasca instanceof SaSecurityControlEnhancement) {
               $result[$key]['code'] = $sasca->SaSecurityControl->SecurityControl->code;
               $result[$key]['enhancement'] = $sasca->SecurityControlEnhancement->number;
           } else {
               throw new Fisma_Zend_Exception('Unknown record type. ' . get_class($sasca));
           }
       }
       return $result;
    }

    /**
     * Custom search action to allow filtering by SA
     *
     * @return void
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());
        $sortBy = $this->_request->getParam('sortby', 'id');
        $order  = $this->_request->getParam('order');
        $keywords  = html_entity_decode($this->_request->getParam('keywords')); 
        $saId = $this->_request->getParam('said');
        $offset = $this->_request->getParam('start', 0);
        $otherThanSatisfied = $this->_request->getParam('otherThanSatisfied', false);

        //filter the sortby to prevent sqlinjection
        $subjectTable = Doctrine::getTable($this->_modelName);
        if (!in_array(strtolower($sortBy), $subjectTable->getColumnNames())) {
            return $this->_helper->json('Invalid "sortBy" parameter');
        }

        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $sasc = Doctrine_Query::create()
            ->from('SaSecurityControl sasc, sasc.SecurityControl sc')
            ->where('sasc.securityAuthorizationId = ?', $saId)
            ->execute();
        $sasce = Doctrine_Query::create()
            ->from(
                'SaSecurityControlEnhancement sasce, ' .
                'sasce.SecurityControlEnhancement sce, ' .
                'sasce.SaSecurityControl sasc, ' .
                'sasc.SecurityControl sc'
            )
            ->where('sasc.securityAuthorizationId = ?', $saId)
            ->execute();
        $sasca = new Doctrine_Collection('SaSecurityControlAggregate');
        $sasca->merge($sasc);
        $sasca->merge($sasce);
        $query  = Doctrine_Query::create()
            ->from('AssessmentPlanEntry ape')
            ->leftJoin('ape.SaSecurityControlAggregate sasca')
            ->whereIn('sasca.id', $sasca->toKeyValueArray('id', 'id'))
            ->orderBy("$sortBy $order")
            ->limit($this->_paging['count'])
            ->offset($offset);
 
        // for authorization step, we only want to show assessments resulting in "Other Than Satisfied"
        if ($otherThanSatisfied) {
            $query->andWhere('ape.result = ?', 'Other Than Satisfied');
        }

        //initialize the data rows
        $tableData    = array('table' => array(
                            'recordsReturned' => 0,
                            'totalRecords'    => 0,
                            'startIndex'      => $this->_paging['startIndex'],
                            'sort'            => $sortBy,
                            'dir'             => $order,
                            'pageSize'        => $this->_paging['count'],
                            'records'         => array()
                        ));
        if (!empty($keywords)) {
            // lucene search 
            $index = new Fisma_Index($this->_modelName);
            $ids = $index->findIds($keywords);
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                //no data
                return $this->_helper->json($tableData);
            }
        }

        $totalRecords = $query->count();
        $rows         = $this->executeSearchQuery($query);
        $rows         = $this->handleCollection($rows);
        $tableData['table']['recordsReturned'] = count($rows);
        $tableData['table']['totalRecords'] = $totalRecords;
        $tableData['totalRecords'] = $totalRecords;
        $tableData['table']['records'] = $rows;
        return $this->_helper->json($tableData);
    }

    /**
     * Override parent behavior
     *
     * The FZCAO::viewAction method calls View::render() at the end for some reason.  I've copied all but that line
     * here so I wouldn't have to modify the base class implementation to add my extra bits.  This will probably need
     * to be rewritten after the new search functionality is merged in.
     *
     * @return void
     */
    public function viewAction()
    {
        $id     = $this->_request->getParam('id');
        $subject = Doctrine::getTable($this->_modelName)->find($id);
        if (!$subject) {
            throw new Fisma_Zend_Exception("Invalid {$this->_modelName} ID");
        }
        $this->_acl->requirePrivilegeForObject('read', $subject);

        $form   = $this->getForm();

        $this->view->assign('editLink', "{$this->_moduleName}/{$this->_controllerName}/edit/id/$id");
        $form->setReadOnly(true);            
        $this->view->assign('deleteLink', "{$this->_moduleName}/{$this->_controllerName}/delete/id/$id");
        $this->setForm($subject, $form);
        $this->view->form = $form;
        $this->view->id   = $id;
        $this->view->subject = $subject;

        $this->_addArtifactUploadButton();
        $this->_addArtifactsArray();
    }

    protected function _addArtifactUploadButton()
    {
        // Upload button
        $uploadPanelButton = new Fisma_Yui_Form_Button(
            'uploadPanelButton', 
            array(
                'label' => 'Upload New Artifact', 
                'onClickFunction' => 'Fisma.AttachArtifacts.showPanel',
                'onClickArgument' => array(
                    'id' => $this->view->id,
                    'server' => array(
                        'module' => 'sa',
                        'controller' => 'assessment-plan-entry',
                        'action' => 'attach-artifact'                        
                    ),
                    'callback' => array(
                        'object' => 'AssessmentPlanEntry',
                        'method' => 'attachArtifactCallback'
                    )
                )
            )
        );

        // @todo conditionally disable button for users without access
        //$uploadPanelButton->readOnly = true;
        
        $this->view->uploadPanelButton = $uploadPanelButton;
    }

    protected function _addArtifactsArray()
    {
        $ape = Doctrine::getTable('AssessmentPlanEntry')->find($this->view->id);
        $artifactCollection = $ape->getArtifacts()->fetch(Doctrine::HYDRATE_RECORD);;
        $artifacts = array();
        
        foreach ($artifactCollection as $artifact) {
            $artifactArray = $artifact->toArray();
            $artifactArray['iconUrl'] = $artifact->getIconUrl();
            $artifactArray['fileSize'] = $artifact->getFileSize();
            
            $artifacts[] = $artifactArray;
        }

        $this->view->artifacts = $artifacts;
    }

    public function attachArtifactAction()
    {
        $id = $this->getRequest()->getParam('id');
        $comment = $this->getRequest()->getParam('comment');
        
        $this->_helper->layout->disableLayout();

        $response = new Fisma_AsyncResponse();
        
        try {
            
            $ape = Doctrine::getTable('AssessmentPlanEntry')->find($id);

            // If file upload is too large, then $_FILES will be empty (thanks for the helpful behavior, PHP!)
            if (0 == count($_FILES)) {
                throw new Fisma_Zend_Exception_User('File size is over the limit.');
            }
            
            // 'file' is the name of the file input element.
            if (!isset($_FILES['file'])) {
                throw new Fisma_Zend_Exception_User('You did not specify a file to upload.');
            }

            $ape->getArtifacts()->attach($_FILES['file'], $comment);
            
        } catch (Fisma_Zend_Exception_User $e) {
            $response->fail($e->getMessage());
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. File not uploaded.");
            }

            Fisma::getLogInstance($this->_me)->err($e->getMessage() . "\n" . $e->getTraceAsString());
        }
        
        $this->view->response = json_encode($response);
        
        if ($response->success) {
            $this->view->priorityMessenger('Artifact uploaded successfully', 'notice');
        }
    }
    
    /**
     * Override parent implementation to automatically generate findings for Other Than Satisfied assessments.
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return integer ID of the object saved. 
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        $oldResult = is_null($subject) ? null : $subject->result;
        $newResult = $form->getValue('result');

        $id = parent::saveValue($form, $subject);

        if ($newResult == 'Other Than Satisfied' && $oldResult != $newResult) {
            if (is_null($subject)) {
                $subject = Doctrine::getTable('AssessmentPlanEntry')->find($id);
            }
            $finding = new Finding();
            $sasca = $subject->SaSecurityControlAggregate;
            if ($sasca instanceof SaSecurityControl) {
                $finding->ResponsibleOrganization = $sasca->SecurityAuthorization->Organization;
                $finding->SecurityControl = $sasca->SecurityControl;
            } else if ($sasca instanceof SaSecurityControlEnhancement) {
                $finding->ResponsibleOrganization = $sasca->SaSecurityControl->SecurityAuthorization->Organization;
                $finding->SecurityControl = $sasca->SaSecurityControl->SecurityControl;
            } else {
                throw new Fisma_Zend_Exception('Unknown SaSecurityControlAggregate type: ' . get_class($subject));
            }
            $finding->discoveredDate = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE);
            $finding->CreatedBy = $this->_me;
            $finding->Source = Doctrine::getTable('Source')->findOneByNickname('C&A');
            $finding->save();
            $subject->Finding = $finding;
            $subject->save();
            $this->view->priorityMessenger('Created Finding for Other Than Satisfied Assessment.', 'info');
        }

        return $id;
    }
}
