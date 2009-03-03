<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: Rowlower.php 1371 2009-02-02 07:11:56Z woody712 $
 * @package   Plugin_Initialize
 */

/**
 * This Class help to process Unit Test for Fisma
 *
 * @package   Plugin_Initialize
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Plugin_Initialize_Unittest extends Plugin_Initialize_Webapp
{
    /**
     * Initialize the datebase resource
     */
    public function initDb()
    {
        parent::initDb();
        try {
            $config = new Zend_Config_Ini(
                $this->_root."/tests/unit/data/database.ini"
            );
            if (!empty($config->database)) {
                Zend_Registry::set('datasource', $config->database);
                $config = Zend_Registry::get('datasource');
                $db = Zend_Db::factory($config);
                Zend_Db_Table::setDefaultAdapter($db);
                Zend_Registry::set('db', $db);
                return;
            }
        } catch (Zend_Config_Exception $e) {
            echo $e->getMessage();
        }
        echo " Using installed db\n";
    }
}
