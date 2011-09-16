<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * A controller that handles the summary page for SA and all related actions
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @subpackage SUBPACKAGE
 */
class Sa_SummaryController extends Fisma_Zend_Controller_Action_Security
{
    /**
    * Create the additional contexts for this class.
    * 
    * @return void
    */
    public function init()
    {
        $this->_helper->fismaContextSwitch()
                      ->addActionContext('data', 'json')
                      ->setAutoJsonSerialization(false)
                      ->initContext();

        if (in_array($this->_request->getParam('format'), array('pdf', 'xls'))) {
            $this->_helper->reportContextSwitch()
                          ->addActionContext('data', array('pdf', 'xls'));
        }

        parent::init();
    }

    /**
     * The main summary action. This shows the status of all SA's across the enterprise.
     */
    public function indexAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'SecurityAuthorization');
    }
    
    /**
     * Provides data for the summmary table (invoked by XHR)
     */
    public function dataAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'SecurityAuthorization');

        $type = $this->getRequest()->getParam('type');
        $source = $this->getRequest()->getParam('sourceNickname');        
        $format = $this->_request->getParam('format');
        // Prepare summary data

        // Get user organizations
        $organizationsQuery = $this->_me->getOrganizationsByPrivilegeQuery('SecurityAuthorization', 'read');
        $organizationsQuery->select('o.id');
        $organizationsQuery->setHydrationMode(Doctrine::HYDRATE_NONE);
        $organizations = $organizationsQuery->execute();

        foreach ($organizations as $k => $v) {
            $organizations[] = $v[0];
            unset($organizations[$k]);
        }

        // Get finding summary counts
        $organizations = $this->_getSaSummaryData($organizations);
        $organizations = $this->_prepareSummaryData($organizations);

        // For excel and PDF requests, return a table format. For JSON requests, return a hierarchical
        // format
        if ('pdf' == $format || 'xls' == $format) {
            die("not implemented");
        } else {
            // Assign children to parents accordingly
            $temp = array(array());
            foreach ($organizations as $n => $a) {
                $d = $a['nodeData']['level']+1;
                $temp[$d-1]['children'][] = &$organizations[$n];
                $temp[$d] = &$organizations[$n];
            }
            $organizations = $temp[0]['children'];

            $this->view->summaryData = $organizations;
        } 
    }

    /**
     * Returns summary data for security authorizations.
     *
     * @param array $organizations A list of organizations this user should see in the summary data.
     * @return array
     */
    private function _getSaSummaryData($organizations)
    {
        $summary = Doctrine_Query::create()
            ->addSelect('node.nickname nickname')
            ->addSelect('node.name name')
            ->addSelect("IF(orgtype.nickname = 'system', system.type, orgtype.icon) orgType")
            ->addSelect('node.id as id')
            ->addSelect(
                "IF(orgtype.nickname <> 'system', orgtype.name,"
                . "CASE WHEN system.type = 'gss' then 'General Support System' WHEN "
                . "system.type = 'major' THEN 'Major Application' WHEN system.type = 'minor' THEN "
                . "'Minor Application' END) orgTypeLabel"
            )
            ->addSelect('node.level level')
            ->addSelect("SUM(IF(sa.status='Select' 
                                OR sa.status='Implement'
                                OR sa.status='Assessment' 
                                OR sa.status='Authorization'
                                OR sa.status='Active', 1, 0)) AS step1")
            ->addSelect("SUM(IF(sa.status='Implement'
                                OR sa.status='Assessment' 
                                OR sa.status='Authorization'
                                OR sa.status='Active', 1, 0)) AS step2")
            ->addSelect("SUM(IF(sa.status='Assessment' 
                                OR sa.status='Authorization'
                                OR sa.status='Active', 1, 0)) AS step3")
            ->addSelect("SUM(IF(sa.status='Authorization' 
                                OR sa.status='Active', 1, 0)) AS step4")
            ->addSelect("SUM(IF(sa.status='Active', 1, 0)) AS step5")
            ->from('Organization node')
            ->leftJoin("node.SecurityAuthorizations sa")
            ->leftJoin('node.System system')
            ->leftJoin('node.OrganizationType orgtype')
            ->where('orgtype.nickname <> ? OR system.sdlcPhase <> ?', array('system', 'disposal'))
                ->groupBy('node.id')
            ->orderBy('node.lft')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!empty($organizations))
            $summary->andWhereIn('node.id', $organizations);

        return $summary->execute();
    }

    /**
     * Prepares the summary data array returned from Doctrine for use 
     * 
     * @param array $organizations 
     * @return array 
     */
    private function _prepareSummaryData($organizations)
    {
        // Remove model names from array keys
        foreach ($organizations as &$organization) {
            foreach ($organization as $k => $v) {
                $underscoreString = strstr($k, '_');
                if ($underscoreString !== FALSE) {
                    $newName = substr($underscoreString, 1);
                    $organization['nodeData'][$newName] = $v;                        
                    unset($organization[$k]);                    
                }
            }

            // Store counts in arrays for YUI data table
            $organization['children'] = array();
        }

        return $organizations;
    }
}
