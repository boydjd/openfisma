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
 * @version    $Id$
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
                      ->addActionContext('set-cookie', 'json')
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

        try {
            $conn->beginTransaction();

            $rolesOrganizations = $this->_request->getPost('organizations', array());

            if (is_null($subject)) {
                $subject = new $this->_modelName();
            } elseif (!($subject instanceof Doctrine_Record)) {
                throw new Fisma_Zend_Exception('Invalid parameter, expected a Doctrine_Model');
            }
            $values = $form->getValues();

            // If any roles were added to the user without any organizations, make sure they're added
            // to the appropriate array for saving the User Roles.
            foreach (array_diff_key(array_flip($values['role']), $rolesOrganizations) as $k => $v) {
                $rolesOrganizations[$k] = array();
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
        $this->view->rows = $user->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);
        $this->view->viewLink = "/user/view/id/$id";
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
                    $user->merge($post);
                    $user->save();
                    $message = "Password updated successfully."; 
                    $model   = 'notice';
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
            //@todo check injection
            $user->notifyFrequency = $this->_request->getParam('notify_frequency');

            $postEvents = $this->_request->getPost('existEvents');
            try {
                Doctrine_Manager::connection()->beginTransaction();
                $modified = $user->getModified();

                $user->unlink('Events');

                if (!empty($postEvents)) {
                    $user->link('Events', $postEvents);
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

        $this->view->me = $user;
    }

    /**
     * Set a cookie that will be reloaded whenever this user logs in
     * 
     * @return void
     */
    public function setCookieAction()
    {
        $response = new Fisma_AsyncResponse();
        
        $cookieName = $this->getRequest()->getParam('name');
        $cookieValue = $this->getRequest()->getParam('value');

        if (empty($cookieName) || is_null($cookieValue)) {
            throw new Fisma_Zend_Exception("Cookie name and/or cookie value cannot be null");
        }

        // See if a cookie exists already
        $query = Doctrine_Query::create()
                 ->from('Cookie c')
                 ->where('c.userId = ? AND c.name = ?', array($this->_me->id, $cookieName))
                 ->limit(1);

        $result = $query->execute();

        if (0 == count($result)) {
            // Insert new cookie
            $cookie = new Cookie;

            $cookie->name = $cookieName;
            $cookie->value = $cookieValue;
            $cookie->userId = $this->_me->id;
        } else {
            // Update existing cookie
            $cookie = $result[0];

            $cookie->value = $cookieValue;
        }
        
        try {
            $cookie->save();
        } catch (Doctrine_Validator_Exception $e) {
            $response->fail($e->getMessage());
        }

        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();

        echo Zend_Json::encode($response);
    }
    
    /**
     * Store user last accept rob and create a audit event
     * 
     * @return void
     */
    public function acceptRobAction()
    {
        $user = CurrentUser::getInstance();
        $user->lastRob = Fisma::now();
        $user->save();
        
        $this->_redirect('/Index/index');
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
            $user = Doctrine::getTable('User')->findOneByUsername($username);
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
                    $organization['name'], 
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
     * @todo code finish this function later
     */
    public function checkAccountAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'User');

        $accountInfo = array();

        $data = LdapConfig::getConfig();
        $account = $this->_request->getParam('account');
        $msg = '';
        if (count($data) == 0) {
            $type = 'warning';
            $msg .= "No LDAP providers defined";
        }

        foreach ($data as $opt) {
            try {
                $srv = new Zend_Ldap($opt);

                $type = 'message';
                $dn = $srv->getCanonicalAccountName($account, Zend_Ldap::ACCTNAME_FORM_DN); 

                // Just get specified standard LDAP attributes.
                $accountInfo = $srv->getEntry(
                    $dn,
                    array('sn',
                          'givenname',
                          'mail',
                          'telephonenumber',
                          'mobile',
                          'title')
                );
                $msg = "$account exists, the dn is: $dn";
                
                break;
            } catch (Zend_Ldap_Exception $e) {
                $type = 'warning';
                // The expected error is LDAP_NO_SUCH_OBJECT, meaning that the
                // DN does not exist.
                if ($e->getErrorCode() ==
                    Zend_Ldap_Exception::LDAP_NO_SUCH_OBJECT) {
                    $msg = "$account does NOT exist";
                } else {
                    $msg .= 'Error while checking account: '
                          . $e->getMessage();
                }
            }
        }

        echo Zend_Json::encode(array('msg' => $msg, 'type' => $type, 'accountInfo' => $accountInfo));
        $this->_helper->viewRenderer->setNoRender();
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

        $this->view->username = $user->username;
        $this->view->viewLink = "/user/view/id/$id";

        $commentData = array();
        foreach ($comments as $comment) {
            $commentData[] = array(
                $comment['createdTs'], 
                $this->view->userInfo($comment['User']['username']), 
                $comment['comment'],
            );
        }

        $dataTable = new Fisma_Yui_DataTable_Local();
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Timestamp', true, 'YAHOO.widget.DataTable.formatText'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('User', true, 'Fisma.TableFormat.formatHtml'))
                  ->addColumn(new Fisma_Yui_DataTable_Column('Comment', false))
                  ->setData($commentData);

        $this->view->dataTable = $dataTable;
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
        ->delete();

        foreach ($userRoles as $userRole) { 
            $userRoleOrganization = new UserRoleOrganization();
            $userRoleOrganization->organizationId = (int) $organizationId;
            $userRoleOrganization->userRoleId = (int) $userRole;
            $userRoleOrganization->save();
        }

        Doctrine_Manager::connection()->commit();
    }
}
