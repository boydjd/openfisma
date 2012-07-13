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
 * CRUD for Account manipulation
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class UserController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'User';

    /**
     * Initialize internal members.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->_user = new User();
        $this->_helper->contextSwitch()
                      ->addActionContext('ldap-autocomplete', 'json')
                      ->addActionContext('tree-data', 'json')
                      ->addActionContext('autocomplete', 'json')
                      ->initContext();
        $this->_helper->ajaxContext()
                      ->addActionContext('log', 'html')
                      ->addActionContext('comments', 'html')
                      ->addActionContext('user', 'html')
                      ->initContext();
    }

    /**
     * Returns the standard form for reading, and updating
     * the current user's profile.
     *
     * @return Zend_Form The loaded user profile from
     * @todo This function is not named correctly
     */
    private function _getProfileForm()
    {
        $form = Fisma_Zend_Form_Manager::loadForm('user');
        $fieldsToOmit = array(
            'password', 'confirmPassword', 'generate_password', 'role', 'locked', 'lockReason', 'lockTs',
            'comment', 'reportingOrganizationId', 'mustResetPassword', 'lookup', 'separator', 'username', 'published'
        );

        foreach ($fieldsToOmit as $field) {
            $form->removeElement($field);
        }

        return $form;
    }

    /**
     * Set the Roles, organization relation before save the model
     *
     * @param Zend_Form $form The specified form to save
     * @param Doctrine_Record|null $subject The specified subject related to the form
     * @return Fisma_Doctrine_Record The saved record
     * @throws Fisma_Zend_Exception if the related subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        $conn = Doctrine_Manager::connection();
        $roleChanged = false;
        $organizationChanged = false;

        try {
            $conn->beginTransaction();

            $rolesOrganizations = $this->_request->getPost('organizations', array());
            $id = $this->getRequest()->getParam('id');

            if (is_null($subject)) {
                $subject = new $this->_modelName();
            } elseif (!($subject instanceof Doctrine_Record)) {
                throw new Fisma_Zend_Exception('Invalid parameter, expected a Doctrine_Model');
            }
            $values = $form->getValues();
            $actionName = strtolower($this->_request->getActionName());
            if ('view' === $actionName && 'root' !== $subject->username) {

                // Check whether role is changed for audit log
                $originalRoles = $subject->getRoles(Doctrine::HYDRATE_ARRAY);
                $originalRoleIds = array();

                if (is_array($originalRoles) && count($originalRoles) > 0) {
                    foreach ($originalRoles[0]['Roles'] as $key => $value) {
                        array_push($originalRoleIds, $value['id']);
                    }
                }
                $roleChanged = $this->_isRoleChanged($originalRoleIds, $values['role']);

                // If role is not changed, then check whether organization is changed for audit log
                if (!$roleChanged) {
                    $originalRoleOrganizations = Doctrine_Query::create()
                         ->from('UserRoleOrganization uro')
                         ->innerJoin('uro.UserRole ur')
                         ->where('ur.userId = ?', $subject->id)
                         ->setHydrationMode(Doctrine::HYDRATE_SCALAR)
                         ->execute();

                    $originalRolesOrganizations = array();
                    if (!empty($originalRoleOrganizations)) {
                        for ($i = 0; $i < count($originalRoleOrganizations); $i++) {
                            $originalRolesOrganizations[$originalRoleOrganizations[$i]['ur_roleId']][]
                            = $originalRoleOrganizations[$i]['uro_organizationId'];
                        }
                    }

                    $organizationChanged = $this->_isRolesOrganizationsChanged($originalRolesOrganizations,
                                                                               $rolesOrganizations);
                }

            }

            // If any roles were added to the user without any organizations, make sure they're added
            // to the appropriate array for saving the User Roles.
            if (Inspekt::isArrayOrArrayObject($values['role'])) {
                foreach (array_diff_key(array_flip($values['role']), $rolesOrganizations) as $k => $v) {
                    $rolesOrganizations[$k] = array();
                }
            }

            unset($values['role']);

            if (empty($values['password'])) {
                unset($values['password']);
            }

            if ($values['locked'] && !$subject->locked) {
                $subject->lockAccount(User::LOCK_TYPE_MANUAL);
                unset($values['locked']);
                unset($values['lockTs']);

                if (!empty($values['comment'])) {
                    $subject->getComments()->addComment($values['comment']);
                }
            } elseif (!$values['locked'] && $subject->locked) {
                $subject->unlockAccount();
                unset($values['locked']);
                unset($values['lockTs']);
            }

            $subject->merge($values);

            /*
             * We need to save the model once before linking related records, because Doctrine has a weird behavior
             * where an invalid record will result in failed foreign key constraints. If this record is invalid,
             * saving it here will avoid those errors.
             */
            $subject->save();

            foreach ($subject->UserRole as $role) {
                $role->unlink('Organizations');
            }
            $subject->save();
            $subject->unlink('Roles');
            $subject->save();

            foreach ($rolesOrganizations as $role => $organizations) {
                $userRole = new UserRole();
                $userRole->userId = (int) $subject->id;
                $userRole->roleId = (int) $role;
                $userRole->save();

                foreach ($organizations as $organization) {
                    $userRoleOrganization = new UserRoleOrganization();
                    $userRoleOrganization->organizationId = (int) $organization;
                    $userRoleOrganization->userRoleId = (int) $userRole->userRoleId;
                    $userRoleOrganization->save();
                    $userRoleOrganization->free();
                    unset($userRoleOrganization);
                }

                $userRole->free();
                unset($userRole);
            }

            if ($subject->{'deleted_at'}) {
                $subject->{'deleted_at'} = null;
                $subject->lastRob = null;
                $subject->save();
            }

            $conn->commit();

            // Just send out email when create a new account or change password by admin user,
            // and it does not sent out email when the root user changes his own password.
            $mail = new Mail();
            $mail->recipient     = $subject->email;
            $mail->recipientName = $subject->nameFirst . ' ' . $subject->nameLast;
            $systemName = Fisma::configuration()->getConfig('system_name');
            if ('create' === $actionName) {
                $options = array(
                    'systemName' => $systemName,
                    'username' => $subject->username,
                    'plainTextPassword' => $subject->plainTextPassword,
                    'authType' => Fisma::configuration()->getConfig('auth_type')
                );

                $mail->subject = "Your new account for $systemName has been created";
                $mail->mailTemplate('send_account_info', $options);

                Zend_Registry::get('mail_handler')->setMail($mail)->send();
                $defaultActiveEvents = Doctrine::getTable('Event')->findByDefaultActive(true);
                $subject->Events->merge($defaultActiveEvents);
                $subject->save();
            } else if ('view' === $actionName
                       && !empty($values['password'])
                       && ('root' !== $subject->username || $this->_me->username !== 'root')) {

                $options = array(
                    'systemName' => $systemName,
                    'plainTextPassword' => $subject->plainTextPassword,
                    'host' => Fisma_Url::baseUrl()
                );

                $mail->subject = "Your password for $systemName has been changed";
                $mail->mailTemplate('send_password', $options);

                Zend_Registry::get('mail_handler')->setMail($mail)->send();
            }

            // do not need to audit log root user's role or organization
            if ('edit' === $actionName && ($roleChanged || $organizationChanged) && 'root' !== $subject->username) {
                $auditLog = $roleChanged ? 'Updated Role.' : 'Updated Organization.';
                $subject->getAuditLog()->write($auditLog);
            }
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $subject;
    }

    /**
     * Get the Roles and the organization from the model and assign them to the form
     *
     * @param Doctrine_Record|null $subject The specified subject
     * @param Zend_Form $form The specified form to set
     * @return Zend_Form The processed form
     */
    protected function setForm($subject, $form)
    {
        $roles = array();
        $assignedRoles = $subject->Roles->toArray();

        foreach ($assignedRoles as $assignedRole) {
            $roles[] = $assignedRole['id'];
        }

        $form->setDefaults($subject->toArray());
        $form->getElement('role')->setValue($roles);

        return $form;
    }

    /**
     * Show audit logs for a given user
     *
     * @GETAllowed
     */
    public function logAction()
    {
        $id = $this->getRequest()->getParam('id');

        $user = Doctrine::getTable('User')->find($id);

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $this->view->username = $user->username;
        $this->view->columns = array('Timestamp', 'User', 'Message');
        $this->view->viewLink = "/user/view/id/$id$fromSearchUrl";

        $logs = $user->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);

        $logRows = array();

        foreach ($logs as $log) {
            $logRows[] = array(
                'timestamp' => $log['o_createdTs'],
                'user' => $log['u_id'] ? $this->view->userInfo($log['u_displayName'], $log['u_id']) : '',
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
     * Display the user's "Edit Profile" page and handle its updating
     *
     * @GETAllowed
     * @return void
     */
    public function profileAction()
    {
        $form = $this->_getProfileForm();
        $user = Doctrine::getTable('User')->find($this->_me->id);
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                $user->merge($form->getValues());
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    $modified = $user->getModified();
                    $user->save();
                    Doctrine_Manager::connection()->commit();
                    $message = "Profile updated successfully";
                    $model   = 'notice';
                    if (CurrentUser::getInstance()->id === $user->id) {
                        CurrentUser::getInstance()->refresh();
                    }
                } catch (Doctrine_Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    $message = $e->getMessage();
                    $model   = 'warning';
                }
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                $message     = "Unable to update profile:<br>" . $errorString;
                $model       = 'warning';
            }
            $this->view->priorityMessenger($message, $model);
            $this->_redirect('/user/profile');
        } else {
            $form->setDefaults($user->toArray());
        }
        $this->view->form    = Fisma_Zend_Form_Manager::prepareForm($form);
    }

    /**
     * Change user's password
     *
     * @GETAllowed
     * @return void
     */
    public function passwordAction()
    {
        // This action isn't allowed unless the system's authorization is based on the database
        if (
            'database' != Fisma::configuration()->getConfig('auth_type') &&
            'root' != CurrentUser::getInstance()->username
        ) {
            throw new Fisma_Zend_Exception(
                'Password change is not allowed when the authentication type is not "database"'
            );
        }

        // Load the change password file
        $form = Fisma_Zend_Form_Manager::loadForm('change_password');
        $form = Fisma_Zend_Form_Manager::prepareForm($form);

        $this->view->requirements =  $this->_helper->passwordRequirements();
        $post   = $this->_request->getPost();

        if (isset($post['oldPassword'])) {
            if ($form->isValid($post)) {
                $user = CurrentUser::getInstance();
                try {
                    $user->mustResetPassword = false;
                    $user->merge($post);
                    $user->save();
                    $message = "Password updated successfully.";
                    $model   = 'notice';
                    if ($this->_helper->ForcedAction->hasForcedAction($user->id, 'mustResetPassword')) {

                        // Remove the forced action of mustResetPassword from session, and send users to
                        // their original requested page or dashboard otherwise.
                        $this->_helper->ForcedAction->unregisterForcedAction($user->id, 'mustResetPassword');

                        $session = Fisma::getSession();
                        if (isset($session->redirectPage) && !empty($session->redirectPage)) {
                            $path = $session->redirectPage;
                            unset($session->redirectPage);
                            $this->_response->setRedirect($path);
                        } else {
                            $this->_redirect('/index/index');
                        }
                    }
                } catch (Doctrine_Exception $e) {
                    $message = $e->getMessage();
                    $model   = 'warning';
                }
            } else {
                $errorString = Fisma_Zend_Form_Manager::getErrors($form);
                $message     = "Unable to change password:<br>" . $errorString;
                $model       = 'warning';
            }
            $this->view->priorityMessenger($message, $model);
            $this->_redirect('/user/password');
        }
        $this->view->form    =  $form;

        $buttons = array();
        $buttons['submitButton'] = new Fisma_Yui_Form_Button(
            'saveChanges',
            array(
                'label' => 'Save',
                'onClickFunction' => 'Fisma.Util.submitFirstForm',
                'imageSrc' => '/images/ok.png'
            )
        );
        $buttons['discardButton'] = new Fisma_Yui_Form_Button_Link(
            'discardChanges',
            array(
                'value' => 'Discard',
                'imageSrc' => '/images/no_entry.png',
                'href' => '/user/password'
            )
        );
        $this->view->toolbarButtons = $buttons;
    }

    /**
     * Set user's notification policy
     *
     * @GETAllowed
     * @return void
     */
    public function notificationAction()
    {
        $user = Doctrine::getTable('User')->find($this->_me->id);

        if ($this->_request->isPost()) {
            $postEvents = $this->_request->getPost('event');
            try {
                Doctrine_Manager::connection()->beginTransaction();

                $user->unlink('Events');
                if (!empty($postEvents)) {
                    $user->link('Events', array_keys($postEvents));
                }
                $user->save();
                Doctrine_Manager::connection()->commit();

                $message = "Notification events modified successfully";
                $model   = 'notice';
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                $message = $e->getMessage();
                $model   = 'warning';
            }
            $this->view->priorityMessenger($message, $model);
            $this->_redirect('/user/notification');
        }

        if ($this->_acl->hasPrivilegeForClass('admin', 'Notification')) {
            $this->view->adminEvents = Doctrine::getTable('Event')->findByCategory('admin');
        }

        $this->view->userEvents = Doctrine::getTable('Event')->findByCategory('user');

        if ($this->_acl->hasPrivilegeForClass('asset', 'Notification')) {
            $this->view->inventoryEvents = Doctrine::getTable('Event')->findByCategory('inventory');
        }

        if ($this->_acl->hasPrivilegeForClass('finding', 'Notification')) {
            $this->view->findingEvents = Doctrine::getTable('Event')->findByCategory('finding');
            $this->view->evaluationEvents = Doctrine::getTable('Event')->findByCategory('evaluation');
        }

        if ($this->_acl->hasPrivilegeForClass('incident', 'Notification')) {
            $this->view->incidentEvents = Doctrine::getTable('Event')->findByCategory('incident');
        }

        if ($this->_acl->hasPrivilegeForClass('vulnerability', 'Notification')) {
            $this->view->vulnerabilityEvents = Doctrine::getTable('Event')->findByCategory('vulnerability');
        }

        $this->view->user = $user;
        $this->view->toolbar = $this->getToolbarButtons();
    }

    /**
     * Store user last accept rob and create a audit event
     *
     * @GETAllowed
     * @return void
     */
    public function acceptRobAction()
    {
        $this->_helper->layout->setLayout('notice');

        $post   = $this->_request->getPost();
        if (isset($post['accept'])) {
            $user = CurrentUser::getInstance();
            $user->lastRob = Fisma::now();
            $user->save();

            if ($this->_helper->ForcedAction->hasForcedAction($user->id, 'rulesOfBehavior')) {
                $this->_helper->ForcedAction->unregisterForcedAction($user->id, 'rulesOfBehavior');
            }

            $this->_helper->layout->setLayout('layout');
            $this->_redirect('/Index/index');
        }

        $this->view->behaviorRule = Fisma::configuration()->getConfig('behavior_rule');
    }

    /**
     * Override parent to add an audit log link
     *
     * @param Fisma_Doctrine_Record $subject
     */
    public function getViewLinks(Fisma_Doctrine_Record $subject)
    {
        $links = array();

        if ($this->_acl->hasPrivilegeForObject('read', $subject)) {
            $links['Audit Log'] = "/user/log/id/{$subject->id}";
        }

        $links['Comments'] = "/user/comments/id/{$subject->id}";

        $links = array_merge($links, parent::getViewLinks($subject));

        return $links;
    }

    /**
     * Displays user info in a small pop-up box. No layout.
     *
     * @GETAllowed
     */
    public function infoAction()
    {
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');

        if ($id) {
            $user = Doctrine::getTable('User')->find($id);
        } else {
            $user = null;
        }

        $this->view->user = $user;
    }

    /**
     * Retrieve the organization subform
     *
     * @GETAllowed
     * @return void
     */
    public function getOrganizationSubformAction()
    {
        $this->_helper->layout()->setLayout('ajax');

        $userId = $this->getRequest()->getParam('user');
        $roleId = $this->getRequest()->getParam('role');
        $readOnly = $this->getRequest()->getParam('readOnly');

        $user = Doctrine::getTable('User')->find($userId);

        $assignedOrgs = array();

        if (!empty($user)) {
            $userOrgs = $user->getOrganizationsByRole($roleId);

            foreach ($userOrgs as $userOrg) {
                $assignedOrgs[] = $userOrg->id;
            }
        }

        $subForm = new Zend_Form_SubForm();
        $subForm->removeDecorator('DtDdWrapper');
        $subForm->removeDecorator('HtmlTag');

        $organizations = new Fisma_Zend_Form_Element_CheckboxTree("organizations");
        $organizations->clearDecorators();
        $organizations->setLabel('Organizations & Information Systems');

        $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
        $q = Doctrine_Query::create()
                ->select('o.id, o.name, o.level')
                ->from('Organization o');
        $organizationTreeObject->setBaseQuery($q);
        $organizationTree = $organizationTreeObject->fetchTree();

        if (!empty($organizationTree)) {
            foreach ($organizationTree as $organization) {
                $organizations->addCheckbox(
                    $organization['id'],
                    $organization['nickname'] . ' - ' . $organization['name'],
                    $organization['level'],
                    $roleId
                );
            }
        }

        $organizations->setValue($assignedOrgs);

        $organizations->readOnly = (boolean) $readOnly;

        $subForm->addElement($organizations);

        $this->view->subForm = $subForm;
    }

    /**
     * TabView
     *
     * @GETAllowed
     */
    public function viewAction()
    {
        $id = $this->_request->getParam('id');
        $subject = $this->_getSubject($id);
        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $tabView = new Fisma_Yui_TabView('FindingView', $id);

        $commentCount = '<span id=\'commentsCount\'>' . $this->_getSubject($id)->getComments()->count() . '</span>';
        $tabView->addTab("User $id", "/user/user/id/$id/format/html");
        $tabView->addTab("Comments ($commentCount)", "/user/comments/id/$id/format/html");
        $tabView->addTab("Audit Log", "/user/log/id/$id/format/html");

        $this->view->tabView = $tabView;

        $viewButtons = $this->getViewButtons($subject);
        $toolbarButtons = $this->getToolbarButtons($subject, $fromSearchParams);
        $searchButtons = $this->getSearchButtons($subject, $fromSearchParams);
        $buttons = array_merge($toolbarButtons, $viewButtons);
        $this->view->modelName = $this->getSingularModelName();
        $this->view->toolbarButtons = $toolbarButtons;
        $this->view->searchButtons = $searchButtons;
    }
    /**
     * Show user details
     *
     * @GETAllowed
     * @return void
     */
    public function userAction()
    {
        $id = $this->getRequest()->getParam('id');
        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
        $subject = Doctrine::getTable('User')->find($id);

        $tabView = new Fisma_Yui_TabView('UserView');

        $q = Doctrine_Query::create()
            ->from('User u')
            ->leftJoin('u.Roles r')
            ->where('u.id = ?', $id);

        $user = $q->fetchArray();

        $subject = $this->_getSubject($id);
        if (!$this->_enforceAcl || $this->_acl->hasPrivilegeForObject('update', $subject)) {
            $readOnly = 0;
        } else {
            $readOnly = 1;
        }

        if (isset($user[0]['Roles'])) {
            foreach ($user[0]['Roles'] as $role) {
                $tabView->addTab(
                    $this->view->escape($role['nickname']),
                    "/User/get-organization-subform/user/{$id}/role/{$role['id']}/readOnly/$readOnly",
                    $role['id'],
                    $readOnly == 0 ? 'true' : 'false'
                );
            }
        }

        $roles = Doctrine_Query::create()
            ->select('r.id, r.nickname')
            ->from('Role r')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();

        $fromSearchParams = $this->_getFromSearchParams($this->getRequest());
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $this->view->fromSearchUrl = $fromSearchUrl;
        $this->view->auditLogLink = "/user/log/id/$id$fromSearchUrl";
        $this->view->commentLink = "/user/comments/id/$id$fromSearchUrl";
        $this->view->tabView = $tabView;
        $this->view->roles = Zend_Json::encode($roles);

        parent::_viewObject();

        $this->view->form->removeDecorator('Fisma_Zend_Form_Decorator');
    }

    /**
     * Override parent method
     *
     * @GETAllowed
     * @return void
     */
    public function createAction()
    {
        // Manually handling re-creation of deleted user
        $manuallyOveride = false;
        if ($this->_request->isPost()) {
            if ($username = $this->_request->getPost('username')) {
                $subject = Doctrine::getTable('User')->findOneByUsername($username);
                if ($subject && $subject->{'deleted_at'}) {
                    $id = $subject->id;

                    // recycle old account and update it
                    $this->_request->setParam('id', $id);
                    if ($this->_viewObject()) {
                        $this->_redirect('/user/view/id/' . $subject->id);
                    } else {
                        $manuallyOveride = true;
                    }
                }
            }
        }

        $tabView = new Fisma_Yui_TabView('UserView');

        $roles = Doctrine_Query::create()
            ->select('r.id, r.nickname')
            ->from('Role r')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();
        $this->view->roles = Zend_Json::encode($roles);
        $this->view->tabView = $tabView;
        parent::_createObject($manuallyOveride);
        $this->view->form->removeDecorator('Fisma_Zend_Form_Decorator');

        $id = $this->getRequest()->getParam('id');
        if (!empty($id)) {
            $user = Doctrine::getTable('User')->find($id);
            $this->view->form->setDefaults($user->toArray());
        }
    }

    /**
     * Generate a password that meet the application's password complexity requirements.
     *
     * @GETAllowed
     * @return void
     */
    public function generatePasswordAction()
    {
        $passLengthMin = Fisma::configuration()->getConfig('pass_min_length');
        $passNum = Fisma::configuration()->getConfig('pass_numerical');
        $passUpper = Fisma::configuration()->getConfig('pass_uppercase');
        $passLower = Fisma::configuration()->getConfig('pass_lowercase');
        $passSpecial = Fisma::configuration()->getConfig('pass_special');

        $flag = 0;
        $password = "";
        $length = 2 * $passLengthMin;

        if (true == $passUpper) {
            $possibleCharactors[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $flag++;
        }

        if (true == $passLower) {
            $possibleCharactors[] = "abcdefghijklmnopqrstuvwxyz";
            $flag++;
        }

        if (true == $passNum) {
            $possibleCharactors[] = "0123456789";
            $flag++;
        }

        if (true == $passSpecial) {
            $possibleCharactors[] = "!@#$%^&*()_+=-`~\|':;?><,.[]{}/";
            $flag++;
        }

        while (strlen($password) < $length) {
            if (0 == $flag) {
                $password .= rand(0, 9);
            } else {
                foreach ($possibleCharactors as $row) {
                    if (strlen($password) < $length) {
                        $password .= substr($row, (rand()%(strlen($row))), 1);
                    }
                }
            }
        }
        echo $password;
        $this->_helper->layout->disableLayout(true);
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Autocomplete provider for LDAP
     *
     * @GETAllowed
     * @return void
     */
    public function ldapAutocompleteAction()
    {
        if (!$this->_acl->hasPrivilegeForClass('read', 'User')) {
            throw new Fisma_Zend_Exception_InvalidPrivilege("User does not have privileges to check account.");
        }

        $query = $this->getRequest()->getParam('query');
        if (empty($query)) {
            throw new Fisma_Zend_Exception_User('No query provided.');
        }

        $ldap = new Fisma_Ldap(LdapConfig::getConfig());
        $results = $ldap->lookup($query);
        foreach ($results as &$r) {
            $r['givenname'] = empty($r['givenname'][0]) ? '' : $r['givenname'][0];
            $r['sn'] = empty($r['sn'][0]) ? '' : $r['sn'][0];
            $r['mail'] = empty($r['mail'][0]) ? '' : $r['mail'][0];

            if (!empty($r['uid'][0])) {
                $r['username'] = $r['uid'][0];
            } else if (!empty($r['samaccountname'][0])) {
                $r['username'] = $r['samaccountname'][0];
            } else {
                $r['username'] = '';
            }
            unset($r['uid']);
            unset($r['samaccountname']);

            $r['label'] = trim($r['givenname'] . ' ' . $r['sn']);
            if (!empty($r['username'])) {
                $r['label'] = trim($r['label'] . ' (' . $r['username'] . ')');
            }
            if (!empty($r['mail'])) {
                $r['label'] = trim($r['label'] . ' <' . $r['mail'] . '>');
            }
        }
        $this->view->results = $results;
    }

    /**
     * Add a comment to a specified user
     *
     */
    public function addCommentAction()
    {
        $id = $this->getRequest()->getParam('id');
        $user = Doctrine::getTable('User')->find($id);

        $comment = $this->getRequest()->getParam('comment');

        if ('' != trim(strip_tags($comment))) {
            $user->getComments()->addComment($comment);
        } else {
            $this->view->priorityMessenger('Comment field is blank', 'warning');
        }

        $this->_redirect("/user/comments/id/$id");
    }

    /**
     * Displays the user comment interface
     *
     * @GETAllowed
     * @return void
     */
    function commentsAction()
    {
        $id = $this->_request->getParam('id');
        $user = Doctrine::getTable('User')->find($id);
        if (!$user) {
            throw new Fisma_Zend_Exception("Invalid User ID");
        }

        $comments = $user->getComments()->fetch(Doctrine::HYDRATE_ARRAY);

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

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);

        $this->view->username = $user->username;
        $this->view->viewLink = "/user/view/id/$id$fromSearchUrl";

        $commentButton = new Fisma_Yui_Form_Button(
            'commentButton',
            array(
                'label' => 'Add Comment',
                'onClickFunction' => 'Fisma.Commentable.showPanel',
                'onClickArgument' => array(
                    'id' => $id,
                    'type' => 'User',
                    'callback' => array(
                        'object' => 'User',
                        'method' => 'commentCallback'
                    )
                )
            )
        );

        $this->view->commentButton = $commentButton;
    }

    /**
     * getUsersAction
     *
     * @GETAllowed
     * @access public
     * @return void
     */
    public function getUsersAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'User');

        $query = $this->getRequest()->getParam('query');

        $users = Doctrine::getTable('User')->getUsersLikeUsernameQuery($query)
                 ->select('u.id, u.username')
                 ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                 ->execute();

        $list = array('users' => $users);

        return $this->_helper->json($list);
    }

    /**
     * removeUserRolesAction
     *
     * @access public
     * @return void
     */
    public function removeUserRolesAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_acl->requirePrivilegeForClass('update', 'User');

        $organizationId = $this->getRequest()->getParam('organizationId');
        $userRoles = $this->getRequest()->getParam('userRoles');

        Doctrine_Manager::connection()->beginTransaction();

        $urosToDelete = Doctrine::getTable('UserRoleOrganization')
                        ->getByOrganizationIdAndUserRoleIdQuery($organizationId, $userRoles)
                        ->execute();

        foreach ($urosToDelete as $uro) {
            $uro->delete();
            $uro->free();
        }

        Doctrine_Manager::connection()->commit();
    }

    /**
     * addUserRolesToOrganizationAction
     *
     * @access public
     * @return void
     */
    public function addUserRolesToOrganizationAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_acl->requirePrivilegeForClass('update', 'User');

        $organizationId = $this->getRequest()->getParam('organizationId');
        $userRoles = $this->getRequest()->getParam('userRoles');

        Doctrine_Manager::connection()->beginTransaction();

        Doctrine::getTable('UserRoleOrganization')
        ->getByOrganizationIdAndUserRoleIdQuery($organizationId, $userRoles)
        ->execute()
        ->delete();

        foreach ($userRoles as $userRole) {
            $userRoleOrganization = new UserRoleOrganization();
            $userRoleOrganization->organizationId = (int) $organizationId;
            $userRoleOrganization->userRoleId = (int) $userRole;
            $userRoleOrganization->save();
        }

        Doctrine_Manager::connection()->commit();
    }

    protected function _isDeletable()
    {
        return false;
    }

    /**
     * Override to fill in option values for the select elements, etc.
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);
        $passwordRequirements = new Fisma_Zend_Controller_Action_Helper_PasswordRequirements();

        if ('create' == $this->_request->getActionName()) {
            $form->getElement('password')->setRequired(true);
        }
        $roles  = Doctrine_Query::create()
                    ->select('*')
                    ->from('Role')
                    ->orderBy('nickname')
                    ->execute();
        foreach ($roles as $role) {
            $form->getElement('role')->addMultiOptions(array($role->id => $role->nickname . ' - ' . $role->name));
        }

        // Show lock explanation if account is locked. Hide explanation otherwise.
        $userId = $this->_request->getParam('id');
        $user = Doctrine::getTable('User')->find($userId);

        if ('database' == Fisma::configuration()->getConfig('auth_type')) {
            $form->removeElement('lookup');
            $form->removeElement('separator');
            $this->view->requirements =  $passwordRequirements->direct();
        } else {
            $form->removeElement('password');
            $form->removeElement('confirmPassword');
            $form->removeElement('generate_password');

            // root user should always show Must Reset Password
            if (empty($user) || ($user && 'root' != $user->username)) {
                $form->removeElement('mustResetPassword');
            }
        }

        if ($user && $user->locked) {
            $reason = $user->getLockReason();
            $form->getElement('lockReason')->setValue($reason);

            $lockTs = new Zend_Date($user->lockTs, Zend_Date::ISO_8601);
            $form->getElement('lockTs')->setValue($lockTs->get(Fisma_Date::FORMAT_DATETIME));
        } else {
            $form->removeElement('lockReason');
            $form->removeElement('lockTs');
        }

        // Populate <select> for responsible organization
        $organizations = Doctrine::getTable('Organization')->getOrganizationSelectQuery(true)->execute();
        $selectArray = array('' => '') + $this->view->systemSelect($organizations);
        $form->getElement('reportingOrganizationId')->addMultiOptions($selectArray);

        return $form;
    }

    /**
     * Check whether the roles have been changed by comparing the roleIds between original and postForm
     *
     * @param $originalData an array of role ids generated from database
     * @param $postData an array of role ids posted from form
     * @return true if two arrays are not equal, otherwise false.
     */
    protected function _isRoleChanged($originalData, $postData)
    {
        if (count($originalData) != count($postData)) {
            return true;
        }
        sort($originalData);
        sort($postData);
        if ($originalData != $postData) {
            return true;
        }
        return false;
    }

    /**
     * Check whether the roles organizations have been changeds
     *
     * Compare the organizations within each roleId between original data gotten from DB and post data from post form
     *
     * @param $originalData an array with key of roleId and value of array of organizationIds generated from database.
     * @param $postData an array with key of roleId and value of array of organizationIds posted from form.
     * @return true if two arrays are not equal, otherwise false.
     */
    protected function _isRolesOrganizationsChanged($originalData, $postData)
    {
        // $postData can be empty when admin does not assign any organization to each role.
        if (empty($postData) && !empty($originalData)) {
            return true;
        } elseif (!empty($postData) && empty($originalData)) {
            return true;
        } elseif (empty($postData) && empty($originalData)) {
            return false;
        }

        sort($originalData);
        sort($postData);

        // Compare two arrays of organizationIds in each role.
        for ($i = 0; $i < count($originalData); $i++) {

            // Do not need to sort and compare original and posted data if one has data and the other one doesn't.
            if (isset($originalData[$i]) Xor isset($postData[$i])) {
                return true;
            }

            sort($originalData[$i]);
            sort($postData[$i]);
            if ($originalData[$i] != $postData[$i]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add the "Convert to Contact" button
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $first = null, $last = null)
    {
        $buttons = array();

        if ($this->_request->getActionName() === 'notification') {
            $buttons['save'] = new Fisma_Yui_Form_Button_Submit(
                'saveChanges',
                array(
                    'label' => 'Save',
                    'imageSrc' => '/images/ok.png'
                )
            );
            $buttons['discard'] =  new Fisma_Yui_Form_Button_Link(
                'discardChanges',
                array(
                    'value' => 'Discard',
                    'imageSrc' => '/images/no_entry.png',
                    'href' => "/user/notification"
                )
            );
            return $buttons;
        }

        if ($this->_acl->hasPrivilegeForClass('read', $this->getAclResourceName())) {
            if ($this->getRequest()->getActionName() === 'list') {
                $buttons['tree'] = new Fisma_Yui_Form_Button_Link(
                    'pocTreeButton',
                    array(
                        'value' => 'Tree View',
                        'href' => $this->getBaseUrl() . '/tree',
                        'imageSrc' => '/images/tree_view.png'
                    )
                );
            }
            if ($this->getRequest()->getActionName() === 'tree') {
                $buttons['list'] = new Fisma_Yui_Form_Button_Link(
                    'pocListButton',
                    array(
                        'value' => 'List View',
                        'href' => $this->getBaseUrl() . '/list',
                        'imageSrc' => '/images/list_view.png'
                    )
                );
            }
        }

        $buttons = array_merge($buttons, parent::getToolbarButtons($record));

        if (!empty($record) && $this->_acl->hasPrivilegeForObject('delete', $record)) {
            if ($this->getRequest()->getActionName() === 'view') {
                $fromSearchParams = $this->_getFromSearchParams($this->_request);
                $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
                $buttons['delete'] = new Fisma_Yui_Form_Button(
                    'deleteButton',
                    array(
                        'label' => 'Delete',
                        'onClickFunction' => 'Fisma.User.deleteUser',
                        'onClickArgument' => array(
                            'link'  => "/user/delete$fromSearchUrl",
                            'id' => $record->id
                        ),
                        'imageSrc' => '/images/trash_recyclebin_empty_closed.png'
                    )
                );
            }
        }

        return $buttons;
    }

    /**
     * A helper action for autocomplete text boxes
     *
     * @GETAllowed
     */
    public function autocompleteAction()
    {
        $keyword = $this->getRequest()->getParam('keyword');
        $expr = 'u.nameFirst LIKE ? OR u.nameLast LIKE ? OR u.email LIKE ? OR u.username LIKE ?';
        $params = array_fill(0, 4, '%' . $keyword . '%');

        $query = Doctrine_Query::create()
                    ->from('User u')
                    ->select("u.id, u.nameFirst, u.nameLast, u.username, u.email")
                    ->where($expr, $params)
                    ->andWhere('(u.lockType IS NULL OR u.lockType <> ?)', 'manual')
                    ->andWhere('u.published')
                    ->orderBy("u.nameFirst, u.nameLast, u.username, u.email")
                    ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $users = $query->execute();
        foreach ($users as &$poc) {
            $poc['name'] = $poc['nameFirst'] . ' ' . $poc['nameLast'] . ' ';
            if (!empty($poc['username'])) {
                $poc['name'] .= '(' . $poc['username'] . ') ';
            }
            $poc['name'] .= '<' . $poc['email'] . '>';
            $poc['name'] = trim(preg_replace('/\s+/', ' ', $poc['name']));
            unset($poc['nameFirst'], $poc['nameLast'], $poc['username'], $poc['email']);
        }

        $this->view->pointsOfContact = $users;
    }

    /**
     * Display organizations and Contacts in tree mode for quick restructuring of the
     * Contact hierarchy.
     *
     * @GETAllowed
     */
    public function treeAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'User');

        $this->view->toolbarButtons = $this->getToolbarButtons();

        $this->view->csrfToken = $this->_helper->csrf->getToken();

        // We're already on the tree screen, so don't show a "view tree" button
        unset($this->view->toolbarButtons['tree']);
    }

    /**
     * Returns a JSON object that describes the Contact tree
     *
     * @GETAllowed
     */
    public function treeDataAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'User');

        $this->view->treeData = $this->_getUserTree();
    }

    /**
     * Gets the organization tree for the current user.
     *
     * @return array The array representation of organization tree
     */
    protected function _getUserTree()
    {
        // Get a list of Contacts
        $pocQuery = Doctrine_Query::create()
                    ->select('u.id, u.username, u.nameFirst, u.nameLast, u.reportingOrganizationId')
                    ->from('User u')
                    ->orderBy('u.reportingOrganizationId, u.username')
                    ->where('u.reportingOrganizationId IS NOT NULL')
                    ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $pocs = $pocQuery->execute();

        // Group Contacts by organization ID
        $pocsByOrgId = array();

        foreach ($pocs as $poc) {
            $orgId = $poc['u_reportingOrganizationId'];

            if (isset($pocsByOrgId[$orgId])) {
                $pocsByOrgId[$orgId][] = $poc;
            } else {
                $pocsByOrgId[$orgId] = array($poc);
            }
        }

        // Get a tree of organizations
        $orgBaseQuery = Doctrine_Query::create()
                        ->from('Organization o')
                        ->select('o.name, o.nickname, ot.nickname, s.type, s.sdlcPhase')
                        ->leftJoin('o.OrganizationType ot')
                        ->where('ot.nickname <> ?', 'system')
                        ->orderBy('o.lft');

        $orgTree = Doctrine::getTable('Organization')->getTree();
        $orgTree->setBaseQuery($orgBaseQuery);
        $organizations = $orgTree->fetchTree();
        $orgTree->resetBaseQuery();

        // Merge organizations and Contacts and return.
        $organizationTree = $this->toHierarchy($organizations, $pocsByOrgId);

        return $organizationTree;
    }

    /**
     * Transform the flat array returned from Doctrine's nested set into a nested array
     *
     * Doctrine should provide this functionality in a future
     *
     * @param Doctrine_Collection $collection The collection of organization record to hierarchy
     * @param array $pocsByOrgId Nested array of Contacts indexed by the Contacts' reporting organization ID
     * @return array The array representation of organization tree
     * @todo review the need for this function in the future
     */
    public function toHierarchy($collection, $pocsByOrgId)
    {
        // Trees mapped
        $trees = array();
        $l = 0;

        // Ensure collection is a tree
        if (!empty($collection)) {
            // Node Stack. Used to help building the hierarchy
            $rootLevel = $collection[0]->level;

            $stack = array();
            foreach ($collection as $node) {
                $item = ($node instanceof Doctrine_Record) ? $node->toArray() : $node;
                $item['level'] -= $rootLevel;
                $item['label'] = $item['nickname'] . ' - ' . $item['name'];
                $item['orgType'] = $node->getType();
                $item['iconId'] = $node->getIconId();
                $item['orgTypeLabel'] = $node->getOrgTypeLabel();
                $item['children'] = array();

                // Merge in any Contacts that report to this organization
                if (isset($pocsByOrgId[$node->id])) {
                    $item['children'] += $pocsByOrgId[$node->id];
                }

                // Number of stack items
                $l = count($stack);
                // Check if we're dealing with different levels
                while ($l > 0 && $stack[$l - 1]['level'] >= $item['level']) {
                    array_pop($stack);
                    $l--;
                }

                if ($l != 0) {
                    if ($node->getNode()->getParent()->name == $stack[$l-1]['name']) {
                        // Add node to parent
                        $i = count($stack[$l - 1]['children']);
                        $stack[$l - 1]['children'][$i] = $item;
                        $stack[] = & $stack[$l - 1]['children'][$i];
                    } else {
                        // Find where the node belongs
                        for ($j = $l; $j >= 0; $j--) {
                            if ($j == 0) {
                                $i = count($trees);
                                $trees[$i] = $item;
                                $stack[] = &$trees[$i];
                            } elseif ($node->getNode()->getParent()->name == $stack[$j-1]['name']) {
                                // Add node to parent
                                $i = count($stack[$j-1]['children']);
                                $stack[$j-1]['children'][$i] = $item;
                                $stack[] = &$stack[$j-1]['children'][$i];
                                break;
                            } elseif ($node->getNode()->getLevel() > 1) {

                                // Find the node's organization parent when its parent is a system.
                                $parent = $this->_getOrganizationParent($node->getNode());

                                if ($parent && $parent->name == $stack[$j-1]['name']) {
                                    // Add node to parent
                                    $i = count($stack[$j-1]['children']);
                                    $stack[$j-1]['children'][$i] = $item;
                                    $stack[] = &$stack[$j-1]['children'][$i];
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Assigning the root node
                    $i = count($trees);
                    $trees[$i] = $item;
                    $stack[] = &$trees[$i];
                }
            }
        }

        return $trees;
    }

    /**
     * Get the nearest ancestor with organization type.
     *
     * @param Doctrine_Record $node The nested node.
     * @return mixed Doctrine_Record if found, otherwise false.
     */
    private function _getOrganizationParent($node)
    {
        $ancestors = $node->getAncestors();
        if ($ancestors) {
            for ($i = count($ancestors) - 1; $i >= 0; $i--) {
                if (is_null($ancestors[$i]->systemId)) {
                    return $ancestors[$i];
                }
            }
        }

        return false;
    }

    /**
     * Moves a Contact node from one organization to another.
     *
     * This is used by the YUI tree node to handle drag and drop of organization nodes. It replies with a JSON object.
     */
    public function moveNodeAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $response = new Fisma_AsyncResponse;

        // Find the source and destination objects from the tree
        $srcId = $this->getRequest()->getParam('src');
        $src = Doctrine::getTable('User')->find($srcId);

        $destId = $this->getRequest()->getParam('dest');
        if ($destId) {
            $dest = Doctrine::getTable('User')->find($destId);
            $destOrg = $dest->ReportingOrganization;
        } else {
            $destId = $this->getRequest()->getParam('destOrg');
            $destOrg = Doctrine::getTable('Organization')->find($destId);
        }

        if ($src && $destOrg) {
            $src->ReportingOrganization = $destOrg;
            $src->save();
        } else {
            $response->fail("Invalid src, dest or destOrg parameter ($srcId, $destId, $destOrgId)");
        }

        print Zend_Json::encode($response);
    }

    /**
     * Delete a user if not associated with any open findings / incidents / organizations / or systems
     *
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
        $subject = $this->_getSubject($id);

        if (!$subject) {
            throw new Fisma_Zend_Exception_User("No active user found with id #{$id}.");
        }

        $messages = array();

        // Check for deleting root or self
        if ($subject->username === 'root') {
            throw new Fisma_Zend_Exception_User("Root account cannot be deleted.");
        } else if ($subject->username === CurrentUser::getAttribute('username')) {
            throw new Fisma_Zend_Exception_User("You cannot delete yourself.");
        }

        // Check for open findings
        $findingCount = Doctrine_Query::create()
            ->from('Finding f')
            ->where('f.pocId = ?', $id)
            ->andWhere('f.status <> ?', 'CLOSED')
            ->andWhere('f.deleted_at is NULL')
            ->count();
        if ($findingCount > 0) {
            $messages[] = "<a href='/finding/remediation/list?q=/denormalizedStatus/enumIsNot/CLOSED/pocUser"
                        . "/textExactMatch/" . $subject->displayName . "' target='_blank'>"
                        . $findingCount . ' open finding' . (($findingCount > 1) ? 's' : '')
                        . "</a>";
        }
        // Check for open incidents
        $incidentCount = Doctrine_Query::create()
            ->from('Incident i')
            ->where('i.pocId = ?', $id)
            ->andWhere('i.status <> ?', 'closed')
            ->andWhere('i.deleted_at is NULL')
            ->count();
        if ($incidentCount > 0) {
            $messages[] = "<a href='/incident/list?q=/status/enumIsNot/closed/pocUser/textExactMatch/"
                        . $subject->displayName . "' target='_blank'>"
                        . $incidentCount . ' open incident' . (($incidentCount > 1) ? 's' : '')
                        . "</a>";
        }
        // Check for organizatinos
        $organizationCount = Doctrine_Query::create()
            ->from('Organization o')
            ->leftJoin('o.OrganizationType ot')
            ->where('o.pocId = ?', $id)
            ->andWhere('ot.nickname <> ?', 'system')
            ->count();
        if ($organizationCount > 0) {
            $messages[] = "<a href='/organization/list?q=/pocUser/textExactMatch/"
                        . $subject->displayName . "' target='_blank'>"
                        . $organizationCount . ' organization' . (($organizationCount > 1) ? 's' : '')
                        . "</a>";
        }
        // Check for active systems
        $systemCount = Doctrine_Query::create()
            ->from('Organization o')
            ->innerJoin('o.System s')
            ->where('o.pocId = ?', $id)
            ->andWhere('s.sdlcPhase <> ?', 'disposal')
            ->count();
        if ($systemCount > 0) {
            $messages[] = "<a href='/system/list?q=/pocUser/textExactMatch/"
                        . $subject->displayName . "' target='_blank'>"
                        . $systemCount . ' system' . (($systemCount > 1) ? 's' : '')
                        . "</a>";
        }

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($subject->UserRole as $role) {
                $role->unlink('Organizations');
            }
            $subject->UserRole->save();
            $subject->unlink('Roles');
            $subject->unlink('Events');
            $subject->published = false;
            $subject->{'deleted_at'} = Fisma::now();
            $subject->save();

            Doctrine_Manager::connection()->commit();
            if (count($messages) > 0) {
                $this->view->priorityMessenger(
                    "User deleted successfully.<br/>" .
                    "Please note that this user is still appointed to: " . implode(', ', $messages),
                    'notice'
                );
            } else {
                $this->view->priorityMessenger('User deleted successfully.', 'info');
            }

        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $error = "User cannot be deleted due to an error: {$e->getMessage()}";
            $type  = 'warning';
            $this->view->priorityMessenger($error, $type);
            $this->_redirect('/user/view/id/' . $id . $fromSearchUrl);
        }

        $this->_redirect('/user/list');
    }

    /**
     * Set preferences
     *
     * @GETAllowed
     * @return null
     */
    public function preferencesAction()
    {
        $currentHomeUrl = CurrentUser::getAttribute('homeUrl');

        if ($this->_request->isPost()) {
            $newUrl = $this->_request->getPost('homeUrl');
            if ($newUrl !== $currentHomeUrl) {
                if (filter_var(Fisma_Url::customUrl($newUrl), FILTER_VALIDATE_URL)) {
                    $user = CurrentUser::getInstance();
                    $user->homeUrl = $newUrl;
                    $user->save();
                    $user->refresh();
                    $currentHomeUrl = $newUrl;
                    $this->view->priorityMessenger('Your preferences has been updated.', 'info');
                } else {
                    $this->view->priorityMessenger('Invalid URL submitted.', 'warning');
                }
            }
        }

        $form = Fisma_Zend_Form_Manager::loadForm('user_preferences');
        $form = Fisma_Zend_Form_Manager::prepareForm($form);

        if (!$this->_acl->hasArea('finding')) {
            $form->getElement('homeSelect')->removeMultiOption('finding');
        }

        $irModule = Doctrine::getTable('Module')->findOneByName('Incident Reporting');
        if (!$irModule || !$irModule->enabled || !$this->_acl->hasArea('incident')) {
            $form->getElement('homeSelect')->removeMultiOption('incident');
        }

        $vmModule = Doctrine::getTable('Module')->findOneByName('Vulnerability Management');
        if (!$vmModule || !$vmModule->enabled || !$this->_acl->hasArea('vulnerability')) {
            $form->getElement('homeSelect')->removeMultiOption('vulnerability');
        }

        if (!$this->_acl->hasArea('system_inventory')) {
            $form->getElement('homeSelect')->removeMultiOption('inventory');
        }

        $form->getElement('homeSelect')->setOptions(array('onChange' => 'Fisma.User.populateHomeUrl(this)'));
        $form->setDefault('homeUrl', $currentHomeUrl);

        $this->view->form = $form;

        $buttons = array();
        $buttons['submitButton'] = new Fisma_Yui_Form_Button(
            'saveChanges',
            array(
                'label' => 'Save',
                'onClickFunction' => 'Fisma.Util.submitFirstForm',
                'imageSrc' => '/images/ok.png'
            )
        );
        $buttons['discardButton'] = new Fisma_Yui_Form_Button_Link(
            'discardChanges',
            array(
                'value' => 'Discard',
                'imageSrc' => '/images/no_entry.png',
                'href' => '/user/password'
            )
        );
        $this->view->toolbarButtons = $buttons;
    }
}
