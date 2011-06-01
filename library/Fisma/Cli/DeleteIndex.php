<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Delete a specific search index
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_DeleteIndex extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     * 
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'model|m=w' => "Name of model to delete index for",
            'all|a' => 'Delete ALL models\' indexes. Mutually exclusive with --model option.'
        );
    }    
    
    /**
     * Drop the index specified on the command line, or if none is specified, drop and rebuild ALL indexes
     */
    protected function _run()
    {     
        $modelName = $this->getOption('model');
        $allModels = $this->getOption('all');

        // The two options are mutually exclusive
        if ( (is_null($modelName) && is_null($allModels)) || (!is_null($modelName) && !is_null($allModels)) ) {

            throw new Fisma_Zend_Exception_User("You must specify either a model or the all option, but not both.");
        }

        $searchEngine = Zend_Registry::get('search_engine');

        if (!is_null($allModels)) {
            $searchEngine->deleteAll($modelName);
        } else {
            $searchEngine->deleteByType($modelName);
        }
    }
}
