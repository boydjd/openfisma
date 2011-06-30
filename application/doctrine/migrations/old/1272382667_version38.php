<?php
// @codingStandardsIgnoreFile
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Add finding comment table and finding comment privilege
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version38 extends Doctrine_Migration_Base
{
    public function up()
    {
        // Comment table
		$this->createTable('finding_comment', array(
             'id' => 
             array(
              'primary' => true,
              'autoincrement' => true,
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
              'length' => NULL,
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

        // Foreign keys
		$this->createForeignKey('finding_comment', 'finding_comment_objectid_finding_id', array(
             'name' => 'finding_comment_objectid_finding_id',
             'local' => 'objectid',
             'foreign' => 'id',
             'foreignTable' => 'finding',
             ));
		$this->createForeignKey('finding_comment', 'finding_comment_userid_user_id', array(
             'name' => 'finding_comment_userid_user_id',
             'local' => 'userid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             ));


        // Comment privilege
        $commentPrivilege = new Privilege();

        $commentPrivilege->resource = 'finding';
        $commentPrivilege->action = 'comment';
        $commentPrivilege->description = 'Comment on Finding';

        $commentPrivilege->save();
    }

    public function down()
    {
        // Drop foreign keys
        $this->dropForeignKey('finding_comment', 'finding_comment_objectid_finding_id');
        $this->dropForeignKey('finding_comment', 'finding_comment_userid_user_id');
                
        // Drop comment table
		$this->dropTable('finding_comment');
		
		// Delete comment privilege
		$query = Doctrine_Query::create()
		         ->from('Privilege p')
		         ->where('p.resource = ? AND p.action = ?', array('finding', 'comment'));
		
		$commentPrivilege = $query->execute();

        if ($commentPrivilege) {
		    $commentPrivilege->delete();
		}
    }
}
