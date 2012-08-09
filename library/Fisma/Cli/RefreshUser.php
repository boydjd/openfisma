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
 * Refresh user information from LDAP
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */

class Fisma_Cli_RefreshUser extends Fisma_Cli_Abstract
{
    /**
     * Set up logging
     */
    public function __construct()
    {
        // Log all migration messages to a dedicated log.
        $fileWriter = new Zend_Log_Writer_Stream(Fisma::getPath('log') . '/refresh-user.log');
        $fileWriter->setFormatter(new Zend_Log_Formatter_Simple("[%timestamp%] %message%\n"));

        parent::getLog()->addWriter($fileWriter);
    }

    /**
     * Run the check on lock/unlock
     */
    protected function _run()
    {
        $enabledUsers = Doctrine_Query::create()
            ->from('User u')
            ->where('u.deleted_at is null')
            ->andWhere('u.username <> ?', 'root')
            ->execute();
        $log = "Found " . $enabledUsers->count() . " existing users to sync.";
        $this->getLog()->info($log);

        foreach ($enabledUsers as $user) {
            try {
                $user->syncWithLdap();
                $user->save();
            } catch (Exception $e) {
                $log .= "\n" . $user->username . ' - ' . $e->getMessage();
                $this->getLog()->err($user->username . ' - ' . $e->getMessage(), $e);
            }
        }
        $log .= "\nSynchronization completed.";
        $this->getLog()->info("Synchronization completed.");
        Notification::notify('LDAP_SYNC', null, null, array('log' => $log));
    }
}
