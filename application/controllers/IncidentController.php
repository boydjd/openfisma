<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nathan Harris <nathan.harris@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The incident controller is used for searching, displaying, and updating
 * incidents.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class IncidentController extends SecurityController
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     */
    protected $_modelName = 'Incident';

    /**
     * Timezones
     * 
     * @todo this doesn't belong here
     */
    private $_timezones = array(
        ''     =>   '',
        'AST'  =>   'Atlantic Standard Time',
        'ADT'  =>   'Atlantic Daylight Time',
        'EST'  =>   'Eastern Standard Time',
        'EDT'  =>   'Eastern Daylight Time',
        'CST'  =>   'Central Standard Time',
        'CDT'  =>   'Central Daylight Time',
        'MST'  =>   'Mountain Standard Time',
        'MDT'  =>   'Mountain Daylight Time',
        'PST'  =>   'Pacific Standard Time',
        'PDT'  =>   'Pacific Daylight Time',
        'AKST' =>   'Alaska Standard Time',
        'AKDT' =>   'Alaska Daylight Time',
        'HAST' =>   'Hawaii-Aleutian Standard Time',
        'HADT' =>   'Hawaii-Aleutian Daylight Time'
    );
    
    /**
     * Set up JSON context switch
     */
    public function init()
    {
        parent::init();
        
        $this->_paging['count'] = 10;
        $this->_paging['startIndex'] = 0;
    }

    /**
     * A list of the separate parts of the incident report form, in order
     * 
     * @var array
     */
    private $_formParts = array(
        array('name' => 'incident0Instructions', 'title' => 'Instructions'),
        array('name' => 'incident1Contact', 'title' => 'Contact Information'),
        array('name' => 'incident2Basic', 'title' => 'Incident Details'),
        array('name' => 'incident3Host', 'title' => 'Affected Asset'),
        array('name' => 'incident4PiiQuestion', 'title' => 'Was PII Involved?'),
        array('name' => 'incident5PiiDetails', 'title' => 'PII Details'),
        array('name' => 'incident6Shipping', 'title' => 'Shipment Details')
    );
    
   /**
     * preDispatch() - invoked before each Actions
     */
    function preDispatch()
    {        
        if (in_array($this->_request->action, array('totalstatus','totalcategory'))) {

            $contextSwitch = $this->_helper->getHelper('contextSwitch');
            // Headers Required for IE+SSL (see bug #2039290) to stream XML
            $contextSwitch->addHeader('xml', 'Pragma', 'private')
                          ->addHeader('xml', 'Cache-Control', 'private')
                          ->addActionContext('totalstatus', 'xml')
                          ->addActionContext('totalcategory', 'xml')
                          ->initContext();
        }
    }

   /**
     * statistics per status 
     */
    public function totalstatusAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Incident');
        
        $arrTotal = array (
                        'new'      => 0,
                        'open'     => 0,
                        'resolved' => 0,
                        'rejected' => 0,
                    );

        $q = Doctrine_Query::create() 
             ->select('count(*) as count, i.status ')
             ->from('Incident i')
             ->groupBy('i.status');       

        $data = $q->execute()->toArray();
        foreach ($data as $key => $item) {
            if (isset($arrTotal[$item['status']])) {
                $arrTotal[$item['status']] = $item['count'];
            }
        }

        $this->view->summary = $arrTotal;
    }

   /**
     * statistics per status 
     * 
     * @todo this has horrendous performance
     */
    public function totalcategoryAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Incident');
        
        $arrTotal = array (
                        'CAT0'      => 0,
                        'CAT1'      => 0,
                        'CAT2'      => 0,
                        'CAT3'      => 0,
                        'CAT4'      => 0,
                        'CAT5'      => 0,
                        'CAT6'      => 0,
                    );

        $cats = $this->_getCategoriesArr();

        foreach ($cats as $cat => $ids) {
            $q = Doctrine_Query::create() 
                 ->select('count(*) as count')
                 ->from('Incident i')
                 ->whereIn('i.categoryId', $ids)
                 ->andWhere('i.status <> ?', array('closed'));

            $data = $q->execute()->toArray();
            $arrTotal[$cat] = $data[0]['count'];
        }

        $this->view->summary = $arrTotal;
    }

    /**
     * Handles the process of creating a new incident report. 
     * 
     * This is organized like a wizard which has several, successive screens to make the process simpler for 
     * the user.
     */
    public function reportAction() 
    {
        // Unauthenticated users see a different layout that doesn't have a menubar
        if (!User::currentUser()) {
            $this->_helper->layout->setLayout('anonymous');
        }
        
        // Fetch the incident report draft from the session or create it if necessary
        $session = Fisma::getSession();
        if (isset($session->irDraft)) {
            $incident = unserialize($session->irDraft);
        } else {
            $incident = new Incident();
        }

        // Save the current form into the Incident and save the incident into the sesion
        $incident->merge($this->getRequest()->getPost());
        $session->irDraft = serialize($incident);

        // Get the current step of the process, defaults to zero
        $step = $this->getRequest()->getParam('step');
        if (is_null($step)) {
            $step = 0;
        } else {
            // The user can move forwards or backwards
            if ($this->getRequest()->getParam('forwards')) {
                $step++;
            } elseif ($this->getRequest()->getParam('backwards')) {
                $step--;
            } elseif ($this->getRequest()->getParam('cancel')) {
                $this->_forward('cancel-report', 'Incident');
                return;
            } else {
                throw new Fisma_Exception('User must move forwards, backwards, or cancel');
            }
        }
        if ($step < 0) {
            throw new Fisma_Exception("Illegal step number: $step");
        }
        
        // Some business logic to determine if any steps can be skipped based on previous answers:
        // Authenticated users skip step 1 (which is reporter contact information)
        if (User::currentUser() && 1 == $step) {
            if ($this->getRequest()->getParam('forwards')) {
                $incident->ReportingUser = User::currentUser();
                $step++;
            } else {
                $step--;
            }
        }
        // If no PII after step 5, then skip to end
        if ($step >=5 && 'YES' != $incident->piiInvolved) {
            if ($this->getRequest()->getParam('forwards')) {
                $step = count($this->_formParts);
            } else {
                $step = 4;
            }
        } elseif ($step >= 6 && 'YES' != $incident->piiShipment) {
            if ($this->getRequest()->getParam('forwards')) {
                $step = count($this->_formParts);
            } else {
                $step = 5;
            }            
        }
        
        // Load the form part corresponding to this step
        if ($step < count($this->_formParts)) {
            $formPart = $this->getFormPart($step);            
        } else {
            $this->_forward('review-report', 'Incident');
            return;
        }
        
        // Authenticated users and unauthenticated users have different form actions
        if (User::currentUser()) {
            $formPart->setAction("/panel/incident/sub/report/step/$step");
        } else {
            $formPart->setAction("/incident/report/step/$step");
        }
        
        // Use the validator to load the incident data into the form. Notice that there aren't actually any
        // validators which could fail here.
        $formPart->isValid($incident->toArray());

        // Render the current step
        $this->view->assign('formPart', $formPart);
        $this->view->assign('stepNumber', $step);
        $this->view->assign('stepTitle', $this->_formParts[$step]['title']);

        $this->render('report');
    }

    /**
     * Loads the specified part of the incident report form
     *
     * @param int $step The step number
     * @return Zend_Form
     */
    public function getFormPart($step)
    {
        $formPart = Fisma_Form_Manager::loadForm($this->_formParts[$step]['name']);

        // Add buttons to the form
        $cancelButton = new Fisma_Yui_Form_Button_Submit(
            'cancel', 
            array(
                'label' => 'Cancel Report', 
                'imageSrc' => '/images/del.png',
            )
        );
        $formPart->addElement($cancelButton);
        if ($step > 0) {
            $backwardButton = new Fisma_Yui_Form_Button_Submit(
                'backwards', 
                array(
                    'label' => 'Go Back', 
                    'imageSrc' => '/images/left_arrow.png',
                )
            );
            $formPart->addElement($backwardButton);
        }
        $forwardButton = new Fisma_Yui_Form_Button_Submit(
            'forwards', 
            array(
                'label' => 'Continue', 
                'imageSrc' => '/images/right_arrow.png',
            )
        );
        $formPart->addElement($forwardButton);

        // Assign decorators
        $formPart->setDisplayGroupDecorators(
            array(
                new Zend_Form_Decorator_FormElements(),
                new Fisma_Form_CreateIncidentDecorator()
            )
        );
        $formPart->setElementDecorators(array(new Fisma_Form_CreateIncidentDecorator()));

        // Each step has some specific data that needs to be set up
        switch ($step) {
            case 1:
                // setting up state dropdown
                $formPart->getElement('reporterState')->addMultiOptions(array(0 => '--select--'));
                foreach ($this->_getStates() as $key => $val) {
                    $formPart->getElement('reporterState')->addMultiOptions(array($key => $val));
                }            
                break;
            case 2:    
                // Decorators for the timestamp
                $timestamp = $formPart->getElement('incidentDate');
                $timestamp->clearDecorators();
                $timestamp->addDecorator('ViewScript', array('viewScript'=>'datepicker.phtml'));
                $timestamp->addDecorator(new Fisma_Form_CreateIncidentDecorator);
                $tz = $formPart->getElement('incidentTimezone');
                $tz->addMultiOptions($this->_timezones);
                break;
            case 3:
                foreach ($this->_getOS() as $key => $os) {
                    $formPart->getElement('hostOs')
                             ->addMultiOptions(array($key => $os));
                }
                break;
            case 4:
                $this->_createBoolean($formPart, array('piiInvolved'));
                break;
            case 5:
                $this->_createBoolean(
                    $formPart, 
                    array(
                        'piiMobileMedia', 
                        'piiEncrypted', 
                        'piiAuthoritiesContacted', 
                        'piiPoliceReport',
                        'piiIndividualsNotified',
                        'piiShipment'
                    )
                );
                $formPart->getElement('piiMobileMediaType')->addMultiOptions(array(0 => '--select--'));
                foreach ($this->_getMobileMedia() as $key => $mm) {
                    $formPart->getElement('piiMobileMediaType')
                             ->addMultiOptions(array($key => $mm));
                }
                break;
            case 6:
                $this->_createBoolean($formPart, array('piiShipmentSenderContacted'));
                break;
        }

        return $formPart;
    }

    /**
     * Loads all form parts into a single form which can be rendered into a single page
     * 
     * @return Zend_Form
     */
    public function getForm()
    {
        $form = new Fisma_Form();
        
        // Load all form parts and append each one to the main form
        $formParts = array_keys($this->_formParts);
        foreach ($formParts as $part) {
            // The first form only contains instructions... so skip it
            if (0 == $part) {
                continue;
            }
         
            // For remaining form parts, load them and remove the navigational buttons and instructions
            $subform = $this->getFormPart($part);
            $subform->removeElement('cancel');
            $subform->removeElement('backwards');
            $subform->removeElement('forwards');
            $subform->removeElement('instructions');
            
            $form->addSubForm($subform, $this->_formParts[$part]['name']);
        }
        
        // Add submit/reset/cancel buttons
        $resetButton = new Fisma_Yui_Form_Button_Reset(
            'reset', 
            array(
                'label' => 'Reset'
            )
        );
        $form->addElement($resetButton);

        $saveButton = new Fisma_Yui_Form_Button_Submit(
            'save', 
            array(
                'label' => 'Save'
            )
        );
        $form->addElement($saveButton);

        // Setup decorators
        $form->setSubFormDecorators(array(new Zend_Form_Decorator_FormElements()));
        $form->setElementDecorators(array(new Fisma_Form_FismaDecorator()));

        return $form;
    }

    /**
     * Lets a user review the incident report in its entirety before submitting it.
     */
    public function reviewReportAction() 
    {
        // Fetch the incident report draft from the session
        $session = Fisma::getSession();
        if (isset($session->irDraft)) {
            $incident = unserialize($session->irDraft);
        } else {
            throw new Fisma_Exception('No incident report found in session');
        }
        
        // Load the view with all of the non-empty values that the user provided
        $incidentReport = $incident->toArray();
        $incidentReview = array();
        $incidentTable = Doctrine::getTable('Incident');
        foreach ($incidentReport as $key => &$value) {
            $cleanValue = trim(strip_tags($value));
            if (!empty($cleanValue)) {
                $columnDef = $incidentTable->getDefinitionOf($key);
                if ('boolean' == $columnDef['type']) {
                    $value = ($value == 1) ? 'YES' : 'NO';
                }
                if ($columnDef) {
                    $logicalName = stripslashes($columnDef['extra']['logicalName']);
                    $incidentReview[$logicalName] = stripslashes($value);
                } else {
                    throw new Fisma_Exception("Column ($key) does not have a logical name");
                }
            }
        }
        
        $this->view->incidentReview = $incidentReview;
        $this->view->step = count($this->_formParts);
        $this->view->actionUrlBase = User::currentUser() 
                                   ? '/panel/incident/sub'
                                   : '/incident';
    }

    /**
     * Inserts an incident record and forwards to the success page
     *
     * @return string the rendered page
     */
    public function saveReportAction() 
    {
        // Unauthenticated users see a different layout that doesn't have a menubar
        if (!User::currentUser()) {
            $this->_helper->layout->setLayout('anonymous');
        }
        
        // Fetch the incident report draft from the session
        $session = Fisma::getSession();
        if (isset($session->irDraft)) {
            $incident = unserialize($session->irDraft);
        } else {
            throw new Fisma_Exception('No incident report found in session');
        }

        // Set the reporting user and assign the IRCs as default actors
        if (User::currentUser()) {
            $incident->ReportingUser = User::currentUser();
        }
        $coordinators = $this->_getIrcs();
        $incident->link('Actors', $coordinators);
        $incident->save();

        // Send an email
        foreach ($coordinators as $coordinator) {
            $mail = new Fisma_Mail();
            $mail->IRReport($coordinator, $incident->id);
        }
        
        // Clear out serialized incident object
        unset($session->irDraft);
    }
    
    /**
     * Remove the serialized incident object from the session object.
     */
    public function cancelReportAction()
    {
        // Unauthenticated users see a different layout that doesn't have a menubar
        if (!User::currentUser()) {
            $this->_helper->layout->setLayout('anonymous');
        }

        $session = Fisma::getSession();
        
        if (isset($session->irDraft)) {
            unset($session->irDraft);
        }
    }

    /**
     * Displays the incident search page
     *
     * @return string the rendered page
     */
    public function listAction() 
    {
        $this->searchboxAction();
        
        $value = trim($this->_request->getParam('keywords'));
        empty($value) ? $link = '' : $link = '/keywords/' . $value;
        
        $this->view->assign('pageInfo', $this->_paging);
        $this->view->assign('link', $link);
        $this->view->allIncidentsUrl = $link
                                     . '/status/all/sortby/reportTs/order/asc/startIndex/0/count/'
                                     . $this->_paging['count'];
        
        $status = ($this->_request->getParam('status')) ? $this->_request->getParam('status') : 'new';
        $this->view->assign('status', $status);

        $this->view->assign('keywords', $this->_request->getParam('keywords'));

        $this->render('list');
    }

    public function searchboxAction() 
    {
        $status = ($this->_request->getParam('status')) ? $this->_request->getParam('status') : 'new';
        $this->view->assign('status', $status);
        
        $this->view->assign('startDt', $this->_request->getParam('startDt'));
        
        $this->view->assign('endDt', $this->_request->getParam('endDt'));
        
        $this->view->assign('keywords', $this->_request->getParam('keywords'));
    
        $this->render('searchbox');
    }

    /**
     * Displays the data related to a particular incident - will be called by ajax on all the incident interfaces
     *
     * @return string the rendered page
     */
    public function incidentdataAction() 
    {
        $this->_helper->layout->disableLayout();
        $incidentId = $this->_request->getParam('id');
        $this->_assertCurrentUserCanViewIncident($incidentId);
        $this->view->assign('id', $incidentId);
       
        $this->view->assign('cloneId', $this->_getClone($incidentId));

        $closed = $this->_request->getParam('closed');
        $this->view->assign('closed', $closed);

        $incident = Doctrine::getTable('Incident')->find($incidentId);
        
        $association = $this->_getAssociation($incidentId);
        $this->view->assign('association', $association);

        $this->view->assign('incident', $incident);
    }

    /**
     * Displays information for editing or viewing a particular incident
     *
     * @return string the rendered page
     */
    public function viewAction() 
    {
        $id = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);
        
        if (!$incident) {
            throw new Fisma_Exception("Invalid incident ID ($id)");
        }
        
        Fisma_Acl::requirePrivilegeForObject('read', $incident);
        
        $this->view->id = $id;
        $this->view->incident = $incident;
        
        $artifactCount = $incident->getArtifacts()->count();

        // Create tab view
        $tabView = new Fisma_Yui_TabView('SystemView', $id);

        $tabView->addTab("Incident #$id", "/incident/incident/id/$id");
        $tabView->addTab('Workflow', "/incident/workflow/id/$id");
        $tabView->addTab('Actors & Observers', "/incident/actors/id/$id");
        $tabView->addTab('Comments', "/incident/comments/id/$id");
        $tabView->addTab("Artifacts ($artifactCount)", "/incident/artifacts/id/$id");
        $tabView->addTab('Audit Log', "/incident/audit-log/id/$id");

        $this->view->tabView = $tabView;
    }
    
    /**
     * Display incident details
     * 
     * This is loaded into a tab view, so it has no layout
     */
    public function incidentAction()
    {        
        $this->_helper->layout->disableLayout();
        
        $id = $this->_request->getParam('id');
        
        $incident = Doctrine::getTable('Incident')->find($id);
        $this->view->incident = $incident;

        Fisma_Acl::requirePrivilegeForObject('read', $incident);
                
        // Create toolbar buttons and form action
        $this->view->discardChangesButton = new Fisma_Yui_Form_Button_Link(
            'discardChanges', 
            array(
                'value' => 'Discard Changes', 
                'href' => "/panel/incident/sub/view/id/$id"
            )
        );
        
        $this->view->saveChangesButton = new Fisma_Yui_Form_Button_Submit(
            'saveChanges',
            array(
                'label' => 'Save Changes'
            )
        );
    
        $this->view->formAction = "/incident/update/id/$id";
    }
    
    /**
     * Display the audit log for an incident
     */
    public function auditLogAction()
    {
        $id = $this->_request->getParam('id');
        
        $incident = Doctrine::getTable('Incident')->find($id);
        
        $logs = $incident->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);
        
        // Convert log messages from plain text to HTML
        foreach ($logs as &$log) {
            $log['o_message'] = $this->view->textToHtml($log['o_message']);
        }

        $this->view->logs = $logs;
    }
    
    /**
     * Display actors and observers and provide controls to add/remove actors and observers
     */
    public function actorsAction()
    {
        $this->_helper->layout->disableLayout();
        
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);

        $incident = Doctrine::getTable('Incident')->find($id);

        Fisma_Acl::requirePrivilegeForObject('read', $incident);
        
        // Get list of actors
        $actorQuery = Doctrine_Query::create()
                      ->select('i.id, a.id, a.username, a.nameFirst, a.nameLast')
                      ->from('Incident i')
                      ->innerJoin('i.Actors a')
                      ->where('i.id = ?', $id)
                      ->orderBy('a.username')
                      ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $actors = $actorQuery->execute();
        
        $this->view->assign('actors', $actors);

        // Get list of observers
        $observerQuery = Doctrine_Query::create()
                         ->select('i.id, o.id, o.username, o.nameFirst, o.nameLast')
                         ->from('Incident i')
                         ->innerJoin('i.Observers o')
                         ->where('i.id = ?', $id)
                         ->orderBy('o.username')
                         ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $observers = $observerQuery->execute();
                
        $this->view->assign('observers', $observers);
        
        // Create autocomplete for actors
        $this->view->actorAutocomplete = new Fisma_Yui_Form_AutoComplete(
            'actorAutocomplete',
            array(
                'resultsList' => 'users',
                'fields' => 'username',
                'xhr' => "/incident/get-eligible-users/id/$id",
                'hiddenField' => 'actorId',
                'queryPrepend' => '/query/',
                'containerId' => 'actorAutocompleteContainer'
            )
        );        

        $this->view->addActorButton = new Fisma_Yui_Form_Button_Submit(
            'addActor',
            array('label' => 'Add Actor')
        );
        
        // Create autocomplete for observers
        $this->view->observerAutocomplete = new Fisma_Yui_Form_AutoComplete(
            'observerAutocomplete',
            array(
                'resultsList' => 'users',
                'fields' => 'username',
                'xhr' => "/incident/get-eligible-users/id/$id",
                'hiddenField' => 'observerId',
                'queryPrepend' => '/query/',
                'containerId' => 'observerAutocompleteContainer'
            )
        );        

        $this->view->addObserverButton = new Fisma_Yui_Form_Button_Submit(
            'addObserver', 
            array('label' => 'Add Observer')
        );
    }
    
    /**
     * Add an actor or observer to the specified incident
     */
    public function addActorAction()
    {
        $incidentId = $this->getRequest()->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        Fisma_Acl::requirePrivilegeForObject('update', $incident);

        // userId is supplied by an autocomplete. If the user did not use autocomplete, show a helpful message
        $userId = $this->getRequest()->getParam('userId');
        
        if (empty($userId)) {
            $message = 'Type a few letters and the system will return a list of matching names. You must select from'
                     . ' that list of names.';
            $this->view->priorityMessenger($message, 'warning');
        }

        // Create the requested link
        $type = $this->getRequest()->getParam('type');

        if ('actor' == $type) {
            $incident->link('Actors', array($userId), true);
        } elseif ('observer' == $type) {
            $incident->link('Observers', array($userId), true);            
        }

        $this->_redirect("/panel/incident/sub/view/id/$incidentId");
    }
    
    /**
     * Remove an actor or observer from the specified incident
     */
    public function removeActorAction()
    {
        $incidentId = $this->getRequest()->getParam('incidentId');
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        Fisma_Acl::requirePrivilegeForObject('update', $incident);
        
        // Remove the specified link
        $userId = $this->getRequest()->getParam('userId');
        $type = $this->getRequest()->getParam('type');

        if ('actor' == $type) {
            $incident->unlink('Actors', array($userId), true);
        } elseif ('observer' == $type) {
            $incident->unlink('Observers', array($userId), true);            
        }

        $this->_redirect("/panel/incident/sub/view/id/$incidentId");
    }
    
    /**
     * Clones a closed incident, assigns it to the EDCIRC, and returns the user to the dashboard
     *
     * @return string the rendered dashboard page
     */
    public function cloneAction() 
    {
        $incidentId = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        // create a clone of the incident object
        $clone = $incident->copy(false);
        $clone->status = 'new';
        $clone->link('Actors', $this->_getIrcs());
        $clone->save();

        // add relationship to the cloned incident table
        $cloneLink = new IrClonedIncident();
        $cloneLink->origIncidentId  = $incidentId;
        $cloneLink->cloneIncidentId = $clone->id;
        $cloneLink->createdTs       = date('Y-d-m H:i:s');
        $cloneLink->userId          = User::currentUser()->id;
        $cloneLink->save();

        $this->view->priorityMessenger('The incident has been cloned.', 'notice');
        $this->_redirect('/panel/incident/sub/dashboard');
    }
        
    /**
     * Displays the incident workflow interface
     * 
     * This actually forwards to one of several different views and doesn't render anything itself
     * 
     * @return string the rendered page
     */
    public function workflowAction() 
    {
        $id = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);
        $this->view->incident = $incident;
        
        Fisma_Acl::requirePrivilegeForObject('read', $incident);

        switch ($incident->status) {
            case 'new':
                $this->_forward('classify-form');
                break;
            case 'open': // falls through
            case 'closed':
                $this->_forward('workflow-steps');
                break;                
        }
        
        $this->getHelper('viewRenderer')->setNoRender();        
    }

    /**
     * Displays the steps in the workflow associated with a particular incident
     */
    public function workflowStepsAction()
    {
        $id = $this->_request->getParam('id');
        $this->view->id = $id;
        
        $incident = Doctrine::getTable('Incident')->find($id);
        $this->view->incident = $incident;

        $stepsQuery = Doctrine_Query::create()
                      ->from('IrIncidentWorkflow iw')
                      ->where('iw.incidentId = ?', $id);

        $steps = $stepsQuery->execute();

        // Load related so we can efficiently access related records in the view
        $steps->loadRelated();
        
        $this->view->steps = $steps;
    }

    /**
     * Updates an incident object by marking a step as completed
     */
    public function completeWorkflowStepAction()
    {
        $id = $this->getRequest()->getParam('id');
        $this->view->id = $id;
        
        $incident = Doctrine::getTable('Incident')->find($id);
        Fisma_Acl::requirePrivilegeForObject('update', $incident);
        
        $comment = $this->getRequest()->getParam('comment');

        if (!empty($comment)) {
            $incident->completeStep($comment);

            foreach ($this->_getAssociatedUsers($incidentId) as $userId) {
                $mail = new Fisma_Mail();
                $mail->IRStep($userId, $incidentId, $workflowDescription, $workflowCompletedBy);
            }

            $message = 'Workflow step completed. ';
            if ('closed' == $incident->status) {
                $message .= 'All steps have been now completed and the incident has been marked as closed.';
            }
            
            $this->view->priorityMessenger($message, 'notice');
        } else {
            $this->view->priorityMessenger('Must provide a comment to complete a step.', 'warning');
        }
        
        $this->_redirect("/panel/incident/sub/view/id/$id");
    }

    /**
     * Show an interface to classify an incident
     */
    public function classifyFormAction()
    {
        $id = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);
        $this->view->incident = $incident;
        
        Fisma_Acl::requirePrivilegeForObject('classify', $incident);        
        
        $form = Fisma_Form_Manager::loadForm('incident_classify');

        $form->setAction("/incident/classify/id/$id");

        // Create the category menu
        $categoryElement = $form->getElement('categoryId');
        $categoryElement->addMultiOption(array('' => ''));
        foreach ($this->_getCategories() as $key => $value) {
            $categoryElement->addMultiOptions(array($key => $value));
        }
        $form->getElement('categoryId')->setValue($incident->categoryId);

        Fisma_Form_Manager::prepareForm($form);
        $this->view->assign('form', $form);
    }

    /**
     * Updates incident to show it has been opened and assigned to a category
     *
     * @return Zend_Form
     */
    public function classifyAction() 
    {
        $id = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);

        $comment = $this->_request->getParam('comment');

        if ($this->_request->getParam('reject') == 'reject') {                

            // Handle incident rejection
            $incident->reject($comment);
            $incident->save();
            
            $message = 'This incident has been marked as rejected.';
            $this->view->priorityMessenger($message, 'notice');
        } elseif ($this->_request->getParam('open') == 'open') {

            // Opening an incident requires a subcategory to be assigned
            $categoryId = $this->_request->getParam('categoryId');
            $category = Doctrine::getTable('IrSubCategory')->find($categoryId);
            
            if (!$category) {
                throw new Fisma_Exception("No subcategory with id ($categoryId) found.");
            }
            
            $incident->open($category, $comment);
            $incident->save();
                        
            // Assign privacy advocates and/or inspector general as actors if requested
            $actors = new Doctrine_Collection('User');

            if (1 == $this->_request->getParam('pa')) { 
                $actors->merge($this->_getPrivacyAdvocates());
            }

            if (1 == $this->_request->getParam('oig')) { 
                $actors->merge($this->_getOigUsers());
            }

            foreach ($actors as $actor) {
                $incident->link('Actors', array($actor->id), true);
            }            

            // Success message
            $message = 'This incident has been opened and a workflow has been assigned. ';
            $this->view->priorityMessenger($message, 'notice');
        }

        $this->_redirect("/panel/incident/sub/view/id/$id");
    }

    /**
     * Add a comment to a specified incident
     */
    public function addCommentAction()
    {
        $id = $this->getRequest()->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);

        Fisma_Acl::requirePrivilegeForObject('update', $incident);
        
        try {
            $comment = new IrComment();
            $comment->User = User::currentUser();
            $comment->Incident = $incident;
            $comment->comment = $this->getRequest()->getParam('comment');
            $comment->save();
        } catch (Fisma_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }
        
        $this->_redirect("/panel/incident/sub/view/id/$id");
    }

    /**
     * Displays the incident comment interface
     *
     * @return Zend_Form
     */
    function commentsAction() 
    {
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);
        $incident = Doctrine::getTable('Incident')->find($id);

        Fisma_Acl::requirePrivilegeForObject('read', $incident);

        $commentQuery = Doctrine_Query::create()
                        ->select('c.createdTs, c.comment, u.nameFirst, u.nameLast, u.username')
                        ->from('IrComment c')
                        ->innerJoin('c.User u')
                        ->where('c.incidentId = ?', $id)
                        ->orderBy('createdTs DESC')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $comments = $commentQuery->execute();

        $this->view->showAddCommentForm = Fisma_Acl::hasPrivilegeForObject('update', $incident);

        $this->view->assign('comments', $comments);
    }
    
    /**
     * Display file artifacts associated with an incident
     */
    public function artifactsAction()
    {
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);
        $incident = Doctrine::getTable('Incident')->find($id);

        Fisma_Acl::requirePrivilegeForObject('read', $incident);

        // Upload button
        $uploadPanelButton = new Fisma_Yui_Form_Button(
            'uploadPanelButton', 
            array(
                'label' => 'Upload New Artifact', 
                'onClickFunction' => 'Fisma.AttachArtifacts.showPanel',
                'onClickArgument' => array(
                    'id' => $id,
                    'server' => array(
                        'controller' => 'incident',
                        'action' => 'attach-artifact'                        
                    ),
                    'callback' => array(
                        'object' => 'Incident',
                        'method' => 'attachArtifactCallback'
                    )
                )
            )
        );

        if (!Fisma_Acl::hasPrivilegeForObject('update', $incident)) {
            $uploadPanelButton->readOnly = true;
        }
        
        $this->view->uploadPanelButton = $uploadPanelButton;

        // Artifact data
        $artifacts = $incident->getArtifacts()->fetch(Doctrine::HYDRATE_RECORD);

        $this->view->artifacts = $artifacts;
        
        $form = Fisma_Form_Manager::loadForm('upload_artifact');
                
        $this->view->form = $form;
        
    }
    
    /**
     * Attach a new artifact to this incident
     * 
     * This is called asychronously through the attach artifacts behavior. This is a bit hacky since it is invoked
     * by YUI's asynchronous file upload. This means the response is written to an iframe, so we can't render this view
     * as JSON.
     * 
     * Instead, we render an HTML view with the JSON-serialized response inside it.
     */
    public function attachArtifactAction()
    {
        $id = $this->getRequest()->getParam('id');
        $comment = $this->getRequest()->getParam('comment');
        
        $this->_helper->layout->disableLayout();

        $response = new Fisma_AsyncResponse();
        
        try {
            
            $incident = Doctrine::getTable('Incident')->find($id);

            Fisma_Acl::requirePrivilegeForObject('update', $incident);

            // If file upload is too large, then $_FILES will be empty (thanks for the helpful behavior, PHP!)
            if (0 == count($_FILES)) {
                throw new Fisma_Exception_User('File size is over the limit.');
            }
            
            // 'file' is the name of the file input element.
            if (!isset($_FILES['file'])) {
                throw new Fisma_Exception_User('You did not specify a file to upload.');
            }

            $incident->getArtifacts()->attach($_FILES['file'], $comment);
            
        } catch (Fisma_Exception_User $e) {
            $response->fail($e->getMessage());
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. File not uploaded.");
            }

            Fisma::getLogInstance()->err($e->getMessage());
        }
        
        $this->view->response = json_encode($response);
        
        if ($response->success) {
            $this->view->priorityMessenger('Artifact uploaded successfully', 'notice');
        }
    }
    
    /**
     * Download an artifact to the user's browser
     */
    public function downloadArtifactAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $incidentId = $this->getRequest()->getParam('id');
        $artifactId = $this->getRequest()->getParam('artifactId');
        
        // If user can view this artifact's incident, then they can download the artifact itself
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        Fisma_Acl::requirePrivilegeForObject('read', $incident);

        // Send artifact to browser
        $incident->getArtifacts()->find($artifactId)->send();
    }
    
    /**
     * list the incidents from the search, 
     * if search none, list all incidents
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Incident');
        $keywords = trim($this->_request->getParam('keywords'));

        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        $sortBy = $this->_request->getParam('sortby', 'reportTs');
        $order = $this->_request->getParam('order');
        $status = array($this->_request->getParam('status'));

        if ($status[0] == 'resolved') {
            $status[] = 'rejected';
        }  
       
        $organization = Doctrine::getTable('Incident');
        if (!in_array(strtolower($sortBy), $organization->getColumnNames())) {
            throw new Fisma_Exception('Invalid "sortBy" parameter');
        }
        
        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $q = $this->_getUserIncidentQuery()
             ->select('i.id, i.additionalInfo, i.status, i.piiInvolved, i.reportTs, c.name')
             ->leftJoin('i.Category c')
             ->orderBy("i.$sortBy $order")
             ->offset($this->_paging['startIndex'])
             ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        if ($status[0] != 'all') {
            $q->whereIn('i.status', $status);
        }

        if ($this->_request->getParam('startDt')) {
            $q->andWhere('i.reportTs > ?', $this->_request->getParam('startDt'));
        }
        
        if ($this->_request->getParam('endDt')) {
            $q->andWhere('i.reportTs < ?', $this->_request->getParam('endDt'));
        }
        
        if ($keywords) {
            // lucene search 
            $index = new Fisma_Index('Incident');
            $ids = $index->findIds($keywords);
            if (!empty($ids)) {
                $q->whereIn('id', $ids);
            }
        }

        $totalRecords = $q->count();
        $incidents = $q->execute();

        foreach ($incidents as $key => $val) {
            $incidents[$key]['category'] = $incidents[$key]['Category']['name'];

            if ('YES' == $incidents[$key]['piiInvolved']) {
                $incidents[$key]['piiInvolved'] = '&#10004;';
            } else {
                $incidents[$key]['piiInvolved'] = '&#10007;';
            }

        }
 
        $tableData = array('table' => array(
            'recordsReturned' => count($incidents),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $incidents,
        ));
        
        echo json_encode($tableData);
    }

    public function updateAction() 
    {
        $id = $this->_request->getParam('id');
        $this->_assertCurrentUserCanUpdateIncident($id);
        $incident = Doctrine::getTable('Incident')->find($id);

        if (!$incident) {
            throw new Exception_General("Invalid Incident ID");
        }

        try {
            $incident->merge($newValues = $this->getRequest()->getParam('incident'));
            $incident->save();
        } catch (Doctrine_Validator_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }

        $this->_redirect("/panel/incident/sub/view/id/$id");
    }

    /** 
     * Overriding Hooks
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } else {
            throw new Fisma_Exception('Invalid parameter expecting a Record model');
        }
        $values = $form->getValues();

        $values['sourceIp'] = $_SERVER['REMOTE_ADDR'];

        $values['reportTs'] = date('Y-m-d G:i:s');
        $values['reportTz'] = date('T');
        
        $values['status'] = 'new';

        if ($values['incidentHour'] && $values['incidentMinute'] && $values['incidentAmpm']) {
            if ($values['incidentAmpm'] == 'PM') {
                $values['incidentHour'] += 12;
            }
            $values['incidentTs'] .= " {$values['incidentHour']}:{$values['incidentMinute']}:00";
        }

        $subject->merge($values);
        $subject->save();

        $actor = new IrIncidentActor();

        $user = User::currentUser();
        $actor->userId = $user['id']; 
        
        $actor->incidentId = $subject['id'];
        $actor->save();
        
        $actor = new IrIncidentActor();

        $actor->incidentId = $subject['id'];
        $edcirc = $this->_getEDCIRC();
        $actor->userId = $edcirc;
        $actor->save();

        $mail = new Fisma_Mail();
        $mail->IRReport($edcirc, $subject['id']);
        
        $mail = new Fisma_Mail();
        $mail->IRReport($user['id'], $subject['id']);
        
        /* Not sure what is happening here.. if the method is called the dashboard renders twice */
        $this->_forward('dashboard');
    }

    /**
     * Assert that the current user is allowed to modify the specified incident.
     * 
     * Throws an exception if the current user is not allowed to modify the specified incident.
     * 
     * This is an expensive operation. DO NOT CALL IT IN A TIGHT LOOP.
     * 
     * @param int $incidentId
     */
    private function _assertCurrentUserCanUpdateIncident($incidentId)
    {
        // A quick check:
        Fisma_Acl::requirePrivilegeForClass('update', 'Incident');
        
        // Otherwise, check if this user is in the actors list
        $q = Doctrine_Query::create()
             ->from('Incident i')
             ->innerJoin('i.Actors a')
             ->where('i.id = ? AND a.id = ?', array($incidentId, User::currentUser()->id));
        $c = $q->count();
        
        if ($c < 1) {
            throw new Fisma_Exception_InvalidPrivilege('You are not allowed to edit this incident.');
        }
    }

    /**
     * Assert that the current user is allowed to view the specified incident.
     * 
     * Throws an exception if the current user is not allowed to view the specified incident.
     * 
     * This is an expensive operation. DO NOT CALL IT IN A TIGHT LOOP.
     * 
     * @param int $incidentId
     */
    private function _assertCurrentUserCanViewIncident($incidentId)
    {
        // A quick check:
        Fisma_Acl::requirePrivilegeForClass('read', 'Incident');
        
        // Otherwise, check if this user is in the observers list
        $q = Doctrine_Query::create()
             ->select('i.id')
             ->from('Incident i')
             ->leftJoin('i.Actors a')
             ->leftJoin('i.Observers o')
             ->where('i.id = ?', array($incidentId))
             ->andWhere('a.id = ? OR o.id = ?', array(User::currentUser()->id, User::currentUser()->id));
        $c = $q->count();
        
        if ($c < 1) {
            throw new Fisma_Exception_InvalidPrivilege('You are not allowed to view this incident.');
        }
    }

    private function _getStates() 
    {
        $states = array (
              'AL' => 'Alabama',
              'AK' => 'Alaska',
              'AZ' => 'Arizona',
              'AR' => 'Arkansas',
              'CA' => 'California',
              'CO' => 'Colorado',
              'CT' => 'Connecticut',
              'DE' => 'Delaware',
              'DC' => 'District of Columbia',
              'FL' => 'Florida',
              'GA' => 'Georgia',
              'HI' => 'Hawaii',
              'ID' => 'Idaho',
              'IL' => 'Illinois',
              'IN' => 'Indiana',
              'IA' => 'Iowa',
              'KS' => 'Kansas',
              'KY' => 'Kentucky',
              'LA' => 'Louisiana',
              'ME' => 'Maine',
              'MD' => 'Maryland',
              'MA' => 'Massachusetts',
              'MI' => 'Michigan',
              'MN' => 'Minnesota',
              'MS' => 'Mississippi',
              'MO' => 'Missouri',
              'MT' => 'Montana',
              'NE' => 'Nebraska',
              'NV' => 'Nevada',
              'NH' => 'New Hampshire',
              'NJ' => 'New Jersey',
              'NM' => 'New Mexico',
              'NY' => 'New York',
              'NC' => 'North Carolina',
              'ND' => 'North Dakota',
              'OH' => 'Ohio',
              'OK' => 'Oklahoma',
              'OR' => 'Oregon',
              'PW' => 'Palau',
              'PA' => 'Pennsylvania',
              'PR' => 'Puerto Rico',
              'RI' => 'Rhode Island',
              'SC' => 'South Carolina',
              'SD' => 'South Dakota',
              'TN' => 'Tennessee',
              'TX' => 'Texas',
              'UT' => 'Utah',
              'VT' => 'Vermont',
              'VI' => 'Virgin Island',
              'VA' => 'Virginia',
              'WA' => 'Washington',
              'WV' => 'West Virginia',
              'WI' => 'Wisconsin',
              'WY' => 'Wyoming'
        );

        return $states;
    }
    
    private function _getOS() 
    {
        return array(        '' => '',
                         'win7' => 'Windows 7',
                        'vista' => 'Vista',
                           'xp' => 'XP',
                        'macos' => 'Mac OSX',
                        'linux' => 'Linux',
                         'unix' => 'Unix'
                    );
    }
    
    private function _getMobileMedia() 
    {
        return array(    'laptop' => 'Laptop',
                           'disc' => 'CD/DVD',
                       'document' => 'Document',
                            'usb' => 'USB/Flash Drive',
                           'tape' => 'Magnetic Tape',
                          'other' => 'Other'
                    );
    }

    private function _createBoolean(&$form, $elements) 
    {
        foreach ($elements as $elementName) {
            $element = $form->getElement($elementName);
            $element->addMultiOptions(array('' => ' -- select -- ')); 
            $element->addMultiOptions(array('NO' => ' NO ')); 
            $element->addMultiOptions(array('YES' => ' YES ')); 
        }

        return 1;
    }

    /**
     * Returns all incident categories as a nested array, suitable for inserting into an HTML select
     * 
     * The outer array contains categories (CAT0, CAT1, etc.) and the inner array contain subcategories.
     * 
     * @return array
     */
    private function _getCategories() 
    {
        $q = Doctrine_Query::create()
             ->select('c.category, c.name, s.id, s.name')
             ->from('IrCategory c')
             ->innerJoin('c.SubCategories s')
             ->orderBy("c.category, s.name")
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $categories = $q->execute();
        
        // The categories need to be reformatted to use in a select menu. Zend Form Select has a weird format
        // for select options
        $selectOptions = array();
        $outerCategory = '';
        foreach ($categories as $category) {
            $categoryLabel = "{$category['c_category']} - {$category['c_name']}";
            $selectOptions[$categoryLabel][$category['s_id']] = $category['s_name'];
        }

        return $selectOptions;
    }
    
    private function _getCategoriesArr() 
    {
        $q = Doctrine_Query::create()
             ->select('c.id, c.category')
             ->from('IrCategory c')
             ->orderBy("c.category");

        $categories = $q->execute()->toArray();
        
        foreach ($categories as $key => $val) {
                $q2 = Doctrine_Query::create()
                     ->select('s.id, s.name')
                     ->from('IrSubCategory s')
                     ->where('s.categoryId = ?', $val['id'])
                     ->orderBy("s.name");

                $subCats = $q2->execute()->toArray();
                foreach ($subCats as $key2 => $val2) {
                    $retVal[$val['category']][] = $val2['id'];
                }
        }

        return $retVal;
    }

    private function _getUser($id) 
    {
        $q = Doctrine_Query::create()
             ->select('u.*')
             ->from('User u')
             ->where("u.id = ?", $id);

        $user = $q->execute();
        
        $user = $user->toArray();

        $q = Doctrine_Query::create()
             ->select('r.*')
             ->from('Role r')
             ->innerJoin('r.UserRole ur')
             ->where('ur.userId = ?', $user[0]['id']);   
        
        $role = $q->execute()->toArray();

        $user[0]['role'] = $role[0]['nickname'];

        return $user[0];
    }

    private function _getRole($id) 
    {
        $q = Doctrine_Query::create()
             ->select('r.name, r.nickname')
             ->from('Role r')
             ->where("r.id = ?", $id);

        $role = $q->execute();
        
        $role = $role->toArray();

        return $role[0];
    }

    /**
     * Get the user ids of all IRCs
     * 
     * @return array
     */
    private function _getIrcs()
    {
        $query = Doctrine_Query::create()
                 ->select('u.id')
                 ->from('User u')
                 ->innerJoin('u.Roles r')
                 ->where('r.nickname LIKE ?', 'IRC')
                 ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $ids = $query->execute();

        // Massage results
        $return = array();
        foreach ($ids as $id) {
            $return[] = $id['u_id'];
        }

        return $return;
    }

    /**
     * Return an array of users with the inspector general (OIG) role
     * 
     * @return Doctrine_Collection
     */
    private function _getOigUsers()
    {
        $oigQuery = Doctrine_Query::create()
                    ->from('User u')
                    ->innerJoin('u.Roles r')
                    ->where('r.nickname = ?', 'OIG');

        $oigUsers = $oigQuery->execute();
                
        return $oigUsers;
    }
    
    /**
     * Return an array of all users with the privacy advocate (PA) role
     * 
     * @return Doctrine_Collection
     */
    private function _getPrivacyAdvocates()
    {
        $paQuery = Doctrine_Query::create()
                   ->from('User u')
                   ->innerJoin('u.Roles r')
                   ->where('r.nickname = ?', 'PA');

        $paUsers = $paQuery->execute();
                
        return $paUsers;
    }

    /**
     * Returns a query which matches all of the users current incidents
     * 
     * @return Doctrine_Query
     */
    private function _getUserIncidentQuery()
    {
        $user = User::currentUser();
        
        // A user can be associated as an actor or observer, and so both tables need to be joined
        // here to get all of a user's incidents.
        $q = Doctrine_Query::create()
             ->select('i.id')
             ->from('Incident i')
             ->leftJoin('i.Actors a')
             ->leftJoin('i.Observers o')
             ->where('a.id = ? OR o.id = ?', array($user->id, $user->id));

        return $q;
    }

    private function _userIncidents() 
    {
        $user = User::currentUser();
        $incidents = array();
        
        $q = Doctrine_Query::create()
             ->select('i.incidentid')
             ->from('IrIncidentActor i')
             ->where('i.userid = ?', $user['id']);

        $actors = $q->execute()->toArray();
       
        foreach ($actors as $actor) {
            $incidents[] = $actor['incidentId'];
        }

        $q = Doctrine_Query::create()
             ->select('i.incidentid')
             ->from('IrIncidentObserver i')
             ->where('i.userid = ?', $user['id']);

        $observers = $q->execute()->toArray();

        foreach ($observers as $observer) {
            $incidents[] = $observer['incidentId'];
        }
    
        return $incidents;
    }

    private function _getAssociation($incidentId) 
    {
        $user = User::currentUser();
        
        $q = Doctrine_Query::create()
             ->select('count(*) as count')
             ->from('IrIncidentActor i')
             ->where('i.userid = ?', $user['id'])
             ->andWhere('i.incidentid = ?', $incidentId);

        $actor = $q->execute()->toArray();

        return ($actor[0]['count'] >= 1) ? 'actor' : 'viewer';
    }   

    private function _getClone($incidentId = null) 
    {
        $q = Doctrine_Query::create()
             ->select('i.origincidentid')
             ->from('IrClonedIncident i')
             ->where('i.cloneincidentid = ?', $incidentId);

        $data = $q->execute()->toArray();        
       
        if ($data) { 
            if ($data[0]['origIncidentId']) {
                return $data[0]['origIncidentId'];
            } 
        }
            
        return false;
    }

    private function _getAssociatedUsers($incidentId) 
    {
        $q = Doctrine_Query::create()
             ->select('u.userId')
             ->from('IrIncidentActor u')   
             ->where('u.incidentId = ?', $incidentId)
             ->groupBy('u.userId');

        $data = $q->execute()->toArray();
        
        $users = array();
        foreach ($data as $key => $val) {
            $users[] = $val['userId'];
        }
    
        $q = Doctrine_Query::create()
             ->select('u.userId')
             ->from('IrIncidentObserver u')   
             ->where('u.incidentId = ?', $incidentId)
             ->groupBy('u.userId');

        $data = $q->execute()->toArray();
        
        foreach ($data as $key => $val) {
            $users[] = $val['userId'];
        }    

        return $users;
    }

    /**
     * List users eligible to be an actor or observer
     * 
     * All users are eligible unless they are already an actor or observer for this incident.
     */
    public function getEligibleUsersAction()
    {
        $id = $this->getRequest()->getParam('id');
        $queryString = $this->getRequest()->getParam('query');
        
        $userQuery = Doctrine_Query::create()
                     ->select('u.username')
                     ->from('User u')
                     ->leftJoin('u.ActorIncidents ai')
                     ->leftJoin('u.ObserverIncidents oi')
                     ->where("u.username like ?", "%$queryString%")
                     ->andWhere('ai.id IS NULL OR ai.id <> ?', $id)
                     ->andWhere('oi.id IS NULL OR oi.id <> ?', $id)
                     ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $users = $userQuery->execute();

        $list = array('users' => array_values($users));

        return $this->_helper->json($list);
    }
}
