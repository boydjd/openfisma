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
 * Sa_InformationTypeController 
 * 
 * @uses Fisma_Zend_Controller_Action_Object
 * @package Security Authorization 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Sa_InformationTypeController extends Fisma_Zend_Controller_Action_Object
{
    protected $_modelName = 'SaInformationType';

    /**
     * indexAction 
     * 
     * @return void
     */
    public function indexAction()
    {
        $this->_forward('list');
    }

    /**
     * Return types which can be assigned to a system
     * The system ID is included in the data for use on the System FIPS-199 page
     * 
     * @return void
     */
    public function activeTypesAction()
    {
        $this->_helper->layout->setLayout('ajax');

        $systemId = $this->getRequest()->getParam('systemId');
        $count          = $this->getRequest()->getParam('count', 10);
        $start          = $this->getRequest()->getParam('start', 0);
        $sort           = $this->getRequest()->getParam('sort', 'category');
        $dir            = $this->getRequest()->getParam('dir', 'asc');

        $system = Doctrine::getTable('System')->find($systemId);
        $organizationId = $system->Organization->id;

        $this->_acl->requirePrivilegeForObject('read', $system->Organization);

        $informationTypes = Doctrine_Query::create()
                            // TODO: Make sure not vulnerable to injection
                            ->select("*, {$systemId} as system")
                            ->from('SaInformationType sat')
                            ->where('sat.hidden = FALSE')
                            ->andWhere(
                                'sat.id NOT IN (' . 
                                'SELECT s.sainformationtypeid FROM SaInformationTypeSystem s where s.systemid = ?' .
                                ')', $systemId
                            )
                            ->limit($count)
                            ->offset($start)
                            ->orderBy("sat.{$sort} {$dir}");

        $informationTypesData = array();
        $informationTypesData['totalRecords'] = $informationTypes->count();
        $informationTypesData['informationTypes'] = $informationTypes->execute()->toArray();
        $this->view->informationTypesData = $informationTypesData;
    }

    /**
     * Return all information types currently assigned to the system
     *
     * @return void
     */
    public function informationTypesAction()
    {
        $this->_helper->layout->setLayout('ajax');

        $id    = $this->getRequest()->getParam('id');
        $count = $this->getRequest()->getParam('count', 10);
        $start = $this->getRequest()->getParam('start', 0);
        $sort  = $this->getRequest()->getParam('sort', 'category');
        $dir   = $this->getRequest()->getParam('dir', 'asc');

        $system = Doctrine::getTable('System')->find($id);

        $this->_acl->requirePrivilegeForObject('read', $system->Organization);

        $systemId = $system->id;

        $informationTypes = Doctrine_Query::create()
                ->select("sat.*, {$system->id} as system")
                ->from('SaInformationType sat, SaInformationTypeSystem sats')
                ->where('sats.systemid = ?', $systemId)
                ->andWhere('sats.sainformationtypeid = sat.id')
                ->andWhere('sat.hidden = FALSE')
                ->orderBy("sat.{$sort} {$dir}")
                ->limit($count)
                ->offset($start);

        $informationTypesData = array();
        $informationTypesData['totalRecords'] = $informationTypes->count();
        $informationTypesData['informationTypes'] = $informationTypes->execute()->toArray();
        $this->view->informationTypesData = $informationTypesData;
    }

    /**
     * Add a single information type to a system
     *
     * @return void
     */
    public function addInformationTypeAction()
    {
        $response = new Fisma_AsyncResponse();
        try {
            $informationTypeId = $this->getRequest()->getParam('sitId');
            $id = $this->getRequest()->getParam('id');

            $system = Doctrine::getTable('System')->find($id);

            $this->_acl->requirePrivilegeForObject('update', $system->Organization);

            $systemId = $system->id;

            $informationTypeSystem = new SaInformationTypeSystem();
            $informationTypeSystem->sainformationtypeid = $informationTypeId;
            $informationTypeSystem->systemid = $systemId;
            $informationTypeSystem->save();
        } catch (Exception $e) {
        $this->getInvokeArg('bootstrap')->getResource('Log')->log($e, Zend_Log::ERR);
            Doctrine_Manager::connection()->rollback();
            $response->fail($e);
        }
        $this->view->response = $response;
    }

    /**
     * Remove a single information type from a system
     *
     * @return void
     */
    public function removeInformationTypeAction()
    {
        $informationTypeId = $this->getRequest()->getParam('sitId');
        $id = $this->getRequest()->getParam('id');

        $system = Doctrine::getTable('System')->find($id);

        $this->_acl->requirePrivilegeForObject('update', $system->Organization);

        $informationType = Doctrine_Query::create()
                ->from('SaInformationTypeSystem saits')
                ->where('saits.sainformationtypeid = ?', $informationTypeId)
                ->andWhere('saits.systemid = ?', $id)
                ->execute();

        $informationType->delete();

        $this->_redirect("/system/view/id/$id");
    }

}
