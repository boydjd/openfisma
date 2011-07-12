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
 * Add user_comment table
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version95 extends Doctrine_Migration_Base
{
    /**
     * Create user_comment table
     */
    public function up()
    {
        $this->createTable('user_comment', array(
            'id' => 
            array(
                'primary' => '1',
                'autoincrement' => '1',
                'type' => 'integer',
                'length' => 8,
            ),
            'createdts' => 
            array(
                'comment' => 'The timestamp when this entry was created',
                'type' => 'timestamp',
                'length' => 25,
            ),
            'comment' => 
            array(
                'comment' => 'The text of the comment',
                'type' => 'string',
                'length' => '',
            ),
            'objectid' => 
            array(
                'comment' => 'The parent object to which this comment belongs',
                'type' => 'integer',
                'length' => 8,
            ),
            'userid' => 
            array(
                'comment' => 'The user who created comment',
                'type' => 'integer',
                'length' => 8,
            ),
        ), array(
            'indexes' => 
            array(
            ),
            'primary' => 
            array(
                0 => 'id',
            ),
        ));

        // Add foreign keys for user_comment table
        $this->createForeignKey('user_comment', 'user_comment_objectid_user_id', array(
            'name' => 'user_comment_objectid_user_id',
            'local' => 'objectid',
            'foreign' => 'id',
            'foreignTable' => 'user',
        ));
        $this->createForeignKey('user_comment', 'user_comment_userid_user_id', array(
            'name' => 'user_comment_userid_user_id',
            'local' => 'userid',
            'foreign' => 'id',
            'foreignTable' => 'user',
        ));
    }

    /**
     * Drop user_comment foreign key and table
     * 
     * @return void
     */
    public function down()
    {
        $this->dropForeignKey('user_comment', 'user_comment_objectid_user_id');
        $this->dropForeignKey('user_comment', 'user_comment_userid_user_id');

        $this->dropTable('user_comment');
    }
}
