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
 */
 
require_once CONTROLLERS . DS . 'PoamBaseController.php';
require_once MODELS . DS . 'blscr.php';

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
    public function init()
    {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('list', 'html')->initContext();
    }
    public function listAction()
    {
        $req = $this->getRequest();
        $module = $req->getParam('o');
        $this->view->selected = $req->getParam('value', '');
        if ($module == 'system') {
            $list = & $this->_system_list;
        }
        if ($module == 'blscr') {
            $m = new Blscr();
            $list = $m->getList('class');
            $list = array_keys($list);
            $list = array_combine($list, $list);
        }
        if (in_array($module, array(
            'threat_level',
            'cmeasure_effectiveness'
        ))) {
            $list = array(
                "NONE" => "NONE",
                "LOW" => "LOW",
                "MODERATE" => "MODERATE",
                "HIGH" => "HIGH"
            );
        }
        if ($module == 'decision') {
            $list = array(
                "APPROVED" => "APPROVED",
                "DENIED" => "DENIED"
            );
        }
        if ($module == 'type') {
            $list = array(
                "CAP" => "(CAP) Corrective Action Plan",
                "AR" => "(AR) Accepted Risk",
                "FP" => "(FP) False Positive"
            );
            $this->view->selected = nullGet($list[$this->view->selected], 'CAP');
        }
        $this->view->list = $list;
        $this->render();
    }
}
