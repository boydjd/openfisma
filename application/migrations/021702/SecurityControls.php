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
 * Tweaks to Security Controls and Security Control Catalogs
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021702_SecurityControls extends Fisma_Migration_Abstract
{
    /**
     */
    public function migrate()
    {
        $this->getHelper()->addColumn('security_control_catalog', 'description', 'text NULL', 'name');
        $this->getHelper()->addColumn(
            'security_control_catalog',
            'published', "tinyint(1) NOT NULL DEFAULT '0'",
            'description'
        );

        $default = $this->getHelper()->query('SELECT default_security_control_catalog_id FROM configuration');
        $default = $default[0];
        $default = $default->{'default_security_control_catalog_id'};
        $this->getHelper()->update('security_control_catalog', array('published' => 1), array('id' => $default));
        $newDescription = array(
            'description' => 'Recommended Security Controls for Federal Information Systems and Organizationsa'
        );
        $this->getHelper()->update('security_control_catalog', $newDescription, array('1' => 1));

        $catalog = array(
            'name' => 'NIST SP  800-79',
            'description' => 'Guidelines for the Accreditation of Personal Identity Verification Card Issuers'
        );
        $catalogId = $this->getHelper()->insert('security_control_catalog', $catalog);

        $controlFile = fopen(dirname(__FILE__) . '/SecurityControls.csv', 'r');
        while ($row = fgetcsv($controlFile)) {
            $control = array_combine(array('code', 'name', 'family', 'control', 'externalreferences'), $row);
            $control['securitycontrolcatalogid'] = $catalogId;
            $this->getHelper()->insert('security_control', $control);
        }
        fclose($controlFile);

        $this->getHelper()->dropColumn('configuration', 'default_security_control_catalog_id');
        $this->getHelper()->dropColumn('security_control', 'class');

        $this->_addPrivileges();
    }

    private function _addPrivileges()
    {
        $privs = array(
            'create' => 'Create Security Control Catalog',
            'read' => 'View Security Control Catalog',
            'update' => 'Edit Security Control Catalog',
            'delete' => 'Delete Security Control Catalog'
        );
        $adminId = $this->getHelper()->query("SELECT id FROM role WHERE nickname = 'ADMIN'");
        $adminId = $adminId[0]->id;

        foreach ($privs as $action => $desc) {
            $priv = array('resource' => 'security_control_catalog', 'action' => $action, 'description' => $desc);
            $id = $this->getHelper()->insert('privilege', $priv);
            $this->getHelper()->insert('role_privilege', array('roleId' => $adminId, 'privilegeId' => $id));
        }
    }
}
