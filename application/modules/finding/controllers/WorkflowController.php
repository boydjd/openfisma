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
 * View and edit the finding workflow
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Finding_WorkflowController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Construct the workflow diagram
     *
     * @return void
     */
    public function viewAction()
    {
        $this->view->msList = Doctrine_Query::create()
                                  ->from('Evaluation e')
                                  ->where('approvalGroup = ?', 'action')
                                  ->execute();
        $this->view->evList = Doctrine_Query::create()
                                  ->from('Evaluation e')
                                  ->where('approvalGroup = ?', 'evidence')
                                  ->execute();
    }
}
