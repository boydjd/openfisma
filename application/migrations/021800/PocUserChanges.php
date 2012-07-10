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
 * Changes to Poc and User models.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021800_PocUserChanges extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        // check for duplicates
        $dups = $this->getHelper()->query('SELECT email, COUNT(1) cnt FROM poc GROUP BY email HAVING cnt > 1');
        foreach ($dups as $dup) {
            // form valid alternate e-mail addresses
            list($name, $domain) = explode('@', $dup->email, 2);
            // get a list of entities with this email account
            $pocs = $this->getHelper()->query('SELECT id FROM poc WHERE email = ?', array($dup->email));
            for ($i = 1; $i < count($pocs); $i++) {
                $newAddress = "$name.$i@$domain";
                $this->getHelper()->exec('UPDATE poc SET email = ? WHERE id = ?', array($newAddress, $pocs[$i]->id));
                $this->message('Duplicate email "' . $dup->email . '" updated to "' . $newAddress . '"');
            }
        }

        $this->_dropStuff();

        // make schema changes
        $this->getHelper()->modifyColumn('poc', 'email', 'varchar(255) NOT NULL', 'namelast');
        $this->getHelper()->modifyColumn(
            'poc',
            'locktype',
            "enum('manual','password','inactive','expired')",
            'reportingorganizationid'
        );

        $this->getHelper()->exec("RENAME TABLE poc TO user");

        $this->_createStuff();

        /*
         * Some last-minute tweaks.  doctrine seems inconsistent about its naming conventions and it seems to be
         * throwing everything off.
         */
        $this->getHelper()->exec(
            'ALTER TABLE `ir_incident_user` ' .
            'ADD INDEX `ir_incident_user_userid_user_id` (`userid`) USING BTREE, ' .
            'DROP INDEX `userid_idx`'
        );
        $this->getHelper()->dropIndexes('user_event', 'userid_idx');

        // Add deleted_at column
        $this->getHelper()->addColumn('user', 'deleted_at', 'datetime', 'modifiedts');

        // Add homeurl column
        $this->getHelper()->addColumn(
            'user',
            "homeurl",
            "VARCHAR(255) NOT NULL DEFAULT '/'",
            'mustresetpassword'
        );

        // Add jsonComments field for new commentable behavior (depends on user table)
        $this->_addJsonComments('finding');
        $this->_addJsonComments('incident', 'deleted_at');
        $this->_addJsonComments('vulnerability', 'modifiedts');
        $this->_addJsonComments('user');
    }

    protected function _dropStuff()
    {
        $this->getHelper()->exec(
            'INSERT INTO user_audit_log (userid, createdts, message, objectid) ' .
            'SELECT userid, createdts, message, objectid FROM poc_audit_log'
        );
        $this->getHelper()->dropTable('poc_audit_log');

        $this->getHelper()->exec(
            'INSERT INTO user_comment (createdts, comment, objectid, userid) ' .
            'SELECT createdts, comment, objectid, userid FROM poc_comment'
        );
        $this->getHelper()->dropTable('poc_comment');

        $this->getHelper()->dropColumn('poc', 'type');

        foreach ($this->_foreignKeysToDrop as $table => $keys) {
            $this->gethelper()->dropForeignKeys($table, $keys);
        }
        foreach ($this->_indexesToDrop as $table => $indexes) {
            $this->getHelper()->dropIndexes($table, $indexes);
        }
    }

    protected function _createStuff()
    {
        $this->getHelper()->addColumn('user', 'published', "tinyint(1) NOT NULL DEFAULT '1'", 'locktype');
        $this->getHelper()->addColumn('user', 'displayname', 'text NULL', 'email');

        // initialize displayNames
        $emailExpr = "IF(LENGTH(nameFirst) > 0 AND LENGTH(nameLast) > 0, '', CONCAT('<', email, '>'))";
        $this->getHelper()->exec("UPDATE user SET displayname = TRIM(CONCAT_WS(' ', nameFirst, nameLast, $emailExpr))");

        $this->getHelper()->addUniqueKey('user', array('email'), 'email');

        foreach ($this->_foreignKeysToCreate as $table => $keys) {
            foreach ($keys as $key) {
                $this->getHelper()->addForeignKey($table, $key[0], $key[1], $key[2]);
            }
        }
    }

    protected $_foreignKeysToDrop = array(
        'comment' => 'comment_userid_poc_id',
        'finding' => array('finding_createdbyuserid_poc_id', 'finding_pocid_poc_id'),
        'finding_audit_log' => 'finding_audit_log_userid_poc_id',
        'finding_comment' => 'finding_comment_userid_poc_id',
        'finding_evaluation' => 'finding_evaluation_userid_poc_id',
        'incident' => array('incident_pocid_poc_id','incident_reportinguserid_poc_id'),
        'incident_audit_log' => 'incident_audit_log_userid_poc_id',
        'incident_comment' => 'incident_comment_userid_poc_id',
        'ir_incident_user' => 'ir_incident_user_userid_poc_id',
        'ir_incident_workflow' => 'ir_incident_workflow_userid_poc_id',
        'notification' => 'notification_userid_poc_id',
        'organization' => 'organization_pocid_poc_id',
        'upload' => 'upload_userid_poc_id',
        'poc' => 'poc_reportingorganizationid_organization_id',
        'user_audit_log' => 'user_audit_log_userid_poc_id',
        'user_comment' => 'user_comment_userid_poc_id',
        'user_event' => array('user_event_userid_poc_id'),
        'user_role' => 'user_role_userid_poc_id',
        'vulnerability' => 'vulnerability_createdbyuserid_poc_id',
        'vulnerability_audit_log' => 'vulnerability_audit_log_userid_poc_id',
        'vulnerability_comment' => 'vulnerability_comment_userid_poc_id'
    );

    protected $_indexesToDrop = array(
        'comment' => 'userid_idx',
        'finding' => array('createdbyuserid_idx', 'pocid_idx'),
        'finding_audit_log' => 'userid_idx',
        'finding_comment' => 'userid_idx',
        'finding_evaluation' => 'userid_idx',
        'incident' => array('pocid_idx', 'reportinguserid_idx'),
        'incident_audit_log' => 'userid_idx',
        'incident_comment' => 'userid_idx',
        'ir_incident_user' => array('ir_incident_user_userid_poc_id'),
        'ir_incident_workflow' => 'userid_idx',
        'notification' => 'userid_idx',
        'organization'  => 'pocid_idx',
        'poc' => 'reportingorganizationid_idx',
        'upload'  => 'userid_idx',
        'user_audit_log'  => 'userid_idx',
        'user_comment'  => 'userid_idx',
        'user_role'  => 'userid_idx',
        'vulnerability'  => 'createdbyuserid_idx',
        'vulnerability_audit_log'  => 'userid_idx',
        'vulnerability_comment'  => 'userid_idx'
    );

    protected $_foreignKeysToCreate = array(
        'comment' => array(
            array('userid', 'user', 'id')
        ),
        'finding' => array(
            array('createdbyuserid', 'user', 'id'),
            array('pocid', 'user', 'id')
        ),
        'finding_audit_log' => array(
            array('userid', 'user', 'id')
        ),
        'finding_comment' => array(array('userid', 'user', 'id')),
        'finding_evaluation' => array(array('userid', 'user', 'id')),
        'incident' => array(
            array('pocid', 'user', 'id'),
            array('reportinguserid', 'user', 'id')
        ),
        'incident_audit_log' => array(array('userid', 'user', 'id')),
        'incident_comment' => array(array('userid', 'user', 'id')),
        'ir_incident_user' => array(array('userid', 'user', 'id')),
        'ir_incident_workflow' => array(array('userid', 'user', 'id')),
        'notification' => array(array('userid', 'user', 'id')),
        'organization'  => array(array('pocid', 'user', 'id')),
        'upload'  => array(array('userid', 'user', 'id')),
        'user'  => array(array('reportingorganizationid', 'organization', 'id')),
        'user_audit_log'  => array(array('userid', 'user', 'id')),
        'user_comment'  => array(array('userid', 'user', 'id')),
        'user_event'  => array(array('userid', 'user', 'id')),
        'user_role'  => array(array('userid', 'user', 'id')),
        'vulnerability'  => array(array('createdbyuserid', 'user', 'id')),
        'vulnerability_audit_log'  => array(array('userid', 'user', 'id')),
        'vulnerability_comment'  => array(array('userid', 'user', 'id'))
    );

    /**
     * Adds jsoncomments field to table and populates it with the associated comments
     */
    protected function _addJsonComments($table, $after = null)
    {
        $helper = $this->getHelper();
        $helper->addColumn($table, 'jsoncomments', 'text NULL', $after);
        // get all related comments and build them into a data set
        $comments = $helper->query(
            'SELECT o.id AS oid, ocu.displayName, oc.createdts, oc.comment ' .
            'FROM ' . $table . ' o ' .
            'INNER JOIN ' . $table . '_comment oc ON o.id = oc.objectid ' .
            'INNER JOIN user ocu ON oc.userid = ocu.id ' .
            'ORDER BY oid'
        );
        if (!count($comments)) {
            return; // no comments, we're done
        }
        $toStore = array();
        $currId = $comments[0]->oid;
        foreach ($comments as $comment) {
            if ($comment->oid !== $currId) {
                $json = Zend_Json::encode($toStore);
                $toStore = array();
                $helper->exec("UPDATE $table SET jsoncomments = ? WHERE id = $currId", array($json));
                $currId = $comment->oid;
            }
            $toStore[] = array($comment->displayName, $comment->createdts, $comment->comment);
        }
        $json = Zend_Json::encode($toStore);
        $helper->exec("UPDATE $table SET jsoncomments = ? WHERE id = $currId", array($json));
    }
}
