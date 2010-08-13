#!/usr/bin/env php
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

$script = new OptimizeIndexes();
$script->run();

/**
 * Optimizes (and creates if necessary) Lucene indexes for OpenFISMA
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Scripts
 * @version    $Id$
 */
class OptimizeIndexes
{
    /**
     * Create a script object and connect it to the Fisma library
     * 
     * @return void
     */
    public function __construct() 
    {
        require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::setConfiguration(new Fisma_Configuration_Database);
        Fisma::connectDb();
        
        // @todo Is this still needed?
        ini_set('memory_limit', '512M');
    }
        
    /**
     * The script's entry point
     * 
     * @return void
     */
    public function run()
    {
        print "This may take quite a long time...\n";
        $start = time();

        // Enumerate and index the models which are eligible for indexing 
        $indexEnumerator = new Fisma_Search_Index_Enumerator();

        $searchableClasses = $indexEnumerator->getSearchableClasses(Fisma::getPath('model'));
        
        $indexBuilder = new Fisma_Search_Index_Builder();
        
        foreach ($searchableClasses as $searchableClass) {
            echo "Indexing: $searchableClass\n";

            $indexBuilder->buildIndexForClass($searchableClass);
        }

        // Calculate elapsed time
        $stop = time();
        $elapsed = $stop - $start;
        $minutes = floor($elapsed/60);
        $seconds = $elapsed - ($minutes * 60);
        
        print "Finished in $minutes minutes and $seconds seconds\n";
    }
}
