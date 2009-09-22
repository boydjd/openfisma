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
 * @author    Mark E. Haase
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://openfisma.org/content/license
 * @version   $Id$
 * @package   Migration
 */

/**
 * The finding evaluation contains a comment field that is varchar(255). Convert it to a TEXT or CLOB field.
 *
 * @package    Migration
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class ConvertCommentFromVarcharToClob extends Doctrine_Migration_Base
{
    /**
     * Convert from string(255) to string. 
     * 
     * In the native DB, this will result in a change from VARCHAR to TEXT or CLOB
     * 
     * @param $direction either 'up' or 'down'
     */
    public function up()
    {
        $this->changeColumn('finding_evaluation', 'comment', null, 'string');

        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
    }
    
    /**
     * Convert from string to string(255). 
     * 
     * @param $direction either 'up' or 'down'
     */
    public function down()
    {
        $this->changeColumn('finding_evaluation', 'comment', 255, 'string');

        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
    }
}
