<?php
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
 * Converts existing audit log entries by removing <em> tags and changing <br> or <p> into newlines.
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version97 extends Doctrine_Migration_Base
{
    /**
     * Replace <em> tag with ** and changing <br> or <p> into newlines in message column of audit log tables
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();
        $auditLogTables = array('finding_audit_log', 'incident_audit_log', 'vulnerability_audit_log', 'user_audit_log');
        foreach ($auditLogTables as $auditLogTable) {
            $updateSql = "UPDATE $auditLogTable SET message = "
                       . $this->_getReplaceStatement()
                       . " WHERE message LIKE '%em>%'"
                       . " OR message like '%p>%'"
                       . " OR message like '%<br>%'"
                       . " OR message like '%&lt;%'"
                       . " OR message like '%&gt;%'"
                       . " OR message like '%&quot;%'"
                       . " OR message like '%&amp;%'";

            $conn->exec($updateSql);
        }
    }

    /**
     * Irreversible
     * 
     * @return void
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }

    /**
     * Define SET clause in order to convert or remove some tags.
     * 
     * @return string The nested replace statement
     */
    private function _getReplaceStatement()
    {
        // Replace <em>No value</em> with **No Value**.
        // Replace </p><p> with '\n\n' and <br> with '\n'.
        // Convert special HTML entities back to characters
        $replaceArray = array(
            array('<em>', '**'),
            array('</em>', '**'),
            array('</p><p>', '\n\n'),
            array('<p>', ''),
            array('</p>', ''),
            array('<br>', '\n'),
            array('&lt;', '<'),
            array('&gt;', '>'),
            array('&quot;', '"'),
            array('&amp;', '&')
        );

        // Nested replace statement
        foreach ($replaceArray as $key => $value) {
            if (0 === $key) {
                $replaceStatement = "REPLACE(message, '$value[0]', '$value[1]')";
            } else {
                $replaceStatement = "REPLACE($replaceStatement, '$value[0]', '$value[1]')";
            }
        }

        return $replaceStatement;
    }
}
