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
 * A scan result injection plugin for injecting AppDetective XML output directly into OpenFISMA.
 *
 * This plug-in makes heavy use of the SimpleXML xpath() function, which makes code easier to maintain, but could also
 * be a performance bottleneck for large reports. Currently there has not been any load-testing for this plugin.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 * 
 * @todo       Add audit logging
 */
class Fisma_Inject_AppDetective extends Fisma_Inject_Abstract
{
    /**
     * Store the mapped asset column data
     * 
     * @var array
     */
    private $_asset;
    
    /**
     * Store the mapped product column data
     * 
     * @var array
     */
    private $_product;
    
    /**
     * Store column data of those mapped findings
     * 
     * @var array
     */
    private $_findings;
    
    /**
     * Some appDetective reports can contain over 100k of vulnDetail data per finding. This is too much data to save
     * in a mysql column, so we limit the number of vulnDetails captured to a manageable number. Anything over this
     * amount will be truncated and a warning will be issued.
     */
    const MAX_VULN_DETAILS_PER_FINDING = 25;
    
    /**
     * This is marketing language which is filtered out of the recommendation field.
     */
    const REMOVE_PHRASE = "/As part of a complete .* level of database security./";
    
    /**
     * Implements the required function in the Inject_Abstract interface. This parses the report and commits
     * all data to the database.
     * 
     * @param string $uploadId The specified id of upload file to be parsed
     * @return void
     * @throws Fisma_Zend_Exception_InvalidFileFormat if the file is not an App Detective report
     */
    protected function _parse($uploadId)
    {
        // Parse the XML file
        $report = simplexml_load_file($this->_file);
        
        // Bug 2596247 - "App Detective plug-in does not work with recent vrsn. of AD"        
        // Make sure that this is an AppDetective report, not a Crystal report. (App Detective can generate both 
        // kinds of report.)
        $checkCrystalReport = $report->getNamespaces(true);
        if (in_array('urn:crystal-reports:schemas', $checkCrystalReport)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat('This is a Crystal Report, not an App Detective report.');
        }
        // Apply mapping rules
        $this->_asset    = $this->_mapAsset($report);
        $this->_product  = $this->_mapProduct($report);
        $this->_findings = $this->_mapFindings($report, $uploadId);
        // Free resources used by XML object
        unset($report);

        // Persist all data
        $this->_persist();
    }
    
    /**
     * Performs mapping rules for the asset object. If the asset already exists, then the existing asset
     * id will be used to populate the finding. If no asset is found, then a new asset will be created using the
     * information provided in the report.
     *
     * @param SimpleXMLElement $report The full AppDetective report
     * @return array The parsed and mapped asset
     * @throws Fisma_Zend_Exception_InvalidFileFormat if found multiple appName fields or unable to parse IP or port
     */
    private function _mapAsset($report)
    {
        // Asset information is parsed out of the appName field.
        // There should only be 1 appName field in the entire report.
        $asset = array();
        $reportAppName = $report->xpath('/root/root_header/appName');
        if (count($reportAppName) == 1) {
            $reportAppName = $reportAppName[0];
        } else {
            throw new Fisma_Zend_Exception_InvalidFileFormat(
                'Expected 1 appName field, but found ' . count($reportAppName)
            );
        }
        $appName = array();
        if (preg_match('/\((.*?)\)/', $reportAppName, $appName)) {
            // If a parenthesized expression is found, then use the parenthesized expression.
            $asset['name'] = $appName[1];
        } else {
            // If a parenthesized expression is NOT found, then use the entire appName field
            $asset['name'] = $reportAppName;
        }
        
        // Parse out IP Address
        $ipAddress = array();
        if (!preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $reportAppName, $ipAddress)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat(
                "Unable to parse the IP address from the appName field: \"$reportAppName\""
            );
        }
        $asset['addressIp'] = $ipAddress[0]; // the regex only has one match by its definition
        
        // Parse out port number
        $port = array();
        if (!preg_match('/\bport (\d{1,5})\b/i', $reportAppName, $port)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat(
                "Unable to parse the port number from the appName field: \"$reportAppName\""
            );
        }
        $asset['addressPort'] = $port[1]; // match the parenthesized part of the regex

        $asset['source'] = 'scan';

