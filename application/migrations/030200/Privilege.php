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
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030200_Privilege extends Fisma_Migration_Abstract
{
    protected function _updatePrivileges()
    {
        $this->getHelper()->exec(
            'DELETE FROM role_privilege WHERE privilegeid IN (SELECT id FROM privilege WHERE resource = ?)',
            array('poc')
        );
        $this->getHelper()->exec(
            'DELETE FROM privilege WHERE resource = ?',
            array('poc')
        );
        $this->getHelper()->insert('privilege', array('resource' => 'incident', 'action' => 'comment'));
        $this->getHelper()->insert('privilege', array('resource' => 'incident', 'action' => 'delete'));
        $this->getHelper()->insert(
            'privilege',
            array('resource' => 'incident', 'action' => 'manage_response_strategies')
        );
    }

    protected function _updateRoles()
    {
        $this->getHelper()->exec(
            'UPDATE role SET name = ? WHERE nickname = ?',
            array('Administrator', 'ADMIN')
        );
        if (count($this->getHelper()->query('SELECT id FROM role WHERE name = ?', array('Administrator'))) === 0) {
            $this->getHelper()->insert(
                'role',
                array(
                    'name' => 'Administrator',
                    'nickname' => 'ADMIN'
                )
            );
        }
        if (count($this->getHelper()->query('SELECT id FROM role WHERE name = ?', array('Power User'))) === 0) {
            $this->getHelper()->insert(
                'role',
                array(
                    'name' => 'User',
                    'nickname' => 'USER'
                )
            );
        }
        if (count($this->getHelper()->query('SELECT id FROM role WHERE name = ?', array('Viewer'))) === 0) {
            $this->getHelper()->insert(
                'role',
                array(
                    'name' => 'Viewer',
                    'nickname' => 'VIEWER'
                )
            );
        }
    }

    protected function _assignPrivileges()
    {
        $builtinRoles = array('Administrator', 'Power User', 'Limited User', 'Viewer');
        $inExpr = 'IN (' . implode(',', array_fill(0, count($builtinRoles), '?')) . ')';
        $this->getHelper()->exec(
            'DELETE FROM role_privilege '
            . 'WHERE roleid IN ('
            . '  SELECT id FROM role WHERE name ' . $inExpr
            . ')',
            $builtinRoles
        );
        $fh = fopen(dirname(__FILE__) . '/builtin-roles.csv', 'r');
        while ($row = fgetcsv($fh)) {
            $this->getHelper()->exec(
                'INSERT INTO role_privilege '
                . 'SELECT r.id, p.id '
                . 'FROM role r, privilege p '
                . 'WHERE r.name = ? AND p.resource = ? AND p.action = ?',
                $row
            );
        }
        fclose($fh);
    }

    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->_updatePrivileges();
        $this->_updateRoles();
        $this->_assignPrivileges();
    }
}