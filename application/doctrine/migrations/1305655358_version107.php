<?php
// @codingStandardsIgnoreFile
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Add unique index for code and securityControlCatalogId
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version107 extends Doctrine_Migration_Base
{
    /**
     * Name of the index.
     *
     * @var string
     */
    protected static $indexName = 'codeSecurityControlCatalogIdIndex';

    /**
     * Definition of the index.
     *
     * @var array
     */
    protected static $indexDef = array('fields' => array(0 => 'code', 1 => 'securitycontrolcatalogid'), 'type' => 'unique');

    /**
     * Migrate up.
     *
     * @return void
     */
    public function up()
    {
        $this->_deleteDuplicates();
        $this->addIndex( 'security_control', self::$indexName, self::$indexDef);
    }

    /**
     * Migrate down.
     *
     * @return void
     */
    public function down()
    {
        $this->removeIndex( 'security_control', self::$indexName, self::$indexDef);
    }

    /**
     * Helper function to delete all duplicate SecurityControls.
     *
     * @return void
     */
    protected function _deleteDuplicates()
    {
        $scCountQuery = ""
            . "SELECT code, securitycontrolcatalogid, COUNT(1) AS cnt "
            . "FROM security_control "
            . "GROUP BY code, securitycontrolcatalogid "
            . "HAVING cnt > 1";
        $scDeleteQuery = ""
            . "DELETE FROM security_control "
            . "WHERE code = ? AND securitycontrolcatalogid = ? "
            . "LIMIT ";
        $conn = Doctrine_Manager::connection();
        $duplicates = $conn->execute($scCountQuery);
        while ($obj = $duplicates->fetchObject()) {
            $deleteStatement = $conn->prepare($scDeleteQuery . ($obj->cnt - 1));
            $deleteStatement->execute(array($obj->code, $obj->securitycontrolcatalogid));
        }
    }
}
