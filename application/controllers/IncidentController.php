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
class IncidentController extends Zend_Controller_Action // extends BaseController
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
     * initialize the basic information, my orgSystems
     *
     */
    public function init()
    {
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
        parent::preDispatch();

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
        Fisma_Acl::requirePrivilege('incident', 'read');
        
        $arrTotal = array (
                        'new'      => 0,
                        'open'     => 0,
                        'resolved' => 0,
                        'rejected' => 0,
                        'closed'   => 0,
                    );

        $q = Doctrine_Query::create() 
             ->select('count(*) as count, i.status ')
             ->from('Incident i')
             ->groupBy('i.status');       

        $data = $q->execute()->toArray();
        foreach ($data as $key => $item) {
            $arrTotal[$item['status']] = $item['count'];
        }

        $this->view->summary = $arrTotal;
    }

   /**
     * statistics per status 
     */
    public function totalcategoryAction()
    {
        Fisma_Acl::requirePrivilege('incident', 'read');
        
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

        foreach($cats as $cat => $ids) {
            $q = Doctrine_Query::create() 
                 ->select('count(*) as count')
                 ->from('Incident i')
                 ->whereIn('i.classification', $ids)
                 ->whereIn('i.status', array('open','resolved','closed'));       

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
            $this->_helper->layout->setLayout('anonlayout');
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
            } else {
                throw new Fisma_Exception('User must move forwards or backwards');
            }
        }
        if ($step < 0) {
            throw new Fisma_Exception("Illegal step number: $step");
        }
        
        // Some business logic to determine if any steps can be skipped based on previous answers:
        // Authenticated users skip step 1 (which is reporter contact information)
        if (User::currentUser() && 1 == $step) {
            $incident->ReportingUser = User::currentUser();
            $step++;
        }
        // If no PII after step 5, then skip to end
        if ($step >=5 && 0 == $incident->piiInvolved) {
            if ($this->getRequest()->getParam('forwards')) {
                $step = count($this->_formParts);
            } else {
                $step = 4;
            }
        } elseif ($step >= 6 && 0 == $incident->piiShipment) {
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
        $formPart->setAction("/incident/report/step/$step");
        
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

        // Add forward/backward buttons to the form
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
        $formPart->setDisplayGroupDecorators(array(
            new Zend_Form_Decorator_FormElements(),
            new Fisma_Form_CreateIncidentDecorator()
        ));
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
                foreach($this->_getOS() as $key => $os) {
                    $formPart->getElement('hostOs')
                             ->addMultiOptions(array($key => $os));
                }
                break;
            case 4:
                $this->_createBoolean(&$formPart, array('piiInvolved'));
                break;
            case 5:
                $this->_createBoolean(&$formPart, array('piiMobileMedia', 
                                                        'piiEncrypted', 
                                                        'piiAuthoritiesContacted', 
                                                        'piiPoliceReport',
                                                        'piiIndividualsNotification',
                                                        'piiShipment'));
                $formPart->getElement('piiMobileMediaType')->addMultiOptions(array(0 => '--select--'));
                foreach($this->_getMobileMedia() as $key => $mm) {
                    $formPart->getElement('piiMobileMediaType')
                             ->addMultiOptions(array($key => $mm));
                }
                break;
            case 6:
                $this->_createBoolean(&$formPart, array('piiShipmentSenderContact'));
                break;
        }

        return $formPart;
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
    }

    /**
     * Inserts an incident record and forwards to the success page
     *
     * @return string the rendered page
     */
    public function saveReportAction() {
        // Fetch the incident report draft from the session
        $session = Fisma::getSession();
        if (isset($session->irDraft)) {
            $incident = unserialize($session->irDraft);
        } else {
            throw new Fisma_Exception('No incident report found in session');
        }

        $incident->save();

        // Add the EDCIRC as an actor
        $actor = new IrIncidentActor();
        $actor->incidentId = $incident->id;
        $edcirc = $this->_getEDCIRC();
        $actor->userId = $edcirc;
        $actor->save();
        
        // Send an email
        $mail = new Fisma_Mail();
        $mail->IRReport($edcirc, $subject['id']);
    }

    /**
     * Displays a incident report form for users who aren't logged into the system
     *
     * @return string the rendered page
     */
    public function anonsuccessAction() {
        $this->_helper->layout->setLayout('anonlayout'); 
        $this->render('anonsuccess');
    }   
   
    /**
     * Displays incident dashboard
     *
     * @return string the rendered page
     */
    public function dashboardAction() 
    {
        Fisma_Acl::requirePrivilege('incident', 'read'); 

        $value = trim($this->_request->getParam('keywords'));
        empty($value) ? $link = '' : $link = '/keywords/' . $value;
        
        $this->view->assign('pageInfo', $this->_paging);
        $this->view->assign('link', $link);
       
        /* NATHAN: This seems like it should work, but I am out of time to debug. When you submit a form the 
            dashboard is getting rendered two times.  
         */

        /* 
        $front = Zend_Controller_Front::getInstance();
        if ($stack = $front->getPlugin('Zend_Controller_Plugin_ActionStack')) {
            //clear the action stack to prevent additional exceptions would be throwed
            while($stack->popStack()) { print 'poping<br>'; } 
        }
        $this->_helper->actionStack('header', 'panel');
        */     

        $this->render('dashboard');
    }  

    public function commentdashboardAction() {
        $q  = Doctrine_Query::create()
            ->select('c.*')
            ->from('IrComment c')
            ->orderBy('c.createdTs DESC')
            ->limit('10');

        $comments = $q->execute()->toArray();

        foreach ($comments as $key => $comment) {
            $comments[$key]['user'] = $this->_getUser($comment['userId']);
        }


        $this->view->assign('comments', $comments);

        $this->render('commentdashboard');
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
        
        $status = ($this->_request->getParam('status')) ? $this->_request->getParam('status') : 'new';
        $this->view->assign('status', $status);

        $this->view->assign('startDt', $this->_request->getParam('startDt'));
        
        $this->view->assign('endDt',   $this->_request->getParam('endDt'));
        
        $this->view->assign('keywords',   $this->_request->getParam('keywords'));

        $this->render('list');
    }

    public function searchboxAction() 
    {
        $status = ($this->_request->getParam('status')) ? $this->_request->getParam('status') : 'new';
        $this->view->assign('status', $status);
        
        $this->view->assign('startDt', $this->_request->getParam('startDt'));
        
        $this->view->assign('endDt',   $this->_request->getParam('endDt'));
        
        $this->view->assign('keywords',   $this->_request->getParam('keywords'));
    
        $this->render('searchbox');
    }

    /**
     * Displays the data related to a particular incident - will be called by ajax on all the incident interfaces
     *
     * @return string the rendered page
     */
    public function incidentdataAction() 
    {
        $incident_id = $this->_request->getParam('id');
        $this->view->assign('id', $incident_id);
       
        $this->view->assign('cloneId', $this->_getClone($incident_id));

        $closed = $this->_request->getParam('closed');
        $this->view->assign('closed', $closed);
        
        $q  = Doctrine_Query::create()
            ->select('i.*')
            ->from('Incident i')
            ->where('i.id = ?', $incident_id);

        $incident = $q->execute()->toArray();
            
        $q2 = Doctrine_Query::create()
              ->select('sc.name')
              ->from('IrSubCategory sc')
              ->where('sc.id = ?', $incident[0]['classification']);
        
        $cat = $q2->execute()->toArray();            

        $incident[0]['category'] = $cat[0]['name'];
        
        $association = $this->_getAssociation($incident_id);
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
        Fisma_Acl::requirePrivilege('incident', 'read');

        $incident_id = $this->_request->getParam('id');
        $this->view->assign('id', $incident_id);
        
        $q  = Doctrine_Query::create()
            ->select('i.*')
            ->from('Incident i')
            ->where('i.id = ?', $incident_id);

        $incident = $q->execute();

        $this->view->assign('incident', $incident->toArray());

        $incident = $incident->toArray();
        $status = $incident[0]['status'];

        /* depending on the status of the incident, certain data needs to be retrieved 
           and a particular view script needs to be rendered 
        */    
        if ($status == 'open') {
            
            $this->render('workflow');

        } elseif ($status == 'new') {
            $form = Fisma_Form_Manager::loadForm('incident_classify');

            $this->_createBoolean(&$form, array('pii', 'oig'));
        
            $form->setDisplayGroupDecorators(array(
                new Zend_Form_Decorator_FormElements(),
                new Fisma_Form_CreateIncidentDecorator()
            ));

            $form->setElementDecorators(array(new Fisma_Form_ClassifyIncidentDecorator()));

            foreach($this->_getCategories() as $id => $cat) {
                $form->getElement('classification')
                     ->addMultiOptions(array($id => $cat));
            }

            $form->getElement('classification')->setValue($incident[0]['classification']);

            $element = new Zend_Form_Element_Hidden('id');
            $element->setValue($incident_id);

            $form->addElement($element);

            $this->view->assign('form', $form);

            $this->render('classify');
        
        } elseif (($status == 'resolved') || ($status == 'rejected')) {
            $form = Fisma_Form_Manager::loadForm('incident_close');

            $element = new Zend_Form_Element_Hidden('id');
            $element->setValue($incident_id);
            
            $form->addElement($element);
        
            $q  = Doctrine_Query::create()
                ->select('s.id')
                ->from('IrIncidentWorkflow s')
                ->where('s.incidentId = ?', $incident_id)
                ->andWhere('s.status = ?', 'current');
 
            $step = $q->execute();
            $step = $step->toArray();        

            $element2 = new Zend_Form_Element_Hidden('step_id');
            $element2->setValue($step[0]['id']);
     
            $form->addElement($element2);
            
            $form->setDisplayGroupDecorators(array(
                new Zend_Form_Decorator_FormElements(),
                new Fisma_Form_CreateIncidentDecorator()
            ));

            $form->setElementDecorators(array(new Fisma_Form_ClassifyIncidentDecorator()));

            $this->view->assign('form', $form);

            $q  = Doctrine_Query::create()
                ->select('iw.*')
                ->from('IrIncidentWorkflow iw')
                ->where('iw.incidentId = ?', $incident_id);

            $steps = $q->execute();

            $steps = $steps->toArray();
            
            foreach($steps as $key => $step) {
                if($step['userId']) {
                    $steps[$key]['user'] = $this->_getUser($step['userId']);
                }
            }

            $this->view->assign('steps', $steps);

            $this->render('close');

        } elseif($status == 'closed') {
            
            $q  = Doctrine_Query::create()
                ->select('iw.*')
                ->from('IrIncidentWorkflow iw')
                ->where('iw.incidentId = ?', $incident_id);

            $steps = $q->execute();

            $steps = $steps->toArray();
            
            foreach($steps as $key => $step) {
                if($step['userId']) {
                    $steps[$key]['user'] = $this->_getUser($step['userId']);
                }
            }

            $this->view->assign('steps', $steps);
            

            $this->render('history');
        }
    }
    
    /**
     * Clones a closed incident, assigns it to the EDCIRC, and returns the user to the dashboard
     *
     * @return string the rendered dashboard page
     */
    public function cloneAction() 
    {
        $inc_obj = new Incident();        

        $incident_id = $this->_request->getParam('id');
        $incident = $inc_obj->getTable()->find($incident_id);

        /* create a clone of the incident object */
        $clone = $incident->copy(false);
        $clone->status = 'new';
        $clone->save();


        /* add relationship to the cloned incident table */
        $clone_inc = new IrClonedIncident();

        $user = User::currentUser();
        
        $clone_inc->origIncidentId  = $incident_id;
        $clone_inc->cloneIncidentId = $clone->id;
        $clone_inc->createdTs       = date('Y-d-m H:i:s');
        $clone_inc->userId          = $user['id'];

        $clone_inc->save();

        /* associate edcirc with cloned incident as actor */
        $actor = new IrIncidentActor();

        $actor->incidentId = $clone->id;
        $actor->userId = $this->_getEDCIRC();
        $actor->save();

        $this->message('The incident has been cloned.', self::M_NOTICE);
        $this->_forward('dashboard');
    }
        

    /**
     * Displays the incident workflow interface
     *
     * @return string the rendered page
     */
    public function workflowAction() {
        $incident_id = $this->_request->getParam('id');
        
        $q  = Doctrine_Query::create()
            ->select('iw.*')
            ->from('IrIncidentWorkflow iw')
            ->where('iw.incidentId = ?', $incident_id);

        $steps = $q->execute();

        $steps = $steps->toArray();
       
        foreach($steps as $key => $step) {
            if($step['userId']) {
                $steps[$key]['user'] = $this->_getUser($step['userId']);
            }
            elseif ($step['roleId']) {
                $steps[$key]['role'] = $this->_getRole($step['roleId']);
            }
        }
       
        $user = User::currentUser();
        $this->view->assign('user_roleId', $user['UserRole'][0]['roleId']);
        
        $association = $this->_getAssociation($incident_id);
        $this->view->assign('association', $association);
        
        $this->view->assign('id', $incident_id);
        $this->view->assign('steps', $steps);

        $this->render('workflow-interface');
    }

    /**
     * Updates incident to show that a particular step has been completed
     *
     * @return null
     */
    public function completestepAction() {
        $incident_id = $this->_request->getParam('id');
        $step_id     = $this->_request->getParam('step_id');
        $comments    = $this->_request->getParam('comments');
        
        $step = new IrIncidentWorkflow();

        /* update step just completed */
        $step = $step->getTable()->find($step_id);
        $step->status     = 'completed';
        $step->comments   = $comments;
        $step->userId     = Zend_Auth::getInstance()->getIdentity()->id;
        $step->completeTs = date('Y-m-d H:i:s');
        $step->save();
        
        $step_completed      = $step_id;
        $step_completed_sort = $step->sortorder;

        /* update next step to make it current */
        $step = $step->getTable()->find($step_id + 1);
        $step->status     = 'current';
        $step->save();

        /* check for last step and set incident status to resolved */
        $q  = Doctrine_Query::create()
            ->select('count(*) as count')
            ->from('IrIncidentWorkflow iw')
            ->where('iw.incidentId = ?', $incident_id)
            ->andWhere('iw.status = ?', 'queued');

        $step_count = $q->execute();
        $step_count = $step_count->toArray();    
    
        if ($step_count['0']['count'] == 0) {
            $incident = new Incident();
            $incident = $incident->getTable()->find($incident_id);

            $incident->status = 'resolved';

            $incident->save();
           
    
            foreach($this->_getAssociatedUsers($incident_id) as $userid) {
                /* Must instantiate object for each message to prevent exceptions */
                $mail = new Fisma_Mail();
                $mail->IRResolve($userid, $incident_id);
            }
 
            print 'redirect'; 
            exit;
        }

        foreach($this->_getAssociatedUsers($incident_id) as $userid) {
            $mail = new Fisma_Mail();
            $mail->IRStep($userid, $incident_id);
        }

        $this->view->assign('step_completed', $step_completed);
        $this->view->assign('step_completed_sort', $step_completed_sort);

        $this->_forward('workflow');
    }

    /**
     * Updates incident to show it has been closed
     *
     * @return null
     */
    public function closeAction() {
        $incident_id = $this->_request->getParam('id');
        $step_id     = $this->_request->getParam('step_id');
        $comment     = $this->_request->getParam('comment');

        $incident = new Incident();
        $incident = $incident->getTable()->find($incident_id);

        $incident->status = 'closed';
        $incident->save();

        $step = new IrIncidentWorkflow();
        $step = $step->getTable()->find($step_id);

        $step->status     = 'completed';
        $step->comments   = $comment;
        $step->userId     = Zend_Auth::getInstance()->getIdentity()->id;
        $step->completeTs = date('Y-m-d H:i:s');

        $step->save();
        
        foreach($this->_getAssociatedUsers($incident_id) as $userid) {
            $mail = new Fisma_Mail();
            $mail->IRClose($userid, $incident_id);
        }

 
        $this->message('Incident Closed', self::M_NOTICE);
        $this->_forward('dashboard');
    }

    /**
     * Updates incident to show it has been opened and assigned to a category
     *
     * @return Zend_Form
     */
    public function classifyAction() {
        $id            = $this->_request->getParam('id');
        $subCategoryId = $this->_request->getParam('classification');
        $comment       =  $this->_request->getParam('comment');
        $pa            =  $this->_request->getParam('pii');
        $oig           =  $this->_request->getParam('oig');


        /*  check to make sure nothing has been added to the incident workflow table for this incident already
            this will prevent duplicate entries if the classify page is refreshed
        */
        $q  = Doctrine_Query::create()
            ->select('count(*) as count')
            ->from('IrIncidentWorkflow iw')
            ->where('iw.incidentId = ?', $id);

        $count = $q->execute();
        $count = $count->toArray();    
        $count = $count[0]['count'];


        if($count == 0) {    
            if ($this->_request->getParam('Reject') == 'Reject') {
                $this->message('Incident Rejected', self::M_NOTICE);
                
                $incident = new Incident();
                $incident = $incident->getTable()->find($id);
                $incident->status = 'rejected';
                $incident->save();
            
                
                /* Add rejected step to workflow table*/
                $iw = new IrIncidentWorkflow();    
               
                $iw->incidentId  = $id; 
                $iw->name        = 'Incident Rejected';
                $iw->comments    = $comment;
                $iw->sortorder   = 0;
                $iw->userId      = Zend_Auth::getInstance()->getIdentity()->id;
                $iw->completeTs = date('Y-m-d H:i:s');

                $iw->status      = 'completed';

                $iw->save();

                /* Add final close step to incident workflow table*/
                $iw = new IrIncidentWorkflow();    
               
                $iw->incidentId  = $id; 
                $iw->name        = 'Close Incident';
                $iw->sortorder   = 1;

                $iw->status      = 'queued';

                $iw->save();

            } elseif ($this->_request->getParam('Open') == 'Open')  {
                $this->message('Incident Opened', self::M_NOTICE);

                /* update incident status and category */
                $incident                 = new Incident();
                $incident                 = $incident->getTable()->find($id);
                $incident->status         = 'open';
                $incident->classification = $subCategoryId;
                
                $incident->save();


                /* Add opened step to workflow table*/
                $iw = new IrIncidentWorkflow();    
               
                $iw->incidentId  = $id; 
                $iw->name        = 'Incident Opened';
                $iw->comments    = $comment;
                $iw->sortorder   = 0;
                $iw->userId      = Zend_Auth::getInstance()->getIdentity()->id;
                $iw->completeTs = date('Y-m-d H:i:s');

                $iw->status      = 'completed';

                $iw->save();

                /* create snapshot of workflow and add it to the ir_incident_workflow table */
                $subcat = Doctrine::getTable('IrSubCategory')->find($subCategoryId);
               
                $q = Doctrine_Query::create()
                     ->select('s.id, s.roleId, s.sortorder, s.name, s.description')
                     ->from('IrStep s')
                     ->where('s.workflowid = ?', $subcat->workflowId)
                     ->orderby('s.sortorder');
                    
                $steps = $q->execute();
     
                $steps = $steps->toArray();

                foreach($steps as $step) {
                    $iw = new IrIncidentWorkflow();    
                   
                    $iw->incidentId  = $id; 
                    $iw->roleId      = $step['roleId'];
                    $iw->name        = $step['name'];
                    $iw->description = $step['description'];
                    $iw->sortorder   = $step['sortorder'];

                    $iw->status      = ($step['sortorder'] == 1) ? 'current' : 'queued';
                                    
                    $iw->save();
                }

                /* Add final close step to incident workflow table*/
                $iw = new IrIncidentWorkflow();    
               
                $iw->incidentId  = $id; 
                $iw->name        = 'Close Incident';
                $iw->sortorder   = $step['sortorder'] + 1;

                $iw->status      = 'queued';

                $iw->save();
       
                if ($pa == 1) { 
                    $userid = $this->_getPA();
                    
                    $actor = new IrIncidentActor();

                    $actor->incidentId = $id;
                    $actor->userId = $userid;
                    $actor->save();
                }
                if ($oig == 1) { 
                    $userid = $this->_getOIG();
                    
                    $actor = new IrIncidentActor();

                    $actor->incidentId = $id;
                    $actor->userId = $userid;
                    $actor->save();
                }
        
                foreach($this->_getAssociatedUsers($id) as $userid) {
                    $mail = new Fisma_Mail();
                    $mail->IROpen($userid, $id);
                }
            }
        }

        $this->_forward('view');
    }

    /**
     * Displays the incident comment interface
     *
     * @return Zend_Form
     */
    function commentsAction() {
        $incident_id = $this->_request->getParam('id');
        $this->view->assign('id', $incident_id);

        $association = $this->_getAssociation($incident_id);
        $this->view->assign('association', $association);

        $q  = Doctrine_Query::create()
            ->select('c.*')
            ->from('IrComment c')
            ->where('c.incidentId = ?', $incident_id)
            ->orderBy('createdTs DESC');

        $comments = $q->execute()->toArray();

        foreach($comments as $key => $comment) {
            $comments[$key]['user'] = $this->_getUser($comment['userId']);
        }

        $this->view->assign('comments', $comments);

        $this->render('comments');   
    }
    
    /**
     * Displays just comments, no comment form
     *
     * @return Zend_Form
     */
    function commentsnoformAction() {
        $incident_id = $this->_request->getParam('id');
        $this->view->assign('id', $incident_id);

        $q  = Doctrine_Query::create()
            ->select('c.*')
            ->from('IrComment c')
            ->where('c.incidentId = ?', $incident_id)
            ->orderBy('createdTs DESC');

        $comments = $q->execute();

        $comments = $comments->toArray();

        foreach($comments as $key => $comment) {
            $comments[$key]['user'] = $this->_getUser($comment['userId']);
        }

        $this->view->assign('comments', $comments);

        $this->render('comments-noform');   
    }

    
    /**
     * Adds a comment to the database and associates it with an incident
     *
     * @return Zend_Form
     */
    function addcommentAction() {
        $incident_id = $this->_request->getParam('id');
        $comments    = $this->_request->getParam('comments');
        
        $comment = new IrComment();

        $comment->incidentId = $incident_id;
        $comment->userId     = Zend_Auth::getInstance()->getIdentity()->id;
        $comment->createdTs  = date('Y-m-d H:i:s');
        $comment->comment    = $comments;

        $comment->save();
        
        foreach($this->_getAssociatedUsers($incident_id) as $userid) {
            $mail = new Fisma_Mail();
            $mail->IRComment($userid, $incident_id);
        }

        $this->_forward('comments');
    }


    /**
     * Returns the forms and lists for managing actors and viewers
     *
     * @return Zend_Form
     */
    public function actorAction() 
    {
        $id = $this->_request->getParam('id');

        $q = Doctrine_Query::create()
             ->select('u.id, u.nameFirst, u.nameLast, u.username, ur.*, r.nickname as role')
             ->from('User u')
             ->innerJoin('u.UserRole ur')
             ->innerJoin('ur.Role r')
             ->where('u.id NOT IN (SELECT ia.userId FROM IrIncidentActor ia WHERE ia.incidentid = ?)', $id)
             ->andWhere('u.id NOT IN (SELECT io.userId FROM IrIncidentObserver io WHERE io.incidentid = ?)', $id)
             ->andWhere('NOT (u.username = ?)', 'root')
             ->orderBy('u.nameLast');

        $users = $q->execute()->toArray();

        $this->view->assign('id',$id);
        $this->view->assign('users',$users);

        $q = Doctrine_Query::create()
             ->select('u.id, u.nameFirst, u.nameLast, u.username, ur.*, r.nickname as role')
             ->from('user u')
             ->innerJoin('u.UserRole ur')
             ->innerJoin('ur.Role r')
             ->where('u.id IN (SELECT ia.userId FROM IrIncidentActor ia WHERE ia.incidentId = ?)', $id)
             ->orderBy('u.nameLast');

        $users = $q->execute();

        $this->view->assign('actors',$users->toArray());
        
        $q = Doctrine_Query::create()
             ->select('u.id, u.nameFirst, u.nameLast, u.username, ur.*, r.nickname as role')
             ->from('user u')
             ->innerJoin('u.UserRole ur')
             ->innerJoin('ur.Role r')
             ->where('u.id IN (SELECT io.userId FROM IrIncidentObserver io WHERE io.incidentId = ?)', $id)
             ->orderBy('u.nameLast');

        $users = $q->execute();

        $this->view->assign('observers',$users->toArray());

        $association = $this->_getAssociation($id);
        $this->view->assign('association', $association);
        
        $user = User::currentUser();
        $this->view->assign('user_id', $user['id']);

        $this->render('actors');
    }

    /**
     * Associates a user with an incident as an actor
     *
     * @return Zend_Form
     */
    public function actoraddAction() {
        $id     = $this->_request->getParam('id');
        $userid = $this->_request->getParam('userid');
        
        $actor = new IrIncidentActor();

        $actor->incidentId = $id;
        $actor->userId = $userid;
        $actor->save();

        $mail = new Fisma_Mail();
        $mail->IRAssign($userid, $id);

        $this->_forward('actor'); 
    }
    
    /**
     * Unassociates a user with an incident
     *
     * @return Zend_Form
     */
    public function actorremoveAction() {
        $id     = $this->_request->getParam('id');
        $userid = $this->_request->getParam('userid');
        
        $q =    Doctrine_Query::create()
                ->delete('IrIncidentActor ia')
                ->where('ia.userid = ?', $userid)
                ->andWhere('ia.incidentid = ?', $id);
        
        $q->execute();

        $this->_forward('actor'); 
    }

    /**
     * Associates a user with an incident as an observer
     *
     * @return Zend_Form
     */
    public function observeraddAction() {
        $id     = $this->_request->getParam('id');
        $userid = $this->_request->getParam('userid');
        
        $actor = new IrIncidentObserver();

        $actor->incidentId = $id;
        $actor->userId = $userid;
        $actor->save();
        
        $mail = new Fisma_Mail();
        $mail->IRAssign($userid, $id);

        $this->_forward('actor'); 
    }

    /**
     * Associates a user with the action and associates them as an actor
     *
     * @return Zend_Form
     */
    public function observerremoveAction() {
        $id     = $this->_request->getParam('id');
        $userid = $this->_request->getParam('userid');
        
        $q =    Doctrine_Query::create()
                ->delete('IrIncidentObserver io')
                ->where('io.userid = ?', $userid)
                ->andWhere('io.incidentid = ?', $id);
        
        $q->execute();

        $this->_forward('actor'); 
    }
 
    /**
     * list the incidents from the search, 
     * if search none, list all incidents
     * 
     */
    public function searchAction()
    {
        $ids = $this->_userIncidents();

        if (empty($ids)) {
            $ids = array(-1);
        }

        Fisma_Acl::requirePrivilege('incident', 'read');
        $value = trim($this->_request->getParam('keywords'));

        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        $sortBy = $this->_request->getParam('sortby', 'reportTs');
        $order = $this->_request->getParam('order');
        $status = array($this->_request->getParam('status'));

        if($status[0] == 'resolved') {
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
        
        $q = Doctrine_Query::create()
             ->select('*')
             ->from('Incident i')
             ->whereIn('i.id', $ids)
             ->orderBy("i.$sortBy $order")
             ->limit($this->_paging['count'])
             ->offset($this->_paging['startIndex']);
             
        if ($status[0] != 'all') {
            $q->whereIn('i.status', $status);
        }

        if ($this->_request->getParam('startDt')) {
            $q->andWhere('i.reportTs > ?', $this->_request->getParam('startDt'));
        }
        
        if ($this->_request->getParam('endDt')) {
            $q->andWhere('i.reportTs < ?', $this->_request->getParam('endDt'));
        }
        
        if ($this->_request->getParam('keywords')) {
            $keywords = $this->_request->getParam('keywords');
            $q->andWhere('i.additionalInfo LIKE ?', '%'.$keywords[0].'%');
        }

        $totalRecords = $q->count();
        $incidents = $q->execute();
    
        $incidents = $incidents->toArray();
   
        foreach ($incidents as $key => $val) {
            $q2 = Doctrine_Query::create()
                  ->select('sc.name')
                  ->from('IrSubCategory sc')
                  ->where('sc.id = ?', $val['classification']);
            
            $cat = $q2->execute()->toArray();            

            $incidents[$key]['category'] = $cat[0]['name'];

            if ($incidents[$key]['piiInvolved'] == 1) {
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

    public function editAction() 
    {
        $incident_id = $this->_request->getParam('id');
        $this->view->assign('id', $incident_id);

        $incident = Doctrine::getTable('Incident')->find($incident_id);       


        $form = $this->getForm();
        $form->setAction("/panel/incident/sub/update/id/$incident_id");

        $incident = $incident->toArray();

        $form->setDefaults($incident);

        $this->view->form = $form;
        $this->render('edit');
    }

    public function updateAction() 
    {
       Fisma_Acl::requirePrivilege('incident', 'update');
        $id = $this->_request->getParam('id', 0);
        $incident = new Incident();
        $incident = $incident->getTable()->find($id);

        if (!$incident) {
            throw new Exception_General("Invalid Incident ID");
        }

        $form = $this->getForm($incident);
        $incidentValues = $this->_request->getPost();

        if ($form->isValid($incidentValues)) {
            $isModify = false;
            $incidentValues = $form->getValues();
            $incident->merge($incidentValues);

            if ($incident->isModified()) {
                $incident->save();
                $isModify = true;
            }

            if ($isModify) {
                $msg = "The incident is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changed";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $incident->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update incident<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
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


    private function _getStates() {
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
    
    private function _getOS() {
        return array(        '' => '',
                         'win7' => 'Windows 7',
                        'vista' => 'Vista',
                           'xp' => 'XP',
                        'macos' => 'Mac OSX',
                        'linux' => 'Linux',
                         'unix' => 'Unix'
                    );
    }
    
    private function _getMobileMedia() {
        return array(    'laptop' => 'Laptop',
                           'disc' => 'CD/DVD',
                       'document' => 'Document',
                            'usb' => 'USB/Flash Drive',
                           'tape' => 'Magnetic Tape',
                          'other' => 'Other'
                    );
    }

    private function _createBoolean(&$form, $elements) {
        foreach($elements as $elementName) {
            $element = $form->getElement($elementName);
            $element->addMultiOptions(array('' => ' -- select -- ')); 
            $element->addMultiOptions(array('0' => ' NO ')); 
            $element->addMultiOptions(array('1' => ' YES ')); 
        }

        return 1;
    }

    private function _getCategories() {
        $q = Doctrine_Query::create()
             ->select('c.id, c.category')
             ->from('IrCategory c')
             ->orderBy("c.category");

        $categories = $q->execute()->toArray();
        
        foreach($categories as $key => $val) {
                $q2 = Doctrine_Query::create()
                     ->select('s.id, s.name')
                     ->from('IrSubCategory s')
                     ->where('s.categoryId = ?', $val['id'])
                     ->orderBy("s.name");

                $subCats = $q2->execute()->toArray();
                foreach($subCats as $key2 => $val2) {
                    $ret_val[$val2['id']] = "{$val['category']} - {$val2['name']}";
                }
        }

        return $ret_val;
    }
    
    private function _getCategoriesArr() {
        $q = Doctrine_Query::create()
             ->select('c.id, c.category')
             ->from('IrCategory c')
             ->orderBy("c.category");

        $categories = $q->execute()->toArray();
        
        foreach($categories as $key => $val) {
                $q2 = Doctrine_Query::create()
                     ->select('s.id, s.name')
                     ->from('IrSubCategory s')
                     ->where('s.categoryId = ?', $val['id'])
                     ->orderBy("s.name");

                $subCats = $q2->execute()->toArray();
                foreach($subCats as $key2 => $val2) {
                    $ret_val[$val['category']][] = $val2['id'];
                }
        }

        return $ret_val;
    }

    private function _getUser($id) {
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

    private function _getEDCIRC()
    {
        /* not sure what the deal is here.. not working on anon incident form submit */
        $q = Doctrine_Query::create()
             ->select('r.id')
             ->from('Role r')
             ->where('r.nickname = ?', 'EDCIRC');
       
        $data = $q->execute()->toArray();

        $q = Doctrine_Query::create()
             ->select('u.userId')
             ->from('UserRole u')
             ->where('u.roleId = ?', $data[0]['id']);

        $user = $q->execute()->toArray();
 
        return $user[0]['userId'];
    }

    private function _getOIG()
    {
        $q = Doctrine_Query::create()
             ->select('u.id, ur.*')
             ->from('User u')
             ->innerJoin('u.UserRole ur')
             ->innerJoin('ur.Role r')
             ->where('r.nickname = ?', 'OIG');

        $user = $q->execute()->toArray();
                
        return $user[0]['id'];
    }
    
    private function _getPA()
    {
        $q = Doctrine_Query::create()
             ->select('u.id, ur.*')
             ->from('User u')
             ->innerJoin('u.UserRole ur')
             ->innerJoin('ur.Role r')
             ->where('r.nickname = ?', 'PA');

        $user = $q->execute()->toArray();
                
        return $user[0]['id'];
    }

    private function _userIncidents() 
    {
        $user = User::currentUser();
        
        $q = Doctrine_Query::create()
             ->select('i.incidentid')
             ->from('IrIncidentActor i')
             ->where('i.userid = ?', $user['id']);

        $id_data = $q->execute()->toArray();
       
        foreach ($id_data as $item) {
            $ret_val[] = $item['incidentId'];
        }

        $q = Doctrine_Query::create()
             ->select('i.incidentid')
             ->from('IrIncidentObserver i')
             ->where('i.userid = ?', $user['id']);

        $id_data = $q->execute()->toArray();

        foreach ($id_data as $item) {
            $ret_val[] = $item['incidentId'];
        }
    
        return $ret_val;
    }

    private function _getAssociation($incident_id) {
        $user = User::currentUser();
        
        $q = Doctrine_Query::create()
             ->select('count(*) as count')
             ->from('IrIncidentActor i')
             ->where('i.userid = ?', $user['id'])
             ->andWhere('i.incidentid = ?', $incident_id);

        $actor = $q->execute()->toArray();

        return ($actor[0]['count'] >= 1) ? 'actor' : 'viewer';
    }   

    private function _getClone($incident_id = null) {
        $q = Doctrine_Query::create()
             ->select('i.origincidentid')
             ->from('IrClonedIncident i')
             ->where('i.cloneincidentid = ?', $incident_id);

        $data = $q->execute()->toArray();
        
       
        if ($data ) { 
            if ($data[0]['origIncidentId']) {
                return $data[0]['origIncidentId'];
            } 
        }
            
        return false;
    }

    private function _getAssociatedUsers($incident_id) {
        $q = Doctrine_Query::create()
             ->select('u.userId')
             ->from('IrIncidentActor u')   
             ->where('u.incidentId = ?', $incident_id)
             ->groupBy('u.userId');

        $data = $q->execute()->toArray();
        
        foreach($data as $key => $val) {
            $ret_val[] = $val['userId'];
        }
    
        $q = Doctrine_Query::create()
             ->select('u.userId')
             ->from('IrIncidentObserver u')   
             ->where('u.incidentId = ?', $incident_id)
             ->groupBy('u.userId');

        $data = $q->execute()->toArray();
        
        foreach($data as $key => $val) {
            $ret_val[] = $val['userId'];
        }    

        return $ret_val;
    }
    
}
