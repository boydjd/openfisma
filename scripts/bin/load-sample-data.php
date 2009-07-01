#!/usr/bin/env php
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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));
Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
LoadSampleData::run($argv);

/**
 * Load sample data from doctrine/data/sample
 *
 * @package   Scripts
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class LoadSampleData 
{
    /**
     * Run the command line application
     * 
     * @param array $argv PHP's command line arguments
     */
    public static function run($argv) 
    {
        $startTime = time();

        Fisma::connectDb();
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);
        
        // Loading large data sets can require outsize memory
        ini_set('memory_limit', '512M');
    
        print "This may take several minutes...\n";
        $sampleDataPath = Fisma::getPath('sampleData');
        Doctrine::loadData($sampleDataPath);
        
        $stopTime = time();
        print("Elapsed time: " . ($stopTime - $startTime) . " seconds\n");
    }
}
