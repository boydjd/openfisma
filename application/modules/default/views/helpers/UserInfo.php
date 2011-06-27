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
class View_Helper_UserInfo extends Zend_View_Helper_Abstract
{
    /**
     * Render a username with a clickable attribute so that user info can be displayed in a popup
     * 
     * @param string $displayText The text to display (usually this is the username)
     * @param string $username The name of the user to display info for (if not specified, the $displayText is used)
     * @return string;
     */
    public function userInfo($displayText, $username = null)
    {
        // If no username is provided, then its assumed that the display text is the username
        if (is_null($username)) {
            $username = $displayText;
        }
        
        $view = Zend_Layout::getMvcInstance()->getView();
        
        $render = $view->partial(
            'helper/user-info.phtml', 
            'default', 
            array('displayText' => $displayText, 'username' => $username)
        ); 

        return $render;
    }
}
