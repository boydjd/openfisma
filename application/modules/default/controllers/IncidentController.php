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
 * The incident controller is used for searching, displaying, and updating incidents.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class IncidentController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     */
    protected $_modelName = 'Incident';

    /**
     * Override parent in order to turn off default ACL checks.
     * 
     * Incident ACL checks are unusual and are performed within this controller, not the parent.
     */
    protected $_enforceAcl = false;

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
        array('name' => 'incident6Shipping', 'title' => 'Shipment Details'),
        array('name' => 'incident7Source', 'title' => 'Incident Source')
    );

    /**
     * Set up JSON context switch
     */
    public function init()
    {
        parent::init();
    }
   
   /**
     * preDispatch() - invoked before each Actions
     */
    function preDispatch()
    {        
        parent::preDispatch();

        $module = Doctrine::getTable('Module')->findOneByName('Incident Reporting');

        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }

        $this->_paging['startIndex'] = $this->getRequest()->getParam('startIndex', 0);
    }

    /**
     * Handles the process of creating a new incident report. 
     * 
     * This is organized like a wizard which has several, successive screens to make the process simpler for 
     * the user.
     * 
     * Notice that this method is allowed for unauthenticated users
     */
    public function reportAction() 
    {
        // Unauthenticated users see a different layout that doesn't have a menubar
        if (!$this->_me) {
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
        } elseif ($this->getRequest()->getParam('irReportCancel')) {
            $this->_redirect('/Incident/cancel-report');
            return;
        } elseif (!$incident->isValid()) {
            $this->view->priorityMessenger($incident->getErrorStackAsString(), 'warning');
        } else {
            // The user can move forwards or backwards
            if ($this->getRequest()->getParam('irReportForwards')) {
                $step++;
            } elseif ($this->getRequest()->getParam('irReportBackwards')) {
                $step--;
            } else {
                throw new Fisma_Zend_Exception('User must move forwards, backwards, or cancel');
            }
        }
        
        if ($step < 0) {
            throw new Fisma_Zend_Exception("Illegal step number: $step");
        }
        
        // Some business logic to determine if any steps can be skipped based on previous answers:
        // Authenticated users skip step 1 (which is reporter contact information)
        if ($this->_me && 1 == $step) {
            if ($this->getRequest()->getParam('irReportForwards')) {
                $incident->ReportingUser = $this->_me;
                $step++;
            } else {
                $step--;
            }
        }
        
        // Skip past PII sections if they are not applicable
        if ($step == 5 && 'YES' != $incident->piiInvolved) {
            if ($this->getRequest()->getParam('irReportForwards')) {
                $step = 7;
            } else {
                $step = 4;
            }
        } elseif ($step == 6 && 'YES' != $incident->piiShipment) {
            if ($this->getRequest()->getParam('irReportForwards')) {
                $step = 7;
            } else {
                $step = 5;
            }            
        }
        
        // Load the form part corresponding to this step
        if ($step < count($this->_formParts)) {
            $formPart = $this->getFormPart($step);            
        } else {
            $this->_redirect('/Incident/review-report');
            return;
        }
        
        // Authenticated users and unauthenticated users have different form actions
        if ($this->_me) {
            $formPart->setAction("/incident/report/step/$step");
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
    }

    /**
     * Loads the specified part of the incident report form
     *
     * @param int $step The step number
     * @return Zend_Form
     */
    public function getFormPart($step)
    {
        $formPart = Fisma_Zend_Form_Manager::loadForm($this->_formParts[$step]['name']);
        
        /**
         * Add buttons to the form. The continue button is added first so that it is the default submit button if
         * the user presses the "enter" key. The buttons are re-arranged into a more logical order on the screen with
         * CSS.
         */
        $forwardButton = new Fisma_Yui_Form_Button_Submit(
            'irReportForwards', 
            array(
                'label' => 'Continue', 
                'imageSrc' => $this->view->serverUrl("/images/right_arrow.png"),
            )
        );
        $formPart->addElement($forwardButton);

        $cancelButton = new Fisma_Yui_Form_Button_Submit(
            'irReportCancel', 
            array(
                'label' => 'Cancel Report', 
                'imageSrc' => $this->view->serverUrl("/images/del.png"),
            )
        );
        $formPart->addElement($cancelButton);

        if ($step > 0) {
            $backwardButton = new Fisma_Yui_Form_Button_Submit(
                'irReportBackwards', 
                array(
                    'label' => 'Go Back', 
                    'imageSrc' => $this->view->serverUrl("/images/left_arrow.png"),
                )
            );
            $formPart->addElement($backwardButton);
        }

        // Assign decorators
        $formPart->setDisplayGroupDecorators(
            array(
                new Zend_Form_Decorator_FormElements(),
                new Fisma_Zend_Form_Decorator_Incident_Create()
            )
        );
        $formPart->setElementDecorators(array(new Fisma_Zend_Form_Decorator_Incident_Create()));

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
                $timestamp->addDecorator(new Fisma_Zend_Form_Decorator_Incident_Create);
                $timestamp->addDecorator(new Fisma_Zend_Form_Decorator_Date);
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
    public function getIncidentForm()
    {
        $form = new Fisma_Zend_Form();
        
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
        $form->setElementDecorators(array(new Fisma_Zend_Form_Decorator()));

        return $form;
    }

    /**
     * Lets a user review the incident report in its entirety before submitting it.
     * 
     * This action is available to unauthenticated users.
     */
    public function reviewReportAction() 
    {
        // Fetch the incident report draft from the session
        $session = Fisma::getSession();
        if (isset($session->irDraft)) {
            $incident = unserialize($session->irDraft);
        } else {
            throw new Fisma_Zend_Exception('No incident report found in session');
        }
        
        // Load the view with all of the non-empty values that the user provided
        $incidentReport = $incident->toArray();
        $incidentReview = array();
        $richColumns = array();
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
                    // we need to know, in the view, which fields are rich-text
                    if (!empty($columnDef['extra']['purify'])) {
                        $richColumns[$logicalName] = $columnDef['extra']['purify'];
                    }
                } else {
                    throw new Fisma_Zend_Exception("Column ($key) does not have a logical name");
                }
            }
        }
        
        $this->view->incidentReview = $incidentReview;
        $this->view->richColumns = $richColumns;
        $this->view->step = count($this->_formParts);
        $this->view->actionUrlBase = $this->_me 
                                   ? '/incident'
                                   : '/incident';
    }

    /**
     * Inserts an incident record and forwards to the success page
     * 
     * This action is available to unauthenticated users
     *
     * @return string the rendered page
     */
    public function saveReportAction() 
    {
        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();
        
        // Unauthenticated users see a different layout that doesn't have a menubar
        if (!$this->_me) {
            $this->_helper->layout->setLayout('anonymous');
        }
        
        // Fetch the incident report draft from the session. If no incident report draft is in the session,
        // such as refresh this page, for anonymous user, it goes to incident report page. Otherwise, it goes
        // to incident list page.
        $session = Fisma::getSession();
        if (isset($session->irDraft)) {
            $incident = unserialize($session->irDraft);
        } else {
            if (!$this->_me) {
                $this->_redirect('/incident/report');     
            } else { 
                $this->_redirect('/incident/list');     
            }
        }

        $incident->save();
        
        // Set the reporting user
        if ($this->_me) {
            $incident->ReportingUser = $this->_me;
            
            $incident->save();

            // Add the reporting user as an actor
            $incidentActor = new IrIncidentUser();

            $incidentActor->userId = $this->_me->id;
            $incidentActor->incidentId = $incident->id;
            $incidentActor->accessType = 'ACTOR';

            $incidentActor->save();
        }

        $conn->commit();
        
        // Send emails to IRCs
        $coordinators = $this->_getIrcs();
        foreach ($coordinators as $coordinator) {
            $mail = new Fisma_Zend_Mail();
            $mail->IRReport($coordinator, $incident->id);
        }
        
        // Clear out serialized incident object
        unset($session->irDraft);
    }
    
    /**
     * Remove the serialized incident object from the session object.
     * 
     * This action is available to unauthenticated users
     */
    public function cancelReportAction()
    {
        // Unauthenticated users see a different layout that doesn't have a menubar
        if (!$this->_me) {
            $this->_helper->layout->setLayout('anonymous');
        }

        $session = Fisma::getSession();
        
        if (isset($session->irDraft)) {
            unset($session->irDraft);
        }
    }

    /**
     * Displays information for editing or viewing a particular incident
     *
     * @return string the rendered page
     */
    public function viewAction() 
    {
        $id = $this->_request->getParam('id');
        $incident = $this->_getSubject($id);

        $this->_assertCurrentUserCanViewIncident($id);
                
        $this->view->id = $id;
        $this->view->incident = $incident;

        // Put a span around the comment count so that it can be updated from Javascript
        $commentCount = '<span id=\'incidentCommentsCount\'>' . $incident->getComments()->count() . '</span>';
        
        $artifactCount = $incident->getArtifacts()->count();

        // Create tab view
        $tabView = new Fisma_Yui_TabView('SystemView', $id);

        $tabView->addTab("Incident #$id", "/incident/incident/id/$id");
        $tabView->addTab('Workflow', "/incident/workflow/id/$id");
        $tabView->addTab('Actors & Observers', "/incident/users/id/$id");
        $tabView->addTab("Comments ($commentCount)", "/incident/comments/id/$id");
        $tabView->addTab("Artifacts ($artifactCount)", "/incident/artifacts/id/$id");
        $tabView->addTab('Audit Log', "/incident/audit-log/id/$id");

        $this->view->tabView = $tabView;
        
        $this->view->toolbarButtons = $this->getToolbarButtons();
    }
    
    /**
     * Display incident details
     * 
     * This is loaded into a tab view, so it has no layout
     */
    public function incidentAction()
    {
        /** @todo move to ajax context */
        $this->_helper->layout->disableLayout();
        
        $id = $this->_request->getParam('id');
        
        $incidentQuery = Doctrine_Query::create()
                         ->from('Incident i')
                         ->leftJoin('i.Organization o')
                         ->leftJoin('i.Category category')
                         ->leftJoin('i.ReportingUser reporter')
                         ->where('i.id = ?', $id)
                         ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $results = $incidentQuery->execute();
        $incident = $results[0];

        $this->view->incident = $incident;

        $this->_assertCurrentUserCanViewIncident($id);
        
        $this->view->updateIncidentPrivilege = $this->_currentUserCanUpdateIncident($id);
        $this->view->lockIncidentPrivilege = $this->_acl->hasPrivilegeForClass('lock', 'Incident');

        // Create toolbar buttons and form action
        $this->view->discardChangesButton = new Fisma_Yui_Form_Button_Link(
            'discardChanges', 
            array(
                'value' => 'Discard Changes', 
                'href' => "/incident/view/id/$id"
            )
        );
        
        $this->view->saveChangesButton = new Fisma_Yui_Form_Button_Submit(
            'saveChanges',
            array(
                'label' => 'Save Changes'
            )
        );

        $this->view->unlockButton = new Fisma_Yui_Form_Button_Link(
            'unlock',
            array(
                'value' => 'Unlock Incident',
                'href' => "/incident/unlock/id/$id"
            )
        );

        $this->view->lockButton = new Fisma_Yui_Form_Button_Link(
            'lock',
            array(
                'value' => 'Lock Incident',
                'href' => "/incident/lock/id/$id"
            )
        );
    
        $this->view->formAction = "/incident/update/id/$id";

        $orgId = $incident['Organization']['id'];
        $organization = Doctrine::getTable('Organization')->find($orgId);
        
        // $organization will be false if an organization has not been selected yet
        if ($organization === false) {
            $this->view->userCanViewOrganization = false;
        } else {
            $this->view->userCanViewOrganization = $this->_acl->hasPrivilegeForObject('read', $organization);
        }
    }

    /**
     * Lock the incident 
     * 
     * The access control for these actions is handled inside the Lockable behavior
     * 
     * @return void
     */
    public function lockAction()
    {
        $id = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);
        $this->_acl->requirePrivilegeForObject('lock', $incident);
        $incident->isLocked = TRUE;
        $incident->save();
        $this->_redirect("/incident/view/id/$id");
    }

    /**
     * Unlock the incident 
     * 
     * The access control for these actions is handled inside the Lockable behavior
     * 
     * @return void
     */
    public function unlockAction()
    {
        $id = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);
        $this->_acl->requirePrivilegeForObject('lock', $incident);
        $incident->isLocked = FALSE;
        $incident->save();
        $this->_redirect("/incident/view/id/$id");
    }
    
    /**
     * Display the audit log for an incident
     */
    public function auditLogAction()
    {
        $id = $this->_request->getParam('id');
        
        $this->_assertCurrentUserCanViewIncident($id);

        /** @todo move to ajax context */
        $this->_helper->layout->disableLayout();

        $incident = Doctrine::getTable('Incident')->find($id);
        
        $logs = $incident->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);
        
        // Convert log messages from plain text to HTML
        foreach ($logs as &$log) {
            $log['o_message'] = $this->view->textToHtml($this->view->escape($log['o_message']));
        }

        $this->view->logs = $logs;
    }
    
    /**
     * Display users with actor or observer privileges and provide controls to add/remove actors and observers
     */
    public function usersAction()
    {
        $this->_helper->layout->disableLayout();
        
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);

        $this->_assertCurrentUserCanViewIncident($id);
        
        // Get list of actors
        $actorQuery = Doctrine_Query::create()
                      ->select('i.id, a.id, a.username, a.nameFirst, a.nameLast')
                      ->from('Incident i')
                      ->innerJoin('i.IrIncidentUser iu')
                      ->innerJoin('iu.User a')
                      ->where('i.id = ? AND iu.accessType = ?', array($id, 'ACTOR'))
                      ->orderBy('a.username')
                      ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $actors = $actorQuery->execute();

        $this->view->assign('actors', $actors);

        // Get list of observers
        $observerQuery = Doctrine_Query::create()
                         ->select('i.id, o.id, o.username, o.nameFirst, o.nameLast')
                         ->from('Incident i')
                         ->innerJoin('i.IrIncidentUser iu')
                         ->innerJoin('iu.User o')
                         ->where('i.id = ? AND iu.accessType = ?', array($id, 'OBSERVER'))
                         ->orderBy('o.username')
                         ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $observers = $observerQuery->execute();

        $this->view->assign('observers', $observers);
        $this->view->updateIncidentPrivilege = $this->_currentUserCanUpdateIncident($id);
        
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
     * Add a user as an actor or observer to the specified incident
     */
    public function addUserAction()
    {
        $incidentId = $this->getRequest()->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        $this->_assertCurrentUserCanUpdateIncident($incidentId);
        
        $type = $this->getRequest()->getParam('type');
        
        if (!in_array($type, array('actor', 'observer'))) {
            throw new Fisma_Zend_Exception("Invalid incident user type: '$type'");
        }

        // The user ID is passed as observerId or actorId depending on which type is being submitted
        if ('actor' == $type) {
            $userId = $this->getRequest()->getParam('actorId');
        } else {
            $userId = $this->getRequest()->getParam('observerId');
        }

        /*
         * User ID is supplied by an autocomplete. If the user did not use autocomplete, then check to see if the
         * username can be looked up.
         */         
        if (empty($userId)) {

            $username = ($type == 'actor') 
                      ? $this->getRequest()->getParam('actorAutocomplete')
                      : $this->getRequest()->getParam('observerAutocomplete');
            
            $user = Doctrine::getTable('User')->findOneByUsername($username);
            
            if (!$user) {
                $error = "No user exists with the username \"$username\"";
                $this->view->priorityMessenger($error, 'warning');
            } else {
                $userId = $user->id;
            }
        }

        /*
         * User ID may have been missing in the form submission, but found by looking up the username, so we need to
         * verify that userId is set before creating the link and sending the e-mail.
         */
        if (!empty($userId)) {
            // Create the requested link
            $incidentActor = new IrIncidentUser();

            $incidentActor->userId = $userId;
            $incidentActor->incidentId = $incidentId;
            $incidentActor->accessType = strtoupper($type);

            try {
                $incidentActor->save();
            } catch (Doctrine_Connection_Exception $e) {
                $portableCode = $e->getPortableCode();
                
                if (Doctrine::ERR_ALREADY_EXISTS == $portableCode) {
                    $message = 'A user cannot have both the actor and observer role for the same incident.';
                    $this->view->priorityMessenger($message, 'warning'); 
                } else {
                    throw $e;
                }
            }

            // Send e-mail
            $mail = new Fisma_Zend_Mail();
            $mail->IRAssign($userId, $incidentId);
        }
        
        $this->_redirect("/incident/view/id/$incidentId");
    }
    
    /**
     * Remove user's actor or observer privileges for the specified incident
     */
    public function removeUserAction()
    {
        $incidentId = $this->getRequest()->getParam('incidentId');
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        $this->_assertCurrentUserCanUpdateIncident($incidentId);
                
        // Remove the specified user from this incident
        $userId = $this->getRequest()->getParam('userId');

        $incident->unlink('Users', array($userId));
        $incident->save();

        $this->_redirect("/incident/view/id/$incidentId");
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
        
        /** @todo move to ajax context */
        $this->_helper->layout->disableLayout();

        $this->_assertCurrentUserCanViewIncident($id);
        
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
        
        $this->_assertCurrentUserCanViewIncident($id);
        
        $incident = Doctrine::getTable('Incident')->find($id, Doctrine::HYDRATE_ARRAY);
        $this->view->incident = $incident;

        $stepsQuery = Doctrine_Query::create()
                      ->from('IrIncidentWorkflow iw')
                      ->leftJoin('iw.User user')
                      ->leftJoin('iw.Role role')
                      ->where('iw.incidentId = ?', $id)
                      ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $steps = $stepsQuery->execute();

        $this->view->updateIncidentPrivilege = $this->_currentUserCanUpdateIncident($id);
        $this->view->steps = $steps;
    }

    /**
     * Updates an incident object by marking a step as completed
     */
    public function completeWorkflowStepAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $this->view->id = $id;
            
            $incident = Doctrine::getTable('Incident')->find($id);

            $this->_assertCurrentUserCanUpdateIncident($id);
            
            $comment = $this->getRequest()->getParam('comment');

            // Get reference to current step before marking it complete
            $currentStep = $incident->CurrentWorkflowStep;
            
            $incident->completeStep($comment);

            foreach ($this->_getAssociatedUsers($id) as $user) {
                $mail = new Fisma_Zend_Mail();
                $mail->IRStep($user['userId'], $id, $currentStep->name, $currentStep->User->username);
            }

            $message = 'Workflow step completed. ';
            if ('closed' == $incident->status) {
                $message .= 'All steps have been now completed and the incident has been marked as closed.';
            }
            
            $this->view->priorityMessenger($message, 'notice');
        } catch (Fisma_Zend_Exception_User $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        } catch (Fisma_Doctrine_Behavior_Lockable_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }
        $this->_redirect("/incident/view/id/$id");
    }

    /**
     * Show an interface to classify an incident
     */
    public function classifyFormAction()
    {
        $id = $this->_request->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);
        $this->view->incident = $incident;
        
        $this->_assertCurrentUserCanViewIncident($id);
        
        $this->view->classifyIncidentPrivilege = $this->_currentUserCanClassifyIncident($id);
        
        $form = Fisma_Zend_Form_Manager::loadForm('incident_classify');

        $form->setAction("/incident/classify/id/$id");

        // Create the category menu
        $categoryElement = $form->getElement('categoryId');
        $categoryElement->addMultiOption('', '');
        foreach ($this->_getCategories() as $key => $value) {
            $categoryElement->addMultiOptions(array($key => $value));
        }
        $form->getElement('categoryId')->setValue($incident->categoryId);

        Fisma_Zend_Form_Manager::prepareForm($form);
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

        $this->_acl->requirePrivilegeForObject('classify', $incident);

        $comment = $this->_request->getParam('comment');

        $conn = Doctrine_Manager::connection();
        $conn->beginTransaction();

        try {
            // Validate that comment is not empty
            if ('' == trim($comment)) {
                throw new Fisma_Zend_Exception_User('You must provide a comment');
            }

            if ($this->_request->getParam('reject') == 'reject') {                

                // Handle incident rejection
                $incident->reject($comment);
                $incident->save();
            
                $message = 'This incident has been marked as rejected.';
                $this->view->priorityMessenger($message, 'notice');
            } elseif ($this->_request->getParam('open') == 'open') {

                // Opening an incident requires a subcategory to be assigned
                $categoryId = $this->_request->getParam('categoryId');
            
                if (empty($categoryId)) {
                    throw new Fisma_Zend_Exception_User('You must select a category.');
                }
            
                $category = Doctrine::getTable('IrSubCategory')->find($categoryId);

                if (!$category) {
                    throw new Fisma_Zend_Exception("No subcategory with id ($categoryId) found.");
                }
            
                $incident->open($category);
                $incident->save();
                        
                // Assign privacy advocates and/or inspector general as actors if requested
                $users = new Doctrine_Collection('User');

                if (1 == $this->_request->getParam('pa')) { 
                    $users->merge($this->_getPrivacyAdvocates());
                }

                if (1 == $this->_request->getParam('oig')) { 
                    $users->merge($this->_getOigUsers());
                }

                foreach ($users as $user) {
                    $incidentActor = new IrIncidentUser();
                    
                    $incidentActor->userId = $user->id;
                    $incidentActor->incidentId = $incident->id;
                    $incidentActor->accessType = 'ACTOR';

                    $incidentActor->replace();
                }            

                // Success message
                $message = 'This incident has been opened and a workflow has been assigned. ';

                // Get reference to current step before marking it complete
                $currentStep = $incident->CurrentWorkflowStep;
                
                $incident->completeStep($comment);
                
                if (isset($currentStep)) {
                    foreach ($this->_getAssociatedUsers($id) as $user) {
                        $mail = new Fisma_Zend_Mail();
                        $mail->IRStep($user['userId'], $id, $currentStep->name, $this->_me->username);
                    }
                }
                $this->view->priorityMessenger($message, 'notice');
            }
            
            $conn->commit();
        } catch (Fisma_Zend_Exception_User $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }

        $this->_redirect("/incident/view/id/$id");
    }

    /**
     * Add a comment to a specified incident
     */
    public function addCommentAction()
    {
        $id = $this->getRequest()->getParam('id');
        $incident = Doctrine::getTable('Incident')->find($id);

        $this->_assertCurrentUserCanUpdateIncident($id);
        
        $comment = $this->getRequest()->getParam('comment');
        
        if ('' != trim(strip_tags($comment))) {
            $incident->getComments()->addComment($comment);
        } else {
            $this->view->priorityMessenger('Comment field is blank', 'warning');
        }
        
        $this->_redirect("/incident/view/id/$id");
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

        $this->_assertCurrentUserCanViewIncident($id);

        /** @todo move to ajax context */
        $this->_helper->layout->disableLayout();

        $comments = $incident->getComments()->fetch(Doctrine::HYDRATE_ARRAY);

        $commentButton = new Fisma_Yui_Form_Button(
            'commentButton', 
            array(
                'label' => 'Add Comment', 
                'onClickFunction' => 'Fisma.Commentable.showPanel',
                'onClickArgument' => array(
                    'id' => $id,
                    'type' => 'Incident',
                    'callback' => array(
                        'object' => 'Incident',
                        'method' => 'commentCallback'
                    )
                )
            )
        );

        if (!$this->_currentUserCanUpdateIncident($id)) {
            $commentButton->readOnly = true;
        }

        $this->view->commentButton = $commentButton;
        $this->view->comments = $comments;
    }
    
    /**
     * Display file artifacts associated with an incident
     */
    public function artifactsAction()
    {
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);
        $incident = Doctrine::getTable('Incident')->find($id);

        /** @todo move to ajax context */
        $this->_helper->layout->disableLayout();

        $this->_assertCurrentUserCanViewIncident($id);

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

        if (!$this->_currentUserCanUpdateIncident($id)) {
            $uploadPanelButton->readOnly = true;
        }
        
        $this->view->uploadPanelButton = $uploadPanelButton;

        /**
         * Get artifact data as Doctrine Collection. Loop over to get icon URLs and file size, then convert to array
         * for view binding.
         */
        $artifactCollection = $incident->getArtifacts()->fetch(Doctrine::HYDRATE_RECORD);;
        $artifacts = array();
        
        foreach ($artifactCollection as $artifact) {
            $artifactArray = $artifact->toArray();
            $artifactArray['iconUrl'] = $artifact->getIconUrl();
            $artifactArray['fileSize'] = $artifact->getFileSize();
            
            $artifacts[] = $artifactArray;
        }

        $this->view->artifacts = $artifacts;
        
        $this->view->form = Fisma_Zend_Form_Manager::loadForm('upload_artifact');
        
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

            $this->_assertCurrentUserCanUpdateIncident($id);

            // If file upload is too large, then $_FILES will be empty (thanks for the helpful behavior, PHP!)
            if (0 == count($_FILES)) {
                throw new Fisma_Zend_Exception_User('File size is over the limit.');
            }
            
            // 'file' is the name of the file input element.
            if (!isset($_FILES['file'])) {
                throw new Fisma_Zend_Exception_User('You did not specify a file to upload.');
            }

            $incident->getArtifacts()->attach($_FILES['file'], $comment);
            
        } catch (Fisma_Zend_Exception_User $e) {
            $response->fail($e->getMessage());
        } catch (Exception $e) {
            if (Fisma::debug()) {
                $response->fail("Failure (debug mode): " . $e->getMessage());
            } else {
                $response->fail("Internal system error. File not uploaded.");
            }

            $this->getInvokeArg('bootstrap')->getResource('log')->err($e->getMessage() . "\n" . $e->getTraceAsString());
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

        $this->_assertCurrentUserCanViewIncident($incidentId);

        // Send artifact to browser
        $incident->getArtifacts()->find($artifactId)->send();
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
            $newValues = $this->getRequest()->getParam('incident');
            if (!empty($newValues)) {
                $incident->merge($newValues);
                $incident->save();
            }
        } catch (Doctrine_Validator_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }

        $this->_redirect("/incident/view/id/$id");
    }

    /**
     * Check whether the current user can update the specified incident
     * 
     * This is an expensive operation. DO NOT CALL IT IN A TIGHT LOOP.
     * 
     * @param int $incidentId The ID of the incident
     * @return bool
     */
    public function _currentUserCanUpdateIncident($incidentId)
    {
        $userCanUpdate = false;
        $incident = Doctrine::getTable('Incident')->findOneById($incidentId);

        if (
            $this->_acl->hasPrivilegeForObject('update', $incident) && 
            ((!$incident->isLocked) || 
            ($incident->isLocked && $this->_acl->hasPrivilegeForObject('lock', $incident)))
        ) {
            $userCanUpdate = true;
        } else {
            // Check if this user is an actor
            $userId = $this->_me->id;
            $actorCount = Doctrine_Query::create()
                 ->from('Incident i')
                 ->innerJoin('i.IrIncidentUser iu')
                 ->innerJoin('iu.User u')
                 ->where('i.id = ? AND u.id = ? AND iu.accessType = ?', array($incidentId, $this->_me->id, 'ACTOR'))
                 ->count();
            
            if ($actorCount > 0) {
                $userCanUpdate = true;
            }
        }

        return $userCanUpdate;
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
        if (!$this->_currentUserCanUpdateIncident($incidentId)) {
            throw new Fisma_Zend_Exception_InvalidPrivilege('You are not allowed to edit this incident.');
        }
    }

    /**
     * Check whether the current user can view the specified incident
     * 
     * This is an expensive operation. DO NOT CALL IT IN A TIGHT LOOP.
     * 
     * @param int $incidentId The ID of the incident
     * @return bool
     */
    public function _currentUserCanViewIncident($incidentId) 
    {
        $userCanView = false;
        
        if (!$this->_acl->hasPrivilegeForClass('read', 'Incident')) {
            // Check if this user is an observer or actor
            $observerCount = Doctrine_Query::create()
                 ->select('i.id')
                 ->from('Incident i')
                 ->leftJoin('i.Users u')
                 ->where('i.id = ? AND u.id = ?', array($incidentId, $this->_me->id))
                 ->count();

            if ($observerCount > 0) {
                $userCanView = true;
            }
            
        } else {
            $userCanView = true;
        }
        
        return $userCanView;
    }

    /**
     * Check whether the current user can classify the specified incident 
     * 
     * @param int $incidentId The ID of the incident
     * @return boolean
     */
    private function _currentUserCanClassifyIncident($incidentId)
    {
        $incident = Doctrine::getTable('Incident')->findOneById($incidentId);

        if ($this->_acl->hasPrivilegeForObject('classify', $incident)) {
            if ($incident->isLocked) {
                if ($this->_acl->hasPrivilegeForObject('lock', $incident)) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
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
        if (!$this->_currentUserCanViewIncident($incidentId)) {
            throw new Fisma_Zend_Exception_InvalidPrivilege('You are not allowed to view this incident.');
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

    private function _getAssociatedUsers($incidentId) 
    {
        $incidentUsersQuery = Doctrine_Query::create()
                              ->select('u.userId')
                              ->from('IrIncidentUser u')   
                              ->where('u.incidentId = ?', $incidentId)
                              ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $incidentUsers = $incidentUsersQuery->execute();

        return $incidentUsers;
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
                     ->leftJoin('u.Incidents i')
                     ->where("u.username like ?", "%$queryString%")
                     ->andWhere('i.id IS NULL OR i.id <> ?', $id)
                     ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $users = $userQuery->execute();

        $list = array('users' => array_values($users));

        return $this->_helper->json($list);
    }
    
    /**
     * Replace the default "Create" button with a "Report Incident" button
     *
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons()
    {
        $buttons = parent::getToolbarButtons();

        unset($buttons['create']);

        $buttons['repot'] = new Fisma_Yui_Form_Button_Link(
            'toolbarReportIncidentButton',
            array(
                'value' => 'Report New Incident',
                'href' => $this->getBaseUrl() . '/report'
            )
        );

        return $buttons;
    }
}
