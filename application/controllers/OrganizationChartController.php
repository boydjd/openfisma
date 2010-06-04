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
 * Produces XML data for charts used in the system inventory module
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @subpackage SUBPACKAGE
 * @version    $Id$
 */
class OrganizationChartController extends SecurityController
{
    /**
     * Set contexts for this controller's actions
     */
    public function init()
    {
        parent::init();
        
        $this->_helper->fismaContextSwitch()
                      ->setActionContext('fips-category', 'xml')
                      ->setActionContext('agency-contractor', 'xml')
                      ->initContext();
    }
    
    /**
     * Verify that this module is enabled
     */
    public function preDispatch()
    {
        Fisma_Zend_Acl::requireArea('system_inventory');
    }

    /**
     * Renders a pie chart which shows the proportions of the various FIPS-199 impact categories among information
     * systems.
     */
    public function fipsCategoryAction()
    {
        $userOrganizations = $this->_me->getOrganizationsByPrivilege('finding', 'read')->toKeyValueArray('id', 'id');
        
        $categoriesQuery = Doctrine_Query::create()
                           ->from('Organization o')
                           ->innerJoin('o.System s')
                           ->addSelect('COUNT(s.id) AS fips_count')
                           ->addSelect('IFNULL(s.fipsCategory, \'NONE\') AS fips_category')
                           ->whereIn('o.id', $userOrganizations)
                           ->groupBy('s.fipsCategory')
                           ->orderBy('s.fipsCategory DESC')
                           ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $this->view->categories = $categoriesQuery->execute();
    }
    
    /**
     * Renders a pie chart which shows the proportions of agency-owned vs. contractor-owned systems.
     */
    public function agencyContractorAction()
    {
        $userOrganizations = $this->_me->getOrganizationsByPrivilege('finding', 'read')->toKeyValueArray('id', 'id');
        
        $agencyContractorQuery = Doctrine_Query::create()
                                 ->from('Organization o')
                                 ->innerJoin('o.System s')
                                 ->addSelect('COUNT(s.id) AS count')
                                 ->addSelect('IFNULL(s.controlledBy, \'N/A\') AS controlled_by')
                                 ->whereIn('o.id', $userOrganizations)
                                 ->groupBy('s.controlledBy')
                                 ->orderBy('s.controlledBy')
                                 ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $this->view->agencyContractor = $agencyContractorQuery->execute();

    }
}
