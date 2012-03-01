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
 * A utility class for doing common things migrations need to do.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Migration
 */
class Fisma_Migration_Helper
{
    /**
     * A reference to a PDO database handle.
     *
     * @var PDO
     */
    private $_db;

    /**
     * Construct a helper.
     *
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->_db = $db;
    }

    /**
     * Create a view object that can be used to render migration templates, such as SQL query templates.
     *
     * @return Fisma_Zend_View
     */
    private function _createView()
    {
        $view = new Fisma_Zend_View();

        $view->setScriptPath(Fisma::getPath('migrationViews'))
             ->setEncoding('utf-8');

        return $view;
    }

    /**
     * Execute an SQL statement
     *
     * @param string $sql
     * @return bool|int Number of modified records
     */
    public function exec($sql)
    {
        if (($rval = $this->_db->exec($sql)) === FALSE) {
            throw new Fisma_Zend_Exception_Migration("Not able to execute query: " . $sql);
        }
        return $rval;
    }

    /**
     * Execute an SQL query
     *
     * @param string $sql
     * @return array Query results
     */
    public function execute($sql, $params = array())
    {
        $stmt = $this->_db->prepare($sql);
        if ($stmt->execute($params) === FALSE) {
            throw new Fisma_Zend_Exception_Migration("Not able to execute query: " . $sql);
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Check whether a table exists with the specified name.
     *
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName)
    {
        $statement = $this->_db->prepare("SHOW TABLES LIKE :tableName");

        $statement->bindValue('tableName', addcslashes($tableName, '%_'), PDO::PARAM_STR);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return ($result !== FALSE);
    }

    /**
     * Create a table.
     *
     * @param string $tableName
     * @param array $columns Keys are column names and values are the SQL definitions.
     * @param string $primaryKey This must be one of the key values in $columns.
     */
    public function createTable($tableName, $columns, $primaryKey)
    {
        $view = $this->_createView();

        $view->tableName = $tableName;
        $view->columns = $columns;
        $view->primaryKey = implode((array)$primaryKey, '`,`');

        $createTableSql = $view->render('create_table.phtml');

        $result = $this->_db->exec($createTableSql);

        if ($result === FALSE) {
            throw new Fisma_Zend_Exception_Migration("Not able to create table ($tableName).");
        }
    }

    /**
     * Drop the specified table.
     *
     * @param string $tableName
     */
    public function dropTable($tableName)
    {
        $result = $this->_db->exec("DROP TABLE $tableName");

        if ($result === FALSE) {
            throw new Fisma_Zend_Exception_Migration("Not able to drop table ($tableName).");
        }
    }

    /**
     * Inserts a record into a table
     *
     * @param string $table Table
     * @param array $fields Associative array of field => value pairs
     * @return int Auto-generated idea of the new record
     */
    public function insert($table, $fields)
    {
        $fieldNames = array_keys($fields);
        $fieldValues = array_values($fields);
        $fieldList = implode($fieldNames, ',');
        $valueList = implode(array_fill(0, count($fieldValues), '?'), ',');
        $sql = sprintf('INSERT INTO %s (%s) VALUES(%s)', $table, $fieldList, $valueList);
        $stmt = $this->_db->prepare($sql);
        if (!$stmt->execute($fieldValues)) {
            throw new Exception('Unable to insert record.');
        }
        return $this->_db->lastInsertId();
    }

    /**
     * Update fields on one or more records
     *
     * @param string $table Name of the table on which the update is to be performed
     * @param array $fields An array of field => value pairs to be set
     * @param array $where  An array of field => value pairs to use to constrain the update.  By default, all records
     *                      will be updated.
     */
    public function update($table, $fields, $where)
    {
        $setArray = array_keys($fields);
        foreach ($setArray as &$f) {
            $f .= ' = ?';
        }
        $setClause = implode($setArray, ', ');
        $whereArray = array_keys($where);
        foreach ($whereArray as &$w) {
            $w .= ' = ?';
        }
        $whereClause = implode($whereArray, ' AND ');
        $update = 'UPDATE %s SET %s WHERE %s';
        $sql = sprintf($update, $table, $setClause, $whereClause);
        $stmt = $this->_db->prepare($sql);
        $params = array_values($fields);
        array_splice($params, count($params), 0, array_values($where));
        $stmt->execute($params);
    }
}
