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
                      ->setAutoJsonSerialization(false)
                      ->addActionContext('check-account', 'json')
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
        $form->removeElement('username');
        $form->removeElement('password');
        $form->removeElement('confirmPassword');
        $form->removeElement('checkAccount');
        $form->removeElement('generate_password');
        $form->removeElement('role');
        $form->removeElement('locked');
        $form->removeElement('lockReason');
        $form->removeElement('lockTs');
        $form->removeElement('comment');
        $form->removeElement('reportingOrganizationId');
        $form->removeElement('mustResetPassword');
        return $form;
    }

    /** 
     * Set the Roles, organization relation before save the model
     *
     * @param Zend_Form $form The specified form to save
     * @param Doctrine_Record|null $subject The specified subject related to the form
     * @return void
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

            if (is_null($subject)) {
                $subject = new $this->_modelName();
            } elseif (!($subject instanceof Doctrine_Record)) {
                throw new Fisma_Zend_Exception('Invalid parameter, expected a Doctrine_Model');
            }
            $values = $form->getValues();
            $actionName = strtolower($this->_request->getActionName());
            if ('edit' === $actionName && 'root' !== $subject->username) {

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
            $conn->commit();

            // Just send out email when create a new account or change password by admin user,
            // and it does not sent out email when the root user changes his own password.
            if ('create' === $actionName) {
                $mail = new Fisma_Zend_Mail();
                $mail->sendAccountInfo($subject);
            } else if ('edit' === $actionName
                       && !empty($values['password'])
                       && ('root' !== $subject->username || $this->_me->username !== 'root')) {
                $mail = new Fisma_Zend_Mail();
                $mail->sendPassword($subject);
            } 

            // do not need to audit log root user's role or organization
            if ('edit' === $actionName && ($roleChanged || $organizationChanged) && 'root' !== $subject->username) {
                $auditLog = $roleChanged ? 'Updated Role.' : 'Updated Organization.';
                $subject->getAuditLog()->write($auditLog);
            }
            return $subject->id;
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
            throw $e;
        }
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
     */
    public function logAction()
    {
        $id = $this->getRequest()->getParam('id');
        
        $user = Doctrine::getTable('User')->find($id);
    
        $this->view->username = $user->username;
        $this->view->columns = array('Timestamp', 'User', 'Message');
        $this->view->viewLink = "/user/view/id/$id";

        $logs = $user->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);

        $logRows = array();

        foreach ($logs as $log) {
            $logRows[] = array(
                'timestamp' => $log['o_createdTs'],
                'user' => $this->view->userInfo($log['u_username']),
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
    }

    /**
     * Set user's notification policy
     * 
     * @return void
     */
    public function notificationAction()
    {
        $user = Doctrine::getTable('User')->find($this->_me->id);

        if ($this->_request->isPost()) {
            $notifyFrequency = $this->_request->getParam('notify_frequency');

            $postEvents = $this->_request->getPost('existEvents');
            if (Inspekt::isInt($notifyFrequency)) {
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    $modified = $user->getModified();

                    $user->unlink('Events');

                    if (!empty($postEvents)) {
                        $user->link('Events', $postEvents);
                    }

                    $user->notifyFrequency = $notifyFrequency;
                    $user->save();
                    Doctrine_Manager::connection()->commit();

                    $message = "Notification events modified successfully";
                    $model   = 'notice';
                } catch (Doctrine_Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    $message = $e->getMessage();
                    $model   = 'warning';
                }
            } else {
                /** @todo English */
                $message = "Notify Frequency: '$notifyFrequency' is not a valid value";
                $model   = 'warning';
            }
            $this->view->priorityMessenger($message, $model);
            $this->_redirect('/user/notification');
        }

        $this->view->me = $user;
    }

    /**
     * Store user last accept rob and create a audit event
     * 
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
     * Override parent to add roles interface
     *
     * @return void
     */
    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');

        $tabView = new Fisma_Yui_TabView('UserView');

        $q = Doctrine_Query::create()
            ->from('User u')
            ->leftJoin('u.Roles r')
            ->where('u.id = ?', $id);

        $user = $q->fetchArray();

        foreach ($user[0]['Roles'] as $role) {
            $tabView->addTab(
                $this->view->escape($role['nickname']), 
                "/User/get-organization-subform/user/{$id}/role/{$role['id']}/readOnly/1", 
                $role['id'],
                'false'
            );
        }

        $this->view->tabView = $tabView;

        parent::_viewObject();
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
     */
    public function infoAction()
    {
        $this->_helper->layout->disableLayout();
        
        $username = $this->getRequest()->getParam('username');

        if ($username) {
            $user = Doctrine::getTable('Poc')->findOneByUsername($username);
        } else {
            $user = null;
        }
        
        $this->view->user = $user;
    }

    /**
     * Retrieve the organization subform 
     * 
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
     * Override parent to add a link for audit logs
     *
     * @return void
     */
    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $tabView = new Fisma_Yui_TabView('UserView');

        $q = Doctrine_Query::create()
            ->from('User u')
            ->leftJoin('u.Roles r')
            ->where('u.id = ?', $id);

        $user = $q->fetchArray();

        if (isset($user[0]['Roles'])) {
            foreach ($user[0]['Roles'] as $role) {
                $tabView->addTab(
                    $this->view->escape($role['nickname']), 
                    "/User/get-organization-subform/user/{$id}/role/{$role['id']}/readOnly/0", 
                    $role['id'],
                    'true' 
                );
            }
        }

        $roles = Doctrine_Query::create()
            ->select('r.id, r.nickname')
            ->from('Role r')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();

        $this->view->auditLogLink = "/user/log/id/$id";
        $this->view->commentLink = "/user/comments/id/$id";
        $this->view->tabView = $tabView;
        $this->view->roles = Zend_Json::encode($roles);

        parent::_editObject();

        $this->view->form->removeDecorator('Fisma_Zend_Form_Decorator');
    }

    /**
     * Override parent method
     * 
     * @return void
     */
    public function createAction()
    {
        $tabView = new Fisma_Yui_TabView('UserView');

        $roles = Doctrine_Query::create()
            ->select('r.id, r.nickname')
            ->from('Role r')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();

        $this->view->roles = Zend_Json::encode($roles);
        $this->view->tabView = $tabView;
        parent::_createObject();
        $this->view->form->removeDecorator('Fisma_Zend_Form_Decorator');
    }

    /**
     * Generate a password that meet the application's password complexity requirements.
     * 
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
     * Check if the specified LDAP distinguished name (Account) exists in the system's specified LDAP directory.
     * 
     * @return void
     */
    public function checkAccountAction()
    {
        
        if (! ($this->_acl->hasPrivilegeForClass('read', 'User') 
               || $this->_acl->hasPrivilegeForClass('read', 'Poc')) ) {
            throw new Fisma_Zend_Exception_InvalidPrivilege("User does not have privileges to check account.");
        }

        try {
            $ldapServerConfigurations = LdapConfig::getConfig();

            if (count($ldapServerConfigurations) == 0) {
                throw new Fisma_Zend_Exception_User('No LDAP servers defined.');
            }

            $accountInfo = array();
            $account = $this->_request->getParam('account');

            $msg = '';
            $matchedAccounts = null;

            if (empty($account)) {
                throw new Fisma_Zend_Exception_User('You did not specify any account name.');
            } elseif (strlen($account) < 3) {
                throw new Fisma_Zend_Exception_User('When searching for a user, you must type at least 3 letters.');
            }

            foreach ($ldapServerConfigurations as $ldapServerConfiguration) {        
                $ldapServer = new Zend_Ldap($ldapServerConfiguration);
                $type = 'message';
            
                // Using Zend_Ldap_Filter instead of a string query prevents LDAP injection
                $searchFilter = Zend_Ldap_Filter::orFilter(
                    Zend_Ldap_Filter::begins('sAMAccountName', $account),
                    Zend_Ldap_Filter::begins('uid', $account)
                );

                $matchedAccounts = $ldapServer->search(
                    $searchFilter,
                    null,
                    Zend_Ldap::SEARCH_SCOPE_SUB,
                    array('givenname',
                          'mail',
                          'mobile',
                          'sAMAccountName',
                          'sn',
                          'telephonenumber',
                          'title',
                          'uid'),
                    'givenname',
                    null,
                    10 // limit 10 results to avoid crushing ldap server
                );

                break;
            }
        } catch (Zend_Ldap_Exception $zle) {
            $type = 'warning';
            $msg .= 'Error while checking account: ' . $zle->getMessage();
        } catch (Fisma_Zend_Exception_User $fzeu) {
            $type = 'warning';
            $msg .= 'Error while checking account: ' . $fzeu->getMessage();
        }

        echo Zend_Json::encode(
            array(
                'accounts' => is_object($matchedAccounts) ? $matchedAccounts->toArray() : null,
                'msg' => $msg, 
                'query' => $account,
                'type' => $type, 
            )
        );

        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Add a comment to a specified user
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
                'username' => $this->view->userInfo($comment['User']['username']),
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

        $this->view->username = $user->username;
        $this->view->viewLink = "/user/view/id/$id";

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
            $form->removeElement('checkAccount');
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
        $organizations = Doctrine::getTable('Organization')->getOrganizationSelectQuery()->execute();
        $selectArray = $this->view->systemSelect($organizations);
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
            sort($originalData[$i]);
            sort($postData[$i]);
            if ($originalData[$i] != $postData[$i]) {
                return true;
            } 
        }
        return false;
    }
}
