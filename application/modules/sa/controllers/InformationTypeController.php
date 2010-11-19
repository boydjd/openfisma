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
        $organizationId = $this->getRequest()->getParam('organizationId');
        $systemId = Doctrine::getTable('Organization')->find($organizationId)->System->id;
        $informationTypes = Doctrine_Query::create()
                            // TODO: Make sure not vulnerable to injection
                            ->select("*, {$organizationId} as organization")
                            ->from('SaInformationType sat')
                            ->where('sat.hidden = FALSE')
                            ->andWhere(
                                'sat.id NOT IN (' . 
                                'SELECT s.sainformationtypeid FROM SaInformationTypeSystem s where s.systemid = ?' .
                                ')', $systemId
                            )
                            ->execute()
                            ->toArray();

        $informationTypesData = array();
        $informationTypesData['informationTypes'] = $informationTypes;
        $this->view->informationTypesData = $informationTypesData;
    }
}
