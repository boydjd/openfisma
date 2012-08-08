<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * For the "View As" administrator functionality.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 */
class ViewAsController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Initialize internal members.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->_helper->contextSwitch()
                      ->addActionContext('autocomplete', 'json')
                      ->initContext();
        $this->_helper->ajaxContext()
             ->addActionContext('select-user-form', 'html')
             ->initContext();
    }

    /**
     * @GETAllowed
     */
    public function selectUserFormAction()
    {
        $form = Fisma_Zend_Form_Manager::loadForm('viewas');
        $form = Fisma_Zend_Form_Manager::prepareForm(
            $form,
            array(
                'formName' => ucfirst('Viewas'),
                'view' => $this->view,
                'request' => $this->_request,
                'acl' => $this->_acl,
                'user' => $this->_me
            )
        );
        $form->setAction('/view-as/select-user');
        $this->view->form = $form;
    }

    /**
     * Do autocomplete
     *
     * @GETAllowed
     */
    public function autocompleteAction()
    {
        $keyword = $this->getRequest()->getParam('keyword');
        $expr = 'u.nameFirst LIKE ? OR u.nameLast LIKE ? OR u.email LIKE ? OR u.username LIKE ?';
        $params = array_fill(0, 4, '%' . $keyword . '%');

        $oids = CurrentUser::getInstance()->getOrganizationsByPrivilegeQuery('organization', 'oversee')
            ->select('o.id')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR)
            ->execute();
        $oids = array_map(
            function($v)
            {
                return array_shift($v);
            },
            $oids
        );

        $query = Doctrine_Query::create()
                    ->from('User u')
                    ->select("u.id, u.nameFirst, u.nameLast, u.username, u.email")
                    ->where($expr, $params)
                    ->andWhere('(u.lockType IS NULL OR u.lockType <> ?)', 'manual')
                    ->andWhere('u.published')
                    ->andWhereIn('u.reportingOrganizationId', $oids)
                    ->andWhere('u.id <> ?', CurrentUser::getAttribute('id'))
                    ->orderBy("u.nameFirst, u.nameLast, u.username, u.email")
                    ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $users = $query->execute();
        foreach ($users as &$user) {
            $user['name'] = $user['nameFirst'] . ' ' . $user['nameLast'] . ' ';
            if (!empty($user['username'])) {
                $user['name'] .= '(' . $user['username'] . ') ';
            }
            $user['name'] .= '<' . $user['email'] . '>';
            $user['name'] = trim(preg_replace('/\s+/', ' ', $user['name']));
            unset($user['nameFirst'], $user['nameLast'], $user['username'], $user['email']);
        }

        $this->view->results = $users;

    }

    /**
     *
     */
    public function selectUserAction()
    {
        $userId = $this->getRequest()->getParam('userId');
        $user = Doctrine::getTable('User')->find($userId);
        CurrentUser::getInstance()->viewAs($user);
        $url = $this->getRequest()->getParam('url', '/');
        $this->_redirect($url);
    }

    /**
     * @GETAllowed
     */
    public function stopAction()
    {
        CurrentUser::getInstance()->clearViewAs();
        $url = $this->getRequest()->getParam('url', '/');
        $this->_redirect($url);
    }
}
