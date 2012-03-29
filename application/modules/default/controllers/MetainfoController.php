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
 * The metainfo controller provides access to certain metadata. This controller
 * is designed to be invoked asynchronously and does not render a full view.
 *
 * @author     Chris Chen <chriszero@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class MetainfoController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Initialization
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('list', 'html')->initContext();
    }

    /**
     * List meta data on the remediation detail page
     *
     * @GETAllowed
     * @return void
     */
    public function listAction()
    {
        $module = $this->_request->getParam('o');
        $this->view->selected = $this->_request->getParam('value', '');
        if ($module == 'organization') {
            $organizations  = CurrentUser::getInstance()
                ->getOrganizationsQuery()
                ->leftJoin('o.System s')
                ->leftJoin('o.OrganizationType orgType ')
                ->andWhere('orgType.nickname <> ? OR s.sdlcPhase <> ?', array('system', 'disposal'))
                ->execute();
            $list = $this->view->treeToSelect($organizations, 'nickname');

            // Since the list for organizations is prepended with dashes, we need to do some
            // string transformation on $this->view->selected so that the full name is ripped out
            // and the appropriate level of padding is added.
            $organization = trim(substr($this->view->selected, 0, strpos($this->view->selected, '-')-1));

            foreach ($list as $item) {
                if (!empty($organization) && $pos = strpos($item, $organization)) {
                    $organization = substr($item, 0, $pos) . $organization;
                    break;
                }
            }

            $this->view->selected = $organization;
        } elseif ($module == 'system') {
            $systems = CurrentUser::getInstance()
                ->getSystemsQuery()
                ->leftJoin('o.OrganizationType orgType ')
                ->andWhere('orgType.nickname <> ? OR s.sdlcPhase <> ?', array('system', 'disposal'))
                ->orderBy('o.nickname')
                ->execute();

            $list = $this->view->systemSelect($systems);
        } elseif ($module == 'security_control') {
            $securityControls = Doctrine::getTable('SecurityControl')->findAll();
            foreach ($securityControls as $securityControl) {
                $list[$securityControl->id] = $securityControl->code;
            }
        } elseif (in_array($module, array('threat_level', 'countermeasures-effectiveness'))) {
            $list = array(
                "LOW"      => "LOW",
                "MODERATE" => "MODERATE",
                "HIGH"     => "HIGH"
            );
        } elseif ('confidentiality' == $module) {
            $list = array(
                "NA"       => "NA",
                "LOW"      => "LOW",
                "MODERATE" => "MODERATE",
                "HIGH"     => "HIGH"
            );
        } elseif (in_array($module, array('integrity', 'availability'))) {
            $list = array(
                "LOW"      => "LOW",
                "MODERATE" => "MODERATE",
                "HIGH"     => "HIGH"
            );
        } elseif ($module == 'decision') {
            $list = array(
                "APPROVED" => "APPROVED",
                "DENIED"   => "DENIED"
            );
        } elseif ($module == 'type') {
            $list = array(
                "CAP" => "(CAP) Corrective Action Plan",
                "AR"  => "(AR) Accepted Risk",
                "FP"  => "(FP) False Positive"
            );
            $this->view->selected = isset($list[$this->view->selected]) ? $list[$this->view->selected] : 'CAP';
        } elseif ($module == 'controlledBy') {
            $list = array(
                "AGENCY" => "AGENCY",
                "CONTRACTOR"  => "CONTRACTOR"
            );
            $this->view->selected = isset($list[$this->view->selected]) ? $list[$this->view->selected] : 'CAP';
        } elseif ($module == 'yesNo') {
            $list = array(
                "YES" => "YES",
                "NO"  => "NO"
            );
            $this->view->selected = isset($list[$this->view->selected]) ? $list[$this->view->selected] : 'YES';
        } elseif ($module == 'systemType') {
            $list = Doctrine::getTable('SystemType')->getTypeList();
        } elseif ($module == 'systemSdlcPhase') {
            $list = System::getSdlcPhaseMap();
            $selected = $this->getRequest()->getParam('value');
            $this->view->selected = $list[$selected];
        } elseif ($module == 'source') {
            $sources = Doctrine::getTable('Source')->getSources();
            $list = $this->view->sourceSelect($sources);
        } elseif ($module == 'organizationType') {
            $list = Doctrine::getTable('OrganizationType')->getOrganizationTypeArray();
        } elseif ($module == 'incidentCategories') {
            $list = IrCategoryTable::getCategoriesForSelect();
            $this->view->selectStyle = 'nested';
        }

        $this->view->list = $list;
    }
}
