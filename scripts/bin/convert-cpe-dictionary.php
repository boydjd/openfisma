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
 * <http://www.gnu.org/licenses/>.
 */

require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));
Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
ConvertCpeDictionary::run($argv);

/**
 * Convert the CPE dictionary from XML format into a YAML format for Doctrine
 * 
 * Accepts an argument that is a path to the XML CPE dictionary. Prints the YAML formatted dictionary
 * to the standard output.
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Scripts
 * @version    $Id$
 */
class ConvertCpeDictionary 
{
    /**
     * Run the command line application
     * 
     * @param array $argv PHP's command line arguments
     */
    public static function run($argv) 
    {
        if (!isset($argv[1])) {
            fwrite(STDOUT, "Usage: convert-cpe-dictionary.php <pathToXml>\n");
            exit;
        }

        // Open and parse the XML dictionary
        $cpePath = $argv[1];
        try {
            $xml = @(new SimpleXMLElement("file:///$cpePath", null, true));
        } catch (Exception $e) {
            if (Fisma::debug()) {
                throw $e;
            } else {
                fwrite(STDERR, "Error parsing XML file '$cpePath': {$e->getMessage()}\n");
            }
        }
        
        // Write YAML file header
        fwrite(STDOUT, "Product:\n");
        fwrite(STDOUT, "    # Parsed from XML CPE Dictionary: ". basename($cpePath) . "\n");
        fwrite(STDOUT, "    # Date: ". date('Y-m-d h:i:s') . "\n");        
        fwrite(STDOUT, "    # CPE Version: ". $xml->generator->schema_version . "\n"); 
        fwrite(STDOUT, "    # Parsed by: ". $_SERVER['PHP_SELF'] . "\n"); 
        fwrite(STDOUT, "\n");

        // Iterate over CPE items and write to YAML file
        $itemCount = 0;
        foreach ($xml->children() as $name => $data) {
            if ('cpe-item' == $name) {
                fwrite(STDOUT, "    product$itemCount:\n");
                $itemCount++;
                $cpe = new Fisma_Cpe($data['name']);
                fwrite(STDOUT, "        name: $data->title\n");
                fwrite(STDOUT, "        vendor: $cpe->vendor\n");
                fwrite(STDOUT, "        version: $cpe->version\n");
                fwrite(STDOUT, "        cpeName: $cpe->cpeName\n");                
            }
        }
    }
}
