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
 * Add Tag entity and fields to incident
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030100_IncidentTags extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->getHelper()->createTable(
            'tag',
            array(
                'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'tagid' => 'text COLLATE utf8_unicode_ci',
                'labels' => 'text COLLATE utf8_unicode_ci'
            ),
            'id'
        );

        $this->getHelper()->addColumn( 'incident', 'severitylevel', 'text NULL', 'pocid');
        $this->getHelper()->addColumn( 'incident', 'source', 'text NULL', 'severitylevel');
        $this->getHelper()->addColumn( 'incident', 'impact', 'text NULL', 'source');

        $priv = array('resource' => 'incident');
        $priv['action'] = 'manage_impacts';
        $priv['description'] = 'Manage Incident Impacts';
        $impactsId = $this->getHelper()->insert('privilege', $priv);
        $priv['action'] = 'manage_severity_levels';
        $priv['description'] = 'Manage Severity Levels';
        $securityLevelsId = $this->getHelper()->insert('privilege', $priv);
        $priv['action'] = 'manage_sources';
        $priv['description'] = 'Manage Incident Sources';
        $sourcesId = $this->getHelper()->insert('privilege', $priv);

        $roleQuery = 'SELECT rp.roleid '
                   . 'FROM role_privilege rp '
                   . 'INNER JOIN privilege p ON rp.privilegeid = p.id '
                   . 'WHERE p.resource = ? AND p.action = ?';
        $roles = $this->getHelper()->query($roleQuery, array('ir_workflow_def', 'create'));
        foreach ($roles as $role) {
            $this->getHelper()->insert('role_privilege', array('roleid' => $role->roleid, 'privilegeid' => $impactsId));
            $this->getHelper()
                 ->insert('role_privilege', array('roleid' => $role->roleid, 'privilegeid' => $securityLevelsId));
            $this->getHelper()->insert('role_privilege', array('roleid' => $role->roleid, 'privilegeid' => $sourcesId));
        }

        $newTags = array(
            'incident-severity-level' => array('Low', 'Moderate', 'High', 'Critical'),
            'incident-source' => array('IDS', 'Antivirus', 'Human', 'Keylogger'),
            'incident-impact' => array('False Positive', 'Compromised', 'Inconclusive', 'Protection In Place')
        );
        foreach ($newTags as $k => $v) {
            $this->getHelper()->insert('tag', array('tagid' => $k, 'labels' => serialize($v)));
        }
    }
}

