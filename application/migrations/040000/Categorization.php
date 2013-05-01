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
class Application_Migration_040000_Categorization extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $helper = $this->getHelper();

        $this->message('Update privileges');
        $roleIds = $helper->query('SELECT id from role where name in ("Administrator", "User") order by name');
        $adminId = $roleIds[0]->id;
        $userId = $roleIds[1]->id;

        $areaPrivilege = $helper->insert(
            'privilege',
            array(
                'resource' => 'area',
                'action' => 'sa',
                'description' => 'View Security Authorizations Menu'
            )
        );
        $helper->insert('role_privilege', array('roleid' => $adminId, 'privilegeid' => $areaPrivilege));
        $helper->insert('role_privilege', array('roleid' => $userId, 'privilegeid' => $areaPrivilege));

        $saPrivilege = $helper->insert(
            'privilege',
            array(
                'resource' => 'organization',
                'action' => 'sa',
                'description' => 'Performance Security Authorizations on Systems'
            )
        );
        $helper->insert('role_privilege', array('roleid' => $adminId, 'privilegeid' => $saPrivilege));
        $helper->insert('role_privilege', array('roleid' => $userId, 'privilegeid' => $saPrivilege));

        $dtPrivilege = $helper->insert(
            'privilege',
            array(
                'resource' => 'information_data_type',
                'action' => 'manage',
                'description' => 'Manage Information Data Types'
            )
        );
        $helper->insert('role_privilege', array('roleid' => $adminId, 'privilegeid' => $dtPrivilege));
        $helper->insert('role_privilege', array('roleid' => $userId, 'privilegeid' => $dtPrivilege));

        $dtcPrivilege = $helper->insert(
            'privilege',
            array(
                'resource' => 'information_data_type_catalog',
                'action' => 'manage',
                'description' => 'Manage Information Data Type Catalogs'
            )
        );
        $helper->insert('role_privilege', array('roleid' => $adminId, 'privilegeid' => $dtcPrivilege));
        $helper->insert('role_privilege', array('roleid' => $userId, 'privilegeid' => $dtcPrivilege));

        $this->message('Create 2 new tables');
        $helper->createTable(
            'information_data_type_catalog',
            array(
                'id' => "bigint(20) NOT NULL AUTO_INCREMENT",
                'name' => "char(255) DEFAULT NULL",
                'description' => "text",
                'published' => "tinyint(1) NOT NULL DEFAULT '0'"
            ),
            'id'
        );
        $helper->createTable(
            'information_data_type',
            array(
                'id' => "bigint(20) NOT NULL AUTO_INCREMENT",
                'category' => "varchar(255) DEFAULT NULL",
                'subcategory' => "varchar(255) DEFAULT NULL",
                'catalogid' => "bigint(20) DEFAULT NULL",
                'confidentiality' => "enum('LOW','MODERATE','HIGH') DEFAULT NULL",
                'defaultconfidentiality' => "enum('LOW','MODERATE','HIGH') DEFAULT NULL",
                'integrity' => "enum('LOW','MODERATE','HIGH') DEFAULT NULL",
                'defaultintegrity' => "enum('LOW','MODERATE','HIGH') DEFAULT NULL",
                'availability' => "enum('LOW','MODERATE','HIGH') DEFAULT NULL",
                'defaultavailability' => "enum('LOW','MODERATE','HIGH') DEFAULT NULL",
                'description' => "text"
            ),
            'id'
        );
        $helper->addUniqueKey(
            'information_data_type',
            array('category', 'subcategory', 'catalogid'),
            'category_index_idx'
        );
        $helper->addForeignKey('information_data_type', 'catalogid', 'information_data_type_catalog', 'id');

        $this->message('Insert fixtures');
        $helper->insert(
            'information_data_type_catalog',
            array(
                'id' => '1',
                'name' => 'NIST SP 800-60',
                'description' =>
                    'Guide for Mapping Types of Information and Information Systems to Security Categories',
                'published' => '1'
            )
        );
        foreach ($this->_getInformationDataTypeArray() as $dataType) {
            $helper->insert('information_data_type', $dataType);
        }

        $this->message('Create System <-> InformationDataType relationship');
        $helper->createTable(
            'system_information_data_type',
            array(
                'systemid' => "bigint(20) NOT NULL DEFAULT '0'",
                'informationdatatypeid' => "bigint(20) NOT NULL DEFAULT '0'",
                'denormalizeddatatype' => "text"
            ),
            array('systemid', 'informationdatatypeid')
        );
        $helper->addForeignKey('system_information_data_type', 'systemid', 'system', 'id');
        $helper->addForeignKey(
            'system_information_data_type', 'informationdatatypeid', 'information_data_type', 'id', 'siii');
        $helper->addIndex('system_information_data_type', 'informationdatatypeid', 'siii');
        $helper->dropIndexes('system_information_data_type', array('informationdatatypeid_idx', 'systemid_idx'));
    }

    private function _getInformationDataTypeArray()
    {
        $informationDataType = array();
        include(realpath(dirname(__FILE__) . '/information_data_type.inc'));
        return $informationDataType;
    }
}
