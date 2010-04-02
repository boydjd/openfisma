<?php
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
 * Remove escaping done by the plaintext purifier 
 * 
 * @package Migration
 * @version $Id: 1268179656_version29.php 3090 2010-03-10 14:10:59Z jboyd $
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version31 extends Doctrine_Migration_Base
{
    /**
     * Fix escaping on all columns that are plaintext purified 
     * 
     * @access public
     * @return void
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();

        $models = array(
            'Evaluation' => array('name', 'nickname')
        );

        try {
            $conn->beginTransaction();

            foreach ($models as $modelName => $columns) {
                $collection = Doctrine::getTable($modelName)->findAll();
                foreach ($collection as $item) {
                    foreach ($columns as $column) {
                        if (!empty($item->$column))
                            $item->$column = $this->_fixEscaping($item->$column);
                    }
                }
                $collection->save();
                $collection->free();
                unset($collection);
            }

            $conn->commit();
        } catch(Doctrine_Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * String transformation cannot be reversed 
     * 
     * @access public
     * @return void
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }

    /**
     * Keep decoding escaping until there's no more decoding to be done 
     * 
     * @param mixed $value 
     * @return string 
     */
    private function _fixEscaping($value)
    {
        do {
            $value = htmlspecialchars_decode($value);
        } while ($value != htmlspecialchars_decode($value));

        return $value;
    }
}
