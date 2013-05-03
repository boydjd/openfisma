<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * Summary view for SA
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Form
 */
class Sa_SummaryController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * @GETAllowed
     */
    public function indexAction()
    {
        $this->view->systems = Doctrine_Query::create()
            ->select('s.id as id, o.nickname as nickname, o.name as name')
            ->addSelect('count(idt.id) as idtc')
            ->addSelect('count(sc.id) as scc')
            ->addSelect('o.id, s.id, idt.id, sc.id')
            ->from('System s')
            ->leftJoin('s.Organization o')
            ->leftJoin('s.InformationDataTypes idt')
            ->leftJoin('s.SecurityControls sc')
            ->groupBy('s.id')
            ->whereIn('s.id', Doctrine::getTable('System')->getSystemIds())
            ->orderBy('nickname')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();

        //0 16 33 50 66 83 100
        foreach ($this->view->systems as &$system) {
            $system['progress'] = 0;
            if ($system['idtc'] > 0) {
                $system['progress'] = 16;
            }
            if ($system['scc'] > 0) {
                $system['progress'] = 33;
            }
        }

        $this->view->toolbarButtons = $this->getToolbarButtons();
    }

    public function getToolbarButtons($subject = null)
    {
        $buttons = array();

        $buttons['dashboard'] = new Fisma_Yui_Form_Button_Link(
            'dashboardButton',
            array(
                'value' => 'Dashboard',
                'icon' => 'th-large',
                'href' => "/sa/dashboard"
            )
        );

        return $buttons;
    }
}
