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
 * Changes to Poc and User models.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021800_PocUserChanges extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        // check for duplicates
        $dups = $this->getHelper()->query('SELECT email, COUNT(1) cnt FROM poc GROUP BY email HAVING cnt > 1');
        foreach ($dups as $dup) {
            // form valid alternate e-mail addresses
            list($name, $domain) = explode('@', $dup->email, 2);
            // get a list of entities with this email account
            $pocs = $this->getHelper()->query('SELECT id FROM poc WHERE email = ?', array($dup->email));
            for ($i = 1; $i < count($pocs); $i++) {
                $newAddress = $name . '.' . $i . '@' . $domain;
                $this->getHelper()->exec('UPDATE poc SET email = ? WHERE id = ?', array($newAddress, $i));
                $this->message('Duplicate email "' . $dup->email . '" updated to "' . $newAddress . '"');
            }
        }

        // make schema changes
        $this->getHelper()->addColumn('poc', 'displayname', 'text NULL', 'email');
        $this->getHelper()->modifyColumn('poc', 'email', 'varchar(255) NOT NULL', 'namelast');
        $this->getHelper()->modifyColumn(
            'poc',
            'locktype',
            "enum('manual','password','inactive','expired')",
            'reportingorganizationid'
        );
        $this->getHelper()->addColumn(
            'poc',
            'accounttype',
            "enum('Contact','User') NOT NULL DEFAULT 'Contact'",
            'locktype'
        );
        $this->getHelper()->addUniqueKey('poc', array('email'), 'email');
    }
}
