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
 * Convert the CPE dictionary from XML format into a YAML format for Doctrine
 *
 * @see        http://nvd.nist.gov/cpe.cfm
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_ConvertCpeDictionary extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     * 
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'dictionary|d=s' => 'Path to XML CPE Dictionary',
            'output|o=s' => 'Name of file to write the YAML output to'
        );
    }    

    /**
     * Run the command line application
     * 
     * @param array $argv PHP's command line arguments
     * @throws Exception if fail to initialize SimpleXMLElement
     */
    protected function _run()
    {
        // Get options
        $dictionary = $this->getOption('dictionary');
        $output = $this->getOption('output');

        // Check required options
        if (empty($dictionary) || empty($output)) {
            fwrite(STDERR, "Dictionary and Output are both required fields\n");
            
            return;
        }
        
        // Try opening XML file
        try {
            $xml = new SimpleXMLElement("file:///$dictionary", null, true);
        } catch (Exception $e) {
            if (Fisma::debug()) {
                throw $e;
            } else {
                fwrite(STDERR, "Error parsing XML file '$dictionary': {$e->getMessage()}\n");
            }
        }
        
        // Try opening output file
        $outputHandle = fopen($output, 'w');
        
        if ($outputHandle === false) {
            fwrite(STDERR, "Unable to open output file ($output) for writing\n");
            
            return;
        }
        
        // Write YAML file header
        fwrite($outputHandle, "Product:\n");
        fwrite($outputHandle, "    # Parsed from XML CPE Dictionary: ". basename($dictionary) . "\n");
        fwrite($outputHandle, "    # Date: ". date('Y-m-d h:i:s') . "\n");        
        fwrite($outputHandle, "    # CPE Version: ". $xml->generator->schema_version . "\n"); 
        fwrite($outputHandle, "    # Parsed by: ". $_SERVER['PHP_SELF'] . "\n"); 
        fwrite($outputHandle, "\n");

        // Iterate over CPE items and write to YAML file
        $itemCount = 0;
        foreach ($xml->children() as $name => $data) {
            if ('cpe-item' == $name) {
                fwrite($outputHandle, "    product$itemCount:\n");
                $itemCount++;
                $cpe = new Fisma_Cpe($data['name']);
                fwrite($outputHandle, "        name: $data->title\n");
                fwrite($outputHandle, "        vendor: $cpe->vendor\n");
                fwrite($outputHandle, "        version: $cpe->version\n");
                fwrite($outputHandle, "        cpeName: $cpe->cpeName\n");                
            }
        }
        
        fclose($outputHandle);
    }
}
