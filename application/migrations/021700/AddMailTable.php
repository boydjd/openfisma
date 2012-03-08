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
 * This migration adds the migration table.
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021700_AddMailTable extends Fisma_Migration_Abstract
{
    /**
     * Create the mail table
     */
    public function migrate()
    {
        $columns = array(
            'id'            => "bigint(20) NOT NULL AUTO_INCREMENT",
            'recipient'     => "varchar(255) NOT NULL COMMENT 'The recipient email address'",
            'recipientname' => "varchar(255) DEFAULT NULL COMMENT 'The recipient name.'",
            'sender'        => "varchar(255) DEFAULT NULL COMMENT 'The sender email address'",
            'sendername'    => "varchar(255) DEFAULT NULL COMMENT 'The name for sender mail'",
            'subject'       => "varchar(255) NOT NULL COMMENT 'The subject for mail.'",
            'body'          => "text NOT NULL COMMENT 'Email body text.'"
        );

        $this->message("Creating mail table…");
        $this->getHelper()->createTable('mail', $columns, 'id');
    }
}
