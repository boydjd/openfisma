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
 * @author    Chris Chen <chriszero@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */
 
/**
 * The metainfo controller provides access to certain metadata. This controller
 * is designed to be invoked asynchronously and does not render a full view.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class MetainfoController extends PoamBaseController
{
    /**
     * Initialize 
     */
    public function init()
    {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('list', 'html')->initContext();
    }

    /**
     * List meta data on the remediation detail page
     */
    public function listAction()
    {
        $module = $this->_request->getParam('o');
        $this->view->selected = $this->_request->getParam('value', '');
        if ($module == 'organization') {
            $organizations  = Doctrine::getTable('Organization')->findAll();
            foreach ($organizations as $organization) {
                $list[$organization->id] = "($organization->nickname)-" . $organization->name;
            }
        } elseif ($module == 'security_control') {
            $securityControls = Doctrine::getTable('SecurityControl')->findAll();
            foreach ($securityControls as $securityControl) {
                $list[$securityControl->id] = $securityControl->code;
            }
        } elseif (in_array($module, array('threat_level', 'countermeasures-effectiveness'))) {
            $list = array(
                "NONE"     => "NONE",
                "LOW"      => "LOW",
                "MODERATE" => "MODERATE",
                "HIGH"     => "HIGH"
            );
        } elseif ('confidentiality' == $module) {
            $list = array(
                "na"       => "na",
                "low"      => "low",
                "moderate" => "moderate",
                "high"     => "high"
            );
        } elseif (in_array($module, array('integrity', 'availability'))) {
            $list = array(
                "low"      => "low",
                "moderate" => "moderate",
                "high"     => "high"
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
            $list = array(
                "gss" => "General Support System",
                "major"  => "Major Application",
                "minor"  => "Minor Application"
            );
            $selected = urldecode($this->getRequest()->getParam('value'));
            $this->view->selected = $list[array_search($selected, $list)];
        }

        $this->view->list = $list;
    }
}
