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
 * <http://www.gnu.org/licenses/>.
 *
 * @author    Ryan yang <ryanyang@users.sorceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: $
 * @package   Listener
 */
 
/**
 * A listener for the Config model
 *
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 * @package   Listener
 */
class ConfigListener extends Doctrine_Record_Listener
{
    public function preSave(Doctrine_Event $event)
    {
        $config = $event->getInvoker();
        $modifyValue = $config->getModified();

        if ($modifyValue) {
            $value = $modifyValue['value'];
            $affectedArray = array(Configuration::EXPIRING_TS, Configuration::UNLOCK_DURATION);
            if (in_array($config->name, $affectedArray)) {
                //convert to second
                $value *= 60;
            }
            $config->value = $value;
        }
    }
        
}
