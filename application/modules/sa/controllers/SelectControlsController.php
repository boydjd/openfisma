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
 * Allow users to add/remove controls and enhancements within a security authroization.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    SA
 */
class Sa_SelectControlsController extends Fisma_Zend_Controller_Action_Security
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
                      ->addActionContext('control-tree-data', 'json')
                      ->initContext();
    }

    /**
     * @return void
     */
    public function controlTreeDataAction() 
    {
        $id = $this->_request->getParam('id');
        $controls = Doctrine_Query::create()
            ->from('SaSecurityControl saSC')
            ->leftJoin('saSC.SecurityControl control')
            ->leftJoin('saSC.SecurityControlEnhancements enhancements')
            ->leftJoin('saSC.Inherits inSys')
            ->where('saSC.securityAuthorizationId = ?', $id)
            ->orderBy('control.code')
            ->execute();

        $data = array();
        foreach ($controls as $saControl) {
            $enhancements = array();
            $control = $saControl->SecurityControl;
            foreach ($saControl->SecurityControlEnhancements as $enhancement) {
                $enhancements[] = array(
                    'id' => $enhancement->id,
                    'number' => $enhancement->number,
                    'description' => $enhancement->description
                );
            }
            $data[$control->family][] = array(
                'id' => $control->id,
                'code' => $control->code,
                'name' => $control->name,
                'enhancements' => $enhancements,
                'common' => $saControl->common ? true : false,
                'inherits' => is_null($saControl->Inherits) ? null : $saControl->Inherits->nickname
            );
        }
        $this->view->treeData = $data;
    }
}
