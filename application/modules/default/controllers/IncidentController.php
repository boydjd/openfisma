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
 * The incident controller is used for searching, displaying, and updating incidents.
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
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
     * Set up context switches
     */
    public function init()
    {
        parent::init();

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('add-user', 'json')
                      ->addActionContext('remove-user', 'json')
                      ->initContext();
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
     *
     * @GETAllowed
     */
    public function reportAction()
    {
        $subFormValid = true;

        // Unauthenticated users see a different layout that doesn't have a menubar
        if (!$this->_me) {
            $this->_helper->layout->setLayout('anonymous');
        }

        // Get the current step of the process, defaults to zero
        $step = $this->getRequest()->getParam('step');

        // Fetch the incident report draft from the session or create it if necessary
        $session = Fisma::getSession();
        if (isset($session->irDraft)) {
            $incident = unserialize($session->irDraft);
        } else {
            $incident = new Incident();
        }

        // Save the current form into the Incident and save the incident into the session
        if ($this->_request->isPost()) {
            if (!is_null($step) && $step != 0 && $step < 8) {
                $subForm = $this->getFormPart($step);

                // Add a customized error message to the "Describe the incident" field
                $descIncidentElement = $subForm->getElement('additionalInfo');
                if (!empty($descIncidentElement)) {
                    $descIncidentValidator = $descIncidentElement->getValidator('MceNotEmpty');
                    $descIncidentValidator->setMessage('You must enter a description of the incident to continue.');
                }

                $subFormValid = $subForm->isValid($this->_request->getPost());
                $incident->merge($subForm->getValues());
                $session->irDraft = serialize($incident);
            }
        }

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

                // Only validate the form when moving forward
                if (!$subFormValid) {
                    $errorString = Fisma_Zend_Form_Manager::getErrors($subForm);
                    $this->view->priorityMessenger("Unable to create the incident:<br>$errorString", 'warning');
                } else {
                    $step++;
                }
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
        if (($step == 5 || $step == 6) && 'YES' != $incident->piiInvolved) {
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

        // Initialize incidentDate with current system date
        if (empty($incident->incidentDate)) {
            $incident->incidentDate = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE);
        }

        // Initialize the default selection of piiInvolved with 'NO' option.
        if (empty($incident->piiInvolved)) {
            $incident->piiInvolved = 'NO';
        }

        // Initialize incidentTime with current system time
        if (empty($incident->incidentTime)) {

            // The value of selection option should be multiples of 5
            $minute = (int) Zend_Date::now()->get(Zend_Date::MINUTE_SHORT);
            $minute = $minute - $minute % 5;
            $time = Zend_Date::now()->setMinute($minute)
                                    ->setSecond(0)
                                    ->get(Fisma_Date::FORMAT_TIME);

            $incident->incidentTime = $time;
        }

        // Initialize incidentTimezone with current system timezone
        if (empty($incident->incidentTimezone)) {
            $timezone = Zend_Date::now()->get(Zend_Date::TIMEZONE);

            $incident->incidentTimezone = isset($this->_timezones[$timezone]) ? $timezone : null;
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
        $formPart->setAttrib('id', 'incident_wizard');

        $cancelButton = new Fisma_Yui_Form_Button_Submit(
            'irReportCancel',
            array(
                'label' => 'Cancel Report',
                'imageSrc' => '/images/del.png',
            )
        );
        $formPart->addElement($cancelButton);

        if ($step > 0) {
            $backwardButton = new Fisma_Yui_Form_Button_Submit(
                'irReportBackwards',
                array(
                    'label' => 'Go Back',
                    'imageSrc' => '/images/left_arrow.png',
                )
            );
            $formPart->addElement($backwardButton);
        }

        $forwardButton = new Fisma_Yui_Form_Button_Submit(
            'irReportForwards',
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

                if ($this->_me) {
                    // Load data into organization/system field for authenticated users only
                    $organizationSelect = $formPart->getElement('organizationId');

                    $organizations  = CurrentUser::getInstance()
                        ->getOrganizationsQuery()
                        ->addSelect("CONCAT(o.nickname, ' - ', o.name) AS label")
                        ->leftJoin('o.System s')
                        ->andWhere('s.sdlcPhase IS NULL OR s.sdlcPhase <> ?', 'disposal')
                        ->orderBy('label')
                        ->execute()
                        ->toKeyValueArray('id', 'label');

                    $organizationSelect->addMultiOption(0, "I don't know");
                    $organizationSelect->addMultiOptions($organizations);

                    // Load incident categories for authenticated users only
                    $categorySelect = $formPart->getElement('categoryId');

                    $categorySelect->addMultiOption(0, "I don't know");
                    $categorySelect->addMultiOptions(IrCategoryTable::getCategoriesForSelect());
                } else {
                    $formPart->removeElement('organizationId');
                    $formPart->removeElement('categoryId');
                }

                // Remove the building/room fields
                $formPart->removeElement('locationBuilding');
                $formPart->removeElement('locationRoom');
                break;
            case 3:
                foreach ($this->_getOS() as $key => $os) {
                    $formPart->getElement('hostOs')
                             ->addMultiOptions(array($key => $os));
                }
                break;
            case 4:
                $this->_createBoolean($formPart, array('piiInvolved'));

                // Remove '--select--' option
                $formPart->getElement('piiInvolved')->removeMultiOption('');
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

        $formPart = Fisma_Zend_Form_Manager::addDefaultElementDecorators($formPart);
        return $formPart;
    }

    /**
     * Lets a user review the incident report in its entirety before submitting it.
     *
     * This action is available to unauthenticated users.
     *
     * @GETAllowed
     */
    public function reviewReportAction()
    {
        if (!$this->_me) {
            $this->_helper->layout->setLayout('anonymous');
        }

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

                if ($key == 'organizationId') {
                    $value = "{$incident->Organization->nickname} - {$incident->Organization->name}";
                }

                if ($columnDef) {
                    if (isset($columnDef['extra']['logicalName'])) {
                        $logicalName = stripslashes($columnDef['extra']['logicalName']);
                        $incidentReview[$logicalName] = stripslashes($value);

                        // we need to know, in the view, which fields are rich-text
                        if (!empty($columnDef['extra']['purify'])) {
                            $richColumns[$logicalName] = $columnDef['extra']['purify'];
                        }
                    }
                } else {
                    throw new Fisma_Zend_Exception("Column ($key) is not defined");
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
     * @GETAllowed
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
            $options = array(
                'incidentUrl' => Fisma_Url::baseUrl() . '/incident/view/id/' . $incident->id,
                'incidentId' => $incident->id
            );

            $mail = new Mail();
            $mail->recipient     = $coordinator['u_email'];
            $mail->recipientName = $coordinator['u_name'];
            $mail->subject       = "A new incident has been reported.";

            $mail->mailTemplate('ir_reported', $options);

            Zend_Registry::get('mail_handler')->setMail($mail)->send();
        }

        // Set the intial POC to one of the ISSOs (Yes, this code stinks, but the requirements lack
        // specificity on this topic.)
        if ($incident->organizationId) {
            $issoQuery = Doctrine_Query::create()->from('User u')
                                                 ->select('u.id')
                                                 ->addSelect('u.username')
                                                 ->innerJoin('u.UserRole ur')
                                                 ->innerJoin('ur.Role r')
                                                 ->innerJoin('ur.UserRoleOrganization uro')
                                                 ->where('r.nickname LIKE ?', 'ISSO')
                                                 ->andWhere('uro.organizationId = ?', $incident->organizationId)
                                                 ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

            $issos = $issoQuery->execute();

            if (count($issos) > 0) {
                $incident->pocId = $issos[0]['id'];
                $incident->save();

                $mailSubject = "You have been assigned as the Point Of Contact for an incident.";
                $this->_sendMailToAssignedUser($issos[0]['id'], $incident->id, $mailSubject);

                $message = "The ISSO ({$issos[0]['username']}) has been notified of this incident.";
                $this->view->priorityMessenger($message, 'notice');
            }
        }

        // Clear out serialized incident object
        unset($session->irDraft);

        // Create buttons
        if ($this->_me) {
            $this->view->viewIncidentButton = new Fisma_Yui_Form_Button_Link(
                'viewIncidentButton',
                array('value' => 'View Incident', 'href' => "/incident/view/id/{$incident->id}")
            );
        }

        $this->view->createNewButton = new Fisma_Yui_Form_Button_Link(
            'createNewButton',
            array('value' => 'Create New Incident', 'href' => '/incident/report', 'imageSrc' => '/images/create.png')
        );
    }

    /**
     * Remove the serialized incident object from the session object.
     *
     * This action is available to unauthenticated users
     *
     * @GETAllowed
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
     * @GETAllowed
     * @return string the rendered page
     */
    public function viewAction()
    {
        $id = $this->_request->getParam('id');

        $incidentQuery = Doctrine_Query::create()
                         ->from('Incident i')
                         ->leftJoin('i.Attachments a')
                         ->where('i.id = ?', $id);
        $results = $incidentQuery->execute();
        $incident = $results->getFirst();

        $incident = $this->_getSubject($id);

        $this->_assertCurrentUserCanViewIncident($id);

        $this->view->id = $id;
        $this->view->incident = $incident;

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        // Put a span around the comment count so that it can be updated from Javascript
        $commentCount = '<span id=\'incidentCommentsCount\'>' . $incident->getComments()->count() . '</span>';

        $artifactCount = $incident->Attachments->count();

        // Create tab view
        $tabView = new Fisma_Yui_TabView('SystemView', $id);

        $tabView->addTab("Incident $id", "/incident/incident/id/$id");
        $tabView->addTab('Workflow', "/incident/workflow/id/$id");
        $tabView->addTab('Actors & Observers', "/incident/users/id/$id");
        $tabView->addTab("Comments ($commentCount)", "/incident/comments/id/$id");
        $tabView->addTab("Artifacts ($artifactCount)", "/incident/artifacts/id/$id");
        $tabView->addTab('Audit Log', "/incident/audit-log/id/$id");

        $this->view->tabView = $tabView;
        $this->view->formAction = "/incident/update/id/$id$fromSearchUrl";
        $this->view->toolbarButtons = $this->getToolbarButtons($incident, $fromSearchParams);
        $this->view->searchButtons = $this->getSearchButtons($incident, $fromSearchParams);
    }

    /**
     * Display incident details
     *
     * This is loaded into a tab view, so it has no layout
     *
     * @GETAllowed
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
                         ->leftJoin('i.PointOfContact poc')
                         ->where('i.id = ?', $id)
                         ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $results = $incidentQuery->execute();
        $incident = $results[0];

        $this->view->incident = $incident;
        $createdDateTime = new Zend_Date($incident['reportTs'], Fisma_Date::FORMAT_DATETIME);
        $this->view->createDateTime = $createdDateTime->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                      . ' at '
                                      . $createdDateTime->toString(Fisma_Date::FORMAT_AM_PM_TIME);

        $incidentDateTime = $incident['incidentDate'] . ' ' . $incident['incidentTime'];
        $incidentDate = new Zend_Date($incidentDateTime, Fisma_Date::FORMAT_DATETIME);

        $this->view->incidentDateTime = $incidentDate->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                       . ' at '
                                       . $incidentDate->toString(Fisma_Date::FORMAT_AM_PM_TIME)
                                       . ' ' . $incident['incidentTimezone'];

        if (!empty($incident['closedTs'])) {
            $closedDateTime = new Zend_Date($incident['closedTs'], Fisma_Date::FORMAT_DATETIME);
            $this->view->closedTs = $closedDateTime->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR)
                                      . ' at '
                                      . $closedDateTime->toString(Fisma_Date::FORMAT_AM_PM_TIME);
        }

        $this->_assertCurrentUserCanViewIncident($id);

        $this->view->updateIncidentPrivilege = $this->_currentUserCanUpdateIncident($id);
        $this->view->lockIncidentPrivilege = $this->_acl->hasPrivilegeForClass('lock', 'Incident');

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

        $incident->getAuditLog()->write("The incident has been locked.");

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $this->_redirect("/incident/view/id/$id$fromSearchUrl");
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

        $incident->getAuditLog()->write("The incident has been unlocked.");

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $this->_redirect("/incident/view/id/$id$fromSearchUrl");
    }

    /**
     * Display the audit log for an incident
     *
     * @GETAllowed
     */
    public function auditLogAction()
    {
        $id = $this->_request->getParam('id');

        $this->_assertCurrentUserCanViewIncident($id);

        /** @todo move to ajax context */
        $this->_helper->layout->disableLayout();

        $incident = Doctrine::getTable('Incident')->find($id);

        $logs = $incident->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);

        $logRows = array();
        foreach ($logs as $log) {
            $logRows[] = array(
                'timestamp' => $log['o_createdTs'],
                'user' => !empty($log['u_id']) ? $this->view->userInfo($log['u_displayName'], $log['u_id']) : '',
                'message' =>  $this->view->textToHtml($this->view->escape($log['o_message']))
            );
        }

        $dataTable = new Fisma_Yui_DataTable_Local();

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Timestamp',
                true,
                null,
                null,
                'timestamp'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'User',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'username'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Message',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'message'
            )
        );

        $dataTable->setData($logRows);
        $this->view->dataTable = $dataTable;
    }

    /**
     * Display users with actor or observer privileges and provide controls to add/remove actors and observers
     *
     * @GETAllowed
     */
    public function usersAction()
    {
        $this->_helper->layout->disableLayout();

        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);

        $this->_assertCurrentUserCanViewIncident($id);

        $updateIncidentPrivilege = $this->_currentUserCanUpdateIncident($id);
        $this->view->updateIncidentPrivilege = $updateIncidentPrivilege;

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

        $actorRows = array();

        foreach ($actors as $actor) {
            $actorColumns = array(
                $actor['i_id'],
                $actor['a_id'],
                $actor['a_username'],
                $actor['a_nameFirst'],
                $actor['a_nameLast'],
                null // This is for the delete column
            );

            if (!$updateIncidentPrivilege) {
                array_pop($actorColumns);
            }

            $actorRows[] = $actorColumns;
        }

        $actorTable = new Fisma_Yui_DataTable_Local();
        $actorTable->setRegistryName('actorTable');

        $col = "Fisma_Yui_DataTable_Column";
        $actorTable->addColumn(new $col('', true, 'Fisma.TableFormat.formatHtml', null, 'incidentId', true))
                   ->addColumn(new $col('', true, 'Fisma.TableFormat.formatHtml', null, 'userId', true))
                   ->addColumn(new $col('Username', true, 'Fisma.TableFormat.formatHtml', null, 'username'))
                   ->addColumn(new $col('First Name', true, null, null, 'nameFirst'))
                   ->addColumn(new $col('Last Name', true, null, null, 'nameLast'));

        if ($updateIncidentPrivilege) {
            $actorTable->addColumn(new $col('', true, 'Fisma.TableFormat.remover', null, 'remover'));
        }

        $actorTable->setData($actorRows);

        $this->view->actorDataTable = $actorTable;

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

        $observerRows = array();

        foreach ($observers as $observer) {
            $observerColumns = array(
                $observer['i_id'],
                $observer['o_id'],
                $observer['o_username'],
                $observer['o_nameFirst'],
                $observer['o_nameLast'],
                null // This is for the delete column
            );

            if (!$updateIncidentPrivilege) {
                array_pop($observerColumns);
            }

            $observerRows[] = $observerColumns;
        }

        $observerTable = new Fisma_Yui_DataTable_Local();
        $observerTable->setRegistryName('observerTable');

        $observerTable->addColumn(new $col('', true, 'Fisma.TableFormat.formatHtml', null, 'incidentId', true))
                      ->addColumn(new $col('', true, 'Fisma.TableFormat.formatHtml', null, 'userId', true))
                      ->addColumn(new $col('Username', true, 'Fisma.TableFormat.formatHtml', null, 'username'))
                      ->addColumn(new $col('First Name', true, null, null, 'nameFirst'))
                      ->addColumn(new $col('Last Name', true, null, null, 'nameLast'));

        if ($updateIncidentPrivilege) {
            $observerTable->addColumn(new $col('', true, 'Fisma.TableFormat.remover', null, 'remover'));
        }

        $observerTable->setData($observerRows);

        $this->view->observerDataTable = $observerTable;

        // Create autocomplete for actors
        $this->view->actorAutocomplete = new Fisma_Yui_Form_AutoComplete(
            'actorAutocomplete',
            array(
                'resultsList' => 'users',
                'fields' => 'username',
                'xhr' => "/incident/get-eligible-users/id/$id",
                'hiddenField' => 'actorId',
                'queryPrepend' => '/query/',
                'containerId' => 'actorAutocompleteContainer',
                'enterKeyEventHandler' => 'Fisma.Incident.handleAutocompleteEnterKey',
                'enterKeyEventArgs' => 'actor'
            )
        );

        $this->view->addActorButton = new Fisma_Yui_Form_Button(
            'addActor',
            array(
                'label' => 'Add Actor',
                'onClickFunction' => 'Fisma.Incident.addUser',
                'onClickArgument' => array('type' => 'actor', 'incidentId' => $id)
            )
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
                'containerId' => 'observerAutocompleteContainer',
                'enterKeyEventHandler' => 'Fisma.Incident.handleAutocompleteEnterKey',
                'enterKeyEventArgs' => 'observer'
            )
        );

        $this->view->addObserverButton = new Fisma_Yui_Form_Button(
            'addObserver',
            array(
                'label' => 'Add Observer',
                'onClickFunction' => 'Fisma.Incident.addUser',
                'onClickArgument' => array('type' => 'observer', 'incidentId' => $id)
            )
        );
    }

    /**
     * Add a user as an actor or observer to the specified incident.
     *
     * This is called asynchronously from Fisma.Incident.addUser().
     */
    public function addUserAction()
    {
        $response = new Fisma_AsyncResponse;

        $incidentId = $this->getRequest()->getParam('incidentId');

        $this->_assertCurrentUserCanUpdateIncident($incidentId);

        $type = $this->getRequest()->getParam('type');

        if (!in_array($type, array('actor', 'observer'))) {
            throw new Fisma_Zend_Exception("Invalid incident user type: '$type'");
        }

        $userId = $this->getRequest()->getParam('userId');
        $username = $this->getRequest()->getParam('username');

        /*
         * User ID is supplied by an autocomplete. If the user did not use autocomplete, then check to see if the
         * username can be looked up.
         */
        if (strlen($userId) > 0) {
            $user = Doctrine::getTable('User')->find($userId, Doctrine::HYDRATE_ARRAY);
        } elseif (strlen($username) > 0) {
            $user = Doctrine::getTable('User')->findOneByUsername($username, Doctrine::HYDRATE_ARRAY);
        }

        if (isset($user) && !empty($user)) {
            // Create the requested link
            $incidentActor = new IrIncidentUser();

            $incidentActor->userId = $user['id'];
            $incidentActor->incidentId = $incidentId;
            $incidentActor->accessType = strtoupper($type);

            try {
                $incidentActor->save();
            } catch (Doctrine_Connection_Exception $e) {
                $portableCode = $e->getPortableCode();

                if (Doctrine::ERR_ALREADY_EXISTS == $portableCode) {
                    $message = 'That user is already an actor or an observer on this incident.';
                    $response->fail($message);
                } else {
                    throw $e;
                }
            }

            // Send e-mail
            $mailSubject = "You have been assigned to a new incident.";
            $this->_sendMailToAssignedUser($userId, $incidentId, $mailSubject);
        } else {
            $response->fail("No user found with that name.");
        }

        if ($response->success) {
            $this->view->user = array(
                'userId' => $user['id'],
                'incidentId' => $incidentId,
                'username' => $user['username'],
                'nameFirst' => $user['nameFirst'],
                'nameLast' => $user['nameLast']
            );
        }

        $this->view->response = $response;
    }

    /**
     * Remove user's actor or observer privileges for the specified incident
     */
    public function removeUserAction()
    {
        $response = new Fisma_AsyncResponse;

        $incidentId = $this->getRequest()->getParam('incidentId');
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        $this->_assertCurrentUserCanUpdateIncident($incidentId);

        // Remove the specified user from this incident
        $userId = $this->getRequest()->getParam('userId');

        Doctrine_Query::create()->delete()->from('IrIncidentUser iu')
                                          ->where('iu.userId = ? AND iu.incidentId = ?', array($userId, $incidentId))
                                          ->execute();

        $this->view->response = $response;
    }

    /**
     * Displays the incident workflow interface
     *
     * This actually forwards to one of several different views and doesn't render anything itself
     *
     * @GETAllowed
     *
     * @return string the rendered page
     */
    public function workflowAction()
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

        $this->_helper->layout->disableLayout();
    }

    /**
     * Updates an incident object by marking a step as completed
     *
     * @var Incident $incident
     */
    private function _completeWorkflowStep(Incident $incident)
    {
        try {
            $comment = $this->getRequest()->getParam('comment');

            // Get reference to current step before marking it complete
            $currentStep = $incident->CurrentWorkflowStep;

            $incident->completeStep($comment);

            foreach ($this->_getAssociatedUsers($incident->id) as $user) {
                $options = array(
                    'incidentUrl' => Fisma_Url::baseUrl() . '/incident/view/id/' . $incident->id,
                    'incidentId' => $incident->id,
                    'workflowStep' => $currentStep->name,
                    'workflowCompletedBy' => $currentStep->User->username
                );

                $mail = new Mail();
                $mail->recipient     = $user['u_email'];
                $mail->recipientName = $user['u_name'];
                $mail->subject       = "A workflow step has been completed";

                $mail->mailTemplate('ir_step', $options);

                Zend_Registry::get('mail_handler')->setMail($mail)->send();
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
    }

    /**
     * Add a comment to a specified incident
     *
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
     * @GETAllowed
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

        $commentRows = array();

        foreach ($comments as $comment) {
            $commentRows[] = array(
                'timestamp' => $comment['createdTs'],
                'username' => $this->view->userInfo($comment['User']['displayName'], $comment['User']['id']),
                'Comment' =>  $this->view->textToHtml($this->view->escape($comment['comment']))
            );
        }

        $dataTable = new Fisma_Yui_DataTable_Local();

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Timestamp',
                true,
                null,
                null,
                'timestamp'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'User',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'username'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Comment',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'comment'
            )
        );

        $dataTable->setData($commentRows);

        $this->view->dataTable = $dataTable;

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
    }

    /**
     * Display file artifacts associated with an incident
     *
     * @GETAllowed
     */
    public function artifactsAction()
    {
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);
        $incident = Doctrine_Query::create()
                            ->from('Incident i')
                            ->leftJoin('i.Attachments a')
                            ->where('i.id = ?', $id)
                            ->execute()
                            ->getLast();

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
        $artifactCollection = $incident->Attachments;
        $artifactRows = array();

        foreach ($artifactCollection as $artifact) {
            $downloadUrl = '/incident/download-artifact/id/' . $id . '/artifactId/' . $artifact->id;
            $artifactRows[] = array(
                'iconUrl'  => "<a href=\"$downloadUrl\"><img src=\""
                            . $this->view->escape($artifact->getIconUrl())
                            . "\"></a>",
                'fileName' => $this->view->escape($artifact->fileName),
                'fileNameLink' => "<a href=\"$downloadUrl\">" . $this->view->escape($artifact->fileName) . "</a>",
                'fileSize' => $artifact->getFileSize(),
                'user'     => $this->view->userInfo($artifact->User->displayName, $artifact->User->id),
                'date'     => $artifact->createdTs,
                'comment'  => $this->view->textToHtml($this->view->escape($artifact->description))
            );
        }

        $dataTable = new Fisma_Yui_DataTable_Local();

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Icon',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'icon'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'File Name',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'fileName',
                true
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'File Name',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'fileNameLink',
                false,
                'string',
                'fileName'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Size',
                true,
                'Fisma.TableFormat.formatFileSize',
                null,
                'size',
                false,
                'number'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Uploaded By',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'uploadedBy'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Upload Date',
                true,
                null,
                null,
                'uploadDate'
            )
        );

        $dataTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Comment',
                false,
                'Fisma.TableFormat.formatHtml',
                null,
                'comment'
            )
        );

        $dataTable->setData($artifactRows);

        $this->view->dataTable = $dataTable;
    }

    /**
     * Attach a new artifact to this incident
     *
     * This is called asychronously through the attach artifacts behavior. This is a bit hacky since it is invoked
     * by YUI's asynchronous file upload. This means the response is written to an iframe, so we can't render this view
     * as JSON.
     *
     * Instead, we render an HTML view with the JSON-serialized response inside it.
     *
     * @GETAllowed
     */
    public function attachArtifactAction()
    {
        $id = $this->getRequest()->getParam('id');
        $comment = $this->getRequest()->getParam('comment');

        $this->_helper->layout->disableLayout();

        $response = new Fisma_AsyncResponse();

        try {
            $incident = Doctrine_Query::create()
                            ->from('Incident i')
                            ->leftJoin('i.Attachments a')
                            ->where('i.id = ?', $id)
                            ->execute()
                            ->getLast();

            $this->_assertCurrentUserCanUpdateIncident($id);

            $file = $_FILES['file'];
            if (Fisma_FileManager::getUploadFileError($file)) {
               $error = Fisma_FileManager::getUploadFileError($file);
               throw new Fisma_Zend_Exception_User($error);
            }

            $incident->attach($_FILES['file'], $comment);
            $incident->save();

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
     *
     * @GETAllowed
     */
    public function downloadArtifactAction()
    {
        $incidentId = $this->getRequest()->getParam('id');
        $artifactId = $this->getRequest()->getParam('artifactId');

        // If user can view this artifact's incident, then they can download the artifact itself
        $incident = Doctrine::getTable('Incident')->find($incidentId);

        $this->_assertCurrentUserCanViewIncident($incidentId);

        // Send artifact to browser
        $upload = Doctrine::getTable('Upload')->find($artifactId);
        $this->_helper->downloadAttachment($upload->fileHash, $upload->fileName);
    }

    /**
     * Update incident
     */
    public function updateAction()
    {
        $id = $this->_request->getParam('id');
        $this->_assertCurrentUserCanUpdateIncident($id);
        $incident = Doctrine::getTable('Incident')->find($id);

        if (!$incident) {
            throw new Fisma_Zend_Exception_User("Invalid Incident ID");
        }

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        if ($this->getRequest()->getPost('reject')) {
            $incident->reject();
            $incident->save();
        }

        if ($this->getRequest()->getPost('completeStep')) {
            $this->_completeWorkflowStep($incident);
        }

        try {
            // Update the incident's data
            $newValues = $this->getRequest()->getParam('incident');
            if (!empty($newValues)) {
                $incident->merge($newValues);
                $incident->save();
            }

             // If the POC changed, then send the POC an e-mail.
            if (isset($newValues['pocId']) && !empty($newValues['pocId'])) {
                $mailSubject = "You have been assigned as the Point Of Contact for an incident.";
                $this->_sendMailToAssignedUser($newValues['pocId'], $incident->id, $mailSubject);

                $this->view->priorityMessenger('A notification has been sent to the new Point Of Contact.', 'notice');
            }
        } catch (Doctrine_Validator_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }

        $this->_redirect("/incident/view/id/$id$fromSearchUrl");
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
     * Get the user ids of all IRCs
     */
    private function _getIrcs()
    {
        $query = Doctrine_Query::create()
                 ->select("u.email as email, CONCAT(u.nameFirst, ' ', u.nameLast) as name")
                 ->from('User u')
                 ->innerJoin('u.Roles r')
                 ->where('r.nickname LIKE ?', 'IRC')
                 ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $ircs = $query->execute();

        return $ircs;
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
                              ->select("u.email as email, CONCAT(u.nameFirst, ' ', u.nameLast) as name")
                              ->from('IrIncidentUser iru')
                              ->leftJoin('iru.User u')
                              ->where('iru.incidentId = ?', $incidentId)
                              ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $incidentUsers = $incidentUsersQuery->execute();

        return $incidentUsers;
    }

    /**
     * List users eligible to be an actor or observer
     *
     * All users are eligible unless they are already an actor or observer for this incident.
     *
     * @GETAllowed
     */
    public function getEligibleUsersAction()
    {
        $id = Inspekt::getInt($this->getRequest()->getParam('id'));
        $queryString = $this->getRequest()->getParam('query');

        $userQuery = Doctrine_Query::create()
                     ->select('u.username')
                     ->from('User u')
                     ->leftJoin("u.IrIncidentUser iu ON u.id = iu.userId AND iu.incidentId = $id")
                     ->where("u.username like ?", "%$queryString%")
                     ->andWhere('iu.incidentId IS NULL')
                     ->orderBy('u.username')
                     ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $users = $userQuery->execute();

        $list = array('users' => array_values($users));

        return $this->_helper->json($list);
    }

    /**
     * Replace the default "Create" button with a "Report Incident" button
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @param array $fromSearchParams The array for "Previous" and "Next" button null if not
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $fromSearchParams = null)
    {
        $buttons = parent::getToolbarButtons($record, $fromSearchParams);

        $fromSearchUrl = '';
        if (!empty($fromSearchParams)) {
            $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
        }

        $buttons['create'] = new Fisma_Yui_Form_Button_Link(
            'toolbarReportIncidentButton',
            array(
                'value' => 'Report Incident',
                'href' => $this->getBaseUrl() . '/report',
                'imageSrc' => '/images/create.png'
            )
        );

        // Add a "Reject" button if the incident is still in "new" status
        if ($record && 'new' == $record->status) {
            $buttons['reject'] = new Fisma_Yui_Form_Button(
                'reject',
                array(
                    'label' => 'Reject',
                    'onClickFunction' => 'Fisma.Incident.confirmReject',
                    'imageSrc' => '/images/trash_recyclebin_empty_closed.png'
                )
            );
        }

        // Add lock/unlock buttons if the user has the capability to use them
        if ($record && $this->_acl->hasPrivilegeForClass('lock', 'Incident')) {
            if ($record->isLocked) {
                $buttons['unlock'] = new Fisma_Yui_Form_Button(
                    'unlock',
                    array(
                        'label' => 'Unlock',
                        'onClickFunction' => 'Fisma.Util.formPostAction',
                        'onClickArgument' => array(
                            'action' => "/incident/unlock$fromSearchUrl",
                            'id' => $record->id,
                        ),
                        'imageSrc' => '/images/privacy-small.png'
                    )
                );
            } else {
                $buttons['lock'] = new Fisma_Yui_Form_Button(
                    'lock',
                    array(
                        'label' => 'Lock',
                        'onClickFunction' => 'Fisma.Util.formPostAction',
                        'onClickArgument' => array(
                            'action' => "/incident/lock$fromSearchUrl",
                            'id' => $record->id
                        ),
                        'imageSrc' => '/images/privacy-small.png'
                    )
                );
            }
        }

        return $buttons;
    }

    /**
     * Send email to the user who has been assigned an incident
     *
     * @param integer $userId The id of user
     * @param integer $incidentId The id of incident
     * @param string $mailSubject The subject of mail
     *
     * @return void
     */
    private function _sendMailToAssignedUser($userId, $incidentId, $mailSubject)
    {
        $user = Doctrine::getTable('User')->find($userId);

        $options = array(
            'incidentUrl' => Fisma_Url::baseUrl() . '/incident/view/id/' . $incidentId,
            'incidentId' => $incidentId,
            'isUser' => ($user instanceof User)
        );

        $mail = new Mail();
        $mail->recipient     = $user->email;
        $mail->recipientName = $user->nameFirst . ' ' . $user->nameLast;
        $mail->subject       = $mailSubject;

        $mail->mailTemplate('ir_assign', $options);

        Zend_Registry::get('mail_handler')->setMail($mail)->send();
    }
}
