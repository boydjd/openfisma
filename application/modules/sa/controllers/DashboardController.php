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
 * Dashboard view for SA
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Form
 */
class Sa_DashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * @GETAllowed
     */
    public function indexAction()
    {
        $this->view->toolbarButtons = $this->getToolbarButtons();
    }

    public function getToolbarButtons($subject = null)
    {
        $buttons = array();

        $buttons['summary'] = new Fisma_Yui_Form_Button_Link(
            'summaryButton',
            array(
                'value' => 'Summary',
                'icon' => 'list',
                'href' => "/sa/summary"
            )
        );

        return $buttons;
    }
}
