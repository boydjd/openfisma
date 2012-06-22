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
 * A view helper which makes a username clickable to show detailed info about that user.
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    View_Helper
 */
class View_Helper_OrganizationInfo extends Zend_View_Helper_Abstract
{
    /**
     * Render a username with a clickable attribute so that user info can be displayed in a popup
     *
     * @param string $displayText The text to display (usually this is the username)
     * @param string $orgId The id of the user to display info for
     * @return string
     */
    public function organizationInfo($displayText, $orgId)
    {
        if (empty($orgId)) {
            throw new Fisma_Zend_Exception("Second parameter to OrganizationInfo helper is required.");
        }

        $view = Zend_Layout::getMvcInstance()->getView();

        $render = $view->partial(
            'helper/organization-info.phtml',
            'default',
            array('displayText' => $displayText, 'orgId' => $orgId)
        );

        return $render;
    }
}
