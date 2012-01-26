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
        $result = $this->_db->exec("DROP TABLE `$tableName`");

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

    /**
     * Add a single column to a table.
     *
     * The column definition has the following possible keys, with required keys denoted by *.
     *
     * type*         string   A MySQL data type, e.g. integer(10).
     * nullable      boolean  Defaults to true.
     * after         string   The name of the column that this column is placed after.
     * default       string   The default value of the field, or none if not specified.
     * autoIncrement boolean  Whether this column autoincrements. Defaults to false.
     * unique        boolean  Whether this column is unique. Defaults to false. (Cannot be combined with 'primary'.)
     * primary       boolean  Whether this column is the primary key. Defaults to false.
     * comment       string   The comment on this column. (Not required but highly recommended.)
     *
     * @param string $table
     * @param string $column
     * @param array $columnDefinition
     */
    public function addColumn($table, $column, $columnDefinition)
    {
        // Sanity check
        if (empty($table) || empty($column) || !isset($columnDefinition['type'])) {
            $message = "Table name, column name, and type fields are required when adding a column.";

            throw new Fisma_Zend_Exception_Migration($message);
        }

        if (isset($columnDefinition['unique']) && isset($columnDefinition['primary']) &&
            $columnDefinition['unique'] === true && $columnDefinition['primary'] === true) {

            $message = "A column cannot have both unique and primary keys."
                     . " You probably want to use a primary key only.";

            throw new Fisma_Zend_Exception_Migration($message);
        }

        // Render view
        $view = $this->_createView();
        $view->table = $table;
        $view->column = $column;

        foreach ($columnDefinition as $key => $value) {
            $view->$key = $value;
        }

        $alterTableSql = $view->render('add_column.phtml');
        $this->_db->exec($alterTableSql);
    }

    /**
     * Add columns to a table
     *
     * @see addColumn
     * @param $table
     * @param array $columns Array of column definitions with key = column name and value = column definition.
     */
    public function addColumns($table, $columns)
    {
        foreach ($columns as $columnName => $columnDefinition) {
            $this->addColumn($table, $columnName, $columnDefinition);
        }
    }

    /**
     * Drop a single column on a single table.
     *
     * @param string $table
     * @param string $column
     */
    public function dropColumn($table, $column)
    {
        $view = $this->_createView();
        $view->table = $table;
        $view->column = $column;

        $alterTableSql = $view->render('drop_column.phtml');
        $this->_db->exec($alterTableSql);
    }

    /**
     * Drop the specified columns on a single table.
     *
     * @param string $table
     * @param array $columns Array of column names to drop
     */
    public function dropColumns($table, $columns)
    {
        foreach ($columns as $column) {
            $this->dropColumn($table, $column);
        }
    }

    /**
     * Add an index to a single table.
     *
     * @param string $table
     * @param array|string $columns Array of column names to include in this index or a single column name.
     * @param string $index If not specified and there is only 1 column, the index name is derived from the column.
     */
    public function addIndex($table, $columns, $index = null)
    {
        $view = $this->_createView();

        $view->table = $table;

        if ($index) {
            $view->index = $index;
        } else {
            if (!is_array($columns) || count($columns) == 1) {
                // This naming convention mirror's Doctrine's
                $view->index = (is_array($columns) ? $columns[0] : $columns) . '_idx';
            } else {
                throw new Fisma_Zend_Exception_Migration("Index name is required when using more than 1 column.");
            }
        }

        if (is_array($columns)) {
            $view->columns = implode(', ', array_map(function($v) {return "`$v`";}, $columns));
        } else {
            $view->columns = "`$columns`";
        }

        $alterTableSql = $view->render('add_index.phtml');
        $this->_db->exec($alterTableSql);
    }

    /**
     * Drop the specified indexes on a single table.
     *
     * @param string $table
     * @param array|string $indexes Array of index names to drop or a single name.
     */
    public function dropIndexes($table, $indexes)
    {
        if (is_array($indexes)) {
            foreach ($indexes as $index) {
                $this->dropIndexes($table, $index);
            }
        } else {
            $view = $this->_createView();
            $view->table = $table;
            $view->index = $indexes;

            $alterTableSql = $view->render('drop_key_or_index.phtml');
            $this->_db->exec($alterTableSql);
        }
    }

    /**
     * Add a foreign key.
     *
     * @param string $table
     * @param string $name The name of the constraint. If omitted, it's inferred from the other parameters.
     * @param string $column The name of the column to add the constraint on.
     * @param string $refTable The name of the table that the constraint references.
     * @param string $refColumn The column that the constraint references.
     */
    public function addForeignKey($table, $name = null, $column, $refTable, $refColumn)
    {
        $view = $this->_createView();

        $view->table = $table;
        $view->column = $column;
        $view->refTable = $refTable;
        $view->refColumn = $refColumn;

        if ($name) {
            $view->name = $name;
        } else {
            // This naming convention mirror's Doctrine's
            $view->name = "{$table}_{$column}_{$refTable}_{$refColumn}";
        }

        $alterTableSql = $view->render('add_foreign_key.phtml');
        $this->_db->exec($alterTableSql);
    }

    /**
     * Drop the specified columns on a single table.
     *
     * @param string $table
     * @param array|string $foreignKeys Array of foreign key names to drop or a single name.
     */
    public function dropForeignKeys($table, $foreignKeys)
    {
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $foreignKey) {
                $this->dropForeignKeys($table, $foreignKey);
            }
        } else {
            $view = $this->_createView();
            $view->table = $table;
            $view->foreignKey = $foreignKeys;

            $alterTableSql = $view->render('drop_key_or_index.phtml');
            $this->_db->exec($alterTableSql);
        }
    }
}
