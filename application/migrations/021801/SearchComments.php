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
 * Extend commentable behavior to make comments searchable. OFJ-1385
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021801_SearchComments extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        // Add jsonComments field for new commentable behavior (depends on user table)
        $this->_addJsonComments('finding');
        $this->_addJsonComments('incident', 'deleted_at');
        $this->_addJsonComments('vulnerability', 'modifiedts');
        $this->_addJsonComments('user');
    }

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
            $date = new Zend_Date($comment->createdts, Fisma_Date::FORMAT_DATETIME);
            $dt = $date->toString(Fisma_Date::FORMAT_MONTH_DAY_YEAR);
            $tm = $date->toString(Fisma_Date::FORMAT_AM_PM_TIME);
            $toStore[] = array($comment->displayName, $dt . ' at ' . $tm, $comment->comment);
        }
        $json = Zend_Json::encode($toStore);
        $helper->exec("UPDATE $table SET jsoncomments = ? WHERE id = $currId", array($json));
    }
}
