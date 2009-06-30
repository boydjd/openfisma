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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * Displays warnings or informational messages to the user via DHTML.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo This doesn't appear to be a controller... why is it called MessageController??
 */
class MessageController extends Zend_Controller_Action
{
    const M_NOTICE = 'notice';
    const M_WARNING = 'warning';
    /**
     *  Routine to show messages to UI by ajax
     */
    public function message($msg, $model) {
        assert(in_array($model, array(
            self::M_NOTICE,
            self::M_WARNING
        )));
        $msg = str_replace("\n", '', $msg);
        $msg = addslashes($msg);
        $this->view->msg = $msg;
        $this->view->model = $model;
        $this->_helper->viewRenderer->renderScript('message.phtml');
        // restore the auto rendering
        $this->_helper->viewRenderer->setNoRender(false);
    }
}
