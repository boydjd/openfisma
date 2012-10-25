<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Check users' (un)locking conditions
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */

class Fisma_Cli_LockUser extends Fisma_Cli_Abstract
{
    /**
     * Run the check on lock/unlock
     */
    protected function _run()
    {
        global $application;

        $enabledUsers = Doctrine_Query::create()
            ->from('User u')
            ->where('u.lockType <> ?', 'manual')
            ->orWhere('u.lockType is null')
            ->execute();
        $this->getLog()->info("Found " . $enabledUsers->count() . " enabled users.");

        $lockedUsers = array();
        $unlockedUsers = array();

        foreach ($enabledUsers as $user) {
            $locked = $user->locked;

            $reverseProxyOptions = $application->getOption('reverse_proxy_auth');
            $reverseProxyEnabled = isset($reverseProxyOptions['enable']) && $reverseProxyOptions['enable'];
            $user->checkAccountLock(true, $reverseProxyEnabled);

            if ($locked && !$user->locked) {
                $unlockedUsers[] = $user->username;
            } else if (!$locked && $user->locked) {
                $lockedUsers[] = $user->username;
            }
        }

        if (count($lockedUsers) > 0) {
            $this->getLog()->info(
                count($lockedUsers) . ' users have been locked: {' . implode(', ', $lockedUsers) . '}'
            );
        }

        if (count($unlockedUsers) > 0) {
            $this->getLog()->info(
                count($unlockedUsers) . ' users have been unlocked: {' . implode(', ', $unlockedUsers) . '}'
            );
        }
    }
}