        return $asset;
    }

    /**
     * Performs mapping rules for the product object. If the asset does not already have a product
     * defined, then create a new product. If the asset does have a product but the CPE is not defined, then
     * update the CPE but do not change any other fields.
     *
     * @param SimpleXMLElement $report The full AppDetective report
     * @return array  The parsed and mapped product
     * @throws Fisma_Zend_Exception_InvalidFileFormat if found multiple cpe-item fileds
     */
    private function _mapProduct($report)
    {
        // Product information is parsed out of the cpe-item field
        // There should only be 1 cpe-item field in the entire report.
        $product = array();
        $reportCpeItem = $report->xpath("/root/root_header/*[name()='cpe-item']");
        if (count($reportCpeItem) == 1) {
            $reportCpeItem = $reportCpeItem[0];
        } else {
            throw new Fisma_Zend_Exception_InvalidFileFormat('Expected 1 cpe-item field, but found ' 
                                                        . count($reportCpeItem));
        }

        // Create a CPE object and use that to map the fields
        try {
            // Bug 2596247 - "App Detective plug-in does not work with recent vrsn. of AD"
            // App Detective does not follow the CPE specification when it cannot identify the platform. It creates a
            // CPE called "cpe:no-match", which is not valid and will cause the Cpe class to throw an exception.
            $cpe = new Fisma_Cpe($reportCpeItem->attributes()->name);
        } catch (Fisma_Zend_Exception_InvalidFileFormat $e) {
            // If the CPE is not valid, then return NULL for the product object
            return null;
        }

        $product['cpeName'] = $cpe->cpeName;
        
        return $product;
    }
    
    /**
     * Perform mapping rules for all of the findings contained in this report. Only findings with
     * risk level HIGH, MEDIUM, or LOW are considered.
     *
     * @param SimpleXMLElement $report The full AppDetective report
     * @return array An array of arrays contain one row for each new finding
     * @throws Fisma_Zend_Exception_InvalidFileFormat if found multiple testDate fields 
     * or unable to parse date from the testDate field
     */
    private function _mapFindings($report, $uploadId)
    {
        $findings = array();
        
        // Parse the discovered date/time out of the testDate field
        $testDateString = $report->xpath('/root/root_header/testDate');
        if (count($testDateString) == 1) {
            $testDateString = $testDateString[0];
        } else {
            throw new Fisma_Zend_Exception_InvalidFileFormat('Expected 1 testDate field, but found ' 
                                                        . count($testDateString));
        }
        $testDate = array();
        if (!preg_match('/\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{1,2}:\d{1,2} [AP]M/', $testDateString, $testDate)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat(
                "Unable to parse the date from the testDate field: \"$testDateString\""
            );
        }
        $discoveredDate = new Zend_Date($testDate[0]);
        
        // The creation timestamp for each finding is the current system time
        $creationDate = new Zend_Date();
        
        // Get HIGH, MEDIUM, and LOW risk findings
        $reportData = $report->xpath('/root/root_detail_risklevel_1/data');                           // HIGH
        $reportData = array_merge($reportData, $report->xpath('/root/root_detail_risklevel_2/data')); // MEDIUM
        $reportData = array_merge($reportData, $report->xpath('/root/root_detail_risklevel_3/data')); // LOW
        
        // Iterate over all discovered findings
        foreach ($reportData as $reportFinding) {
            // Some "findings" are empty. We test for emptiness by looking at the risk element -- something which all
            // the findings should have. If a finding is missing a risk element, then we silently skip it.
            if (isset($reportFinding->risk)) {
                $finding = array();
                
                // The finding's asset ID is set during the commit, since the asset may not exist yet.
                $finding['uploadId'] = $uploadId;
                $finding['discoveredDate'] = $discoveredDate->toString(Fisma_Date::FORMAT_DATETIME);
                $finding['sourceId'] = $this->_findingSourceId;
                $finding['responsibleOrganizationId'] = $this->_orgSystemId;
                $finding['recommendation'] = preg_replace(self::REMOVE_PHRASE, '', $reportFinding->fix);
                $finding['recommendation'] = Fisma_String::textToHtml($finding['recommendation']);
                $finding['threatLevel'] = strtoupper($reportFinding->risk);
                //todo english translate "medium" into "MODERATE" to adapt OpenFISMA
                if ('MEDIUM' == $finding['threatLevel']) {
                    $finding['threatLevel'] = 'MODERATE';
                }
                $finding['threat'] = Fisma_String::textToHtml($reportFinding->overview);

                // The mapping for finding_data is a little more complicated
                // WARNING: Because duplicate matching is perfomed on this field, modifications to the markup used in
                // this mapping rule must be approved by a project manager.
                $findingData = $reportFinding->description;
                if (isset($reportFinding->details)) {
                    $findingData .= '<ul>';
                    $vulnDetails = 0;
                    foreach ($reportFinding->details as $vulnerability) {
                        $findingData .= "<li>{$vulnerability->vulnDetail}";
                        $vulnDetails++;
                        if ($vulnDetails > self::MAX_VULN_DETAILS_PER_FINDING) {
                            $vulnDetailsOmitted = count($reportFinding->details) - self::MAX_VULN_DETAILS_PER_FINDING;
                            $findingData .= "<li><i>WARNING: $vulnDetailsOmitted additional vulnerability details were"
                                          . ' truncated when this finding was injected due to storage constraints.</i>';
                            break;
                        }
                    }
                    $findingData .= '</ul>';
                }
                $finding['description'] = Fisma_String::textToHtml($findingData);
                
                // Add this finding to the total findings array
                $findings[] = $finding;
            }
        }

        return $findings;
    }
    
    /**
     * Commits all of the data which has been mapped from the report.
     * 
     * @return void
     * @todo This function needs to wrap a transaction around its queries
     */
    private function _persist()
    {
        foreach ($this->_findings as $finding) {
            $this->_save($finding, $this->_asset, $this->_product);
        }

        $this->_commit();
    }
    
}
