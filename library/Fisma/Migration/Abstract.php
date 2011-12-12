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
 * The base class for migrations.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma_Migration
 */
abstract class Fisma_Migration_Abstract
{
    /**
     * The namespace that prefixes all migration classes.
     *
     * This is used to figure out the version number and name of a migration from its class name.
     *
     * @param string
     */
    const CLASS_NAME_PREFIX = "Application_Migration_";

    /**
     * A reference to a PDO database handle.
     *
     * @var PDO
     */
    private $_db;

    /**
     * Run this migration step.
     *
     * @param PDO
     */
    abstract function migrate();

    /**
     * Return the 6 digit version number associated with this migration.
     *
     * E.g. 021700 is returned for "2.17.0".
     *
     * @return string Returned as a string because it may have a leading zero that is meaningful to us.
     */
    public function getVersion()
    {
        $className = get_class($this);
        $versionString = substr($className, strlen(self::CLASS_NAME_PREFIX), 6);

        return Fisma_Version::createVersionFromPaddedString($versionString);
    }

    /**
     * Return the name of this migration.
     *
     * The name is deteremind by removing the prefix and version number from the class name.
     *
     * @return string
     */
    public function getName()
    {
        $className = get_class($this);

        return substr($className, strlen(self::CLASS_NAME_PREFIX) + 7);
    }

    /**
     * Set the current PDO database handle.
     *
     * @param PDO $db
     */
    public function setDb($db)
    {
        $this->_db = $db;
    }

    /**
     * Get the current PDO database handle.
     *
     * @return PDO
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * Create a view object that can be used to render migration templates, such as SQL query templates.
     *
     * @return Fisma_Zend_View
     */
    protected function _createView()
    {
        $view = new Fisma_Zend_View();

        $view->setScriptPath(Fisma::getPath('migrationViews'))
             ->setEncoding('utf-8');

        return $view;
    }

    /**
     * Create a table.
     *
     * @param string $tableName
     * @param array $columns Keys are column names and values are the SQL definitions.
     * @param string $primaryKey This must be one of the key values in $columns.
     */
    protected function _createTable($tableName, $columns, $primaryKey)
    {
        $view = $this->_createView();

        $view->tableName = $tableName;
        $view->columns = $columns;
        $view->primaryKey = $primaryKey;

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
    protected function _dropTable($tableName)
    {
        $statement = $this->getDb()->prepare("DROP TABLE $tableName");
        $result = $statement->execute();

        if ($result === FALSE) {
            throw new Fisma_Zend_Exception_Migration("Not able to drop table ($tableName).");
        }
    }

    /**
     * Check whether a table exists with the specified name.
     *
     * @param string $tableName
     * @return bool
     */
    protected function _tableExists($tableName)
    {
        $statement = $this->getDb()->prepare("SHOW TABLES LIKE :tableName");

        $statement->bindValue('tableName', addcslashes($tableName, '%_'), PDO::PARAM_STR);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return ($result !== FALSE);
    }
}
