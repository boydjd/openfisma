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
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_040000_Selection extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $helper = $this->getHelper();

        $this->message('Create System <-> SecurityControl relationship');
        $helper->createTable(
            'system_security_control',
            array(
                'systemid' => "bigint(20) NOT NULL DEFAULT '0'",
                'securitycontrolid' => "bigint(20) NOT NULL DEFAULT '0'",
                'common' => "tinyint(1) DEFAULT '0'",
                'imported' => "tinyint(1) DEFAULT '0'",
                'enhancements' => "text"
            ),
            array('systemid', 'securitycontrolid')
        );
        $helper->addForeignKey('system_security_control', 'systemid', 'system', 'id');
        $helper->addForeignKey(
            'system_security_control', 'securitycontrolid', 'security_control', 'id');
        $helper->addIndex(
            'system_security_control',
            'securitycontrolid',
            'system_security_control_securitycontrolid_security_control_id'
        );
        $helper->dropIndexes('system_security_control', array('securitycontrolid_idx', 'systemid_idx'));
    }
}
