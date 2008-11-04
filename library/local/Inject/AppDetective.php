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
 * @author    Mark Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */
 
/**
 * A scan result injection plugin for injecting AppDetective XML output directly into OpenFISMA.
 *
 * This plug-in makes heavy use of the SimpleXML xpath() function, which makes code easier to maintain, but could also
 * be a performance bottleneck for large reports. Currently there has not been any load-testing for this plugin.
 *
 * @package   Inject
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License\
 *
 * @todo Add audit logging
 */
class Inject_AppDetective extends Inject_Abstract
{
    private $_asset;
    private $_product;
    private $_findings;
    
    /**
     * parse() - Implements the required function in the Inject_Abstract interface. This parses the report and commits
     * all data to the database.
     */
    public function parse()
    {
        // Parse the XML file
        $report = simplexml_load_file($this->_file);
        if ($report === false) {
            throw new Exception_InvalidFileFormat('Invalid XML Format');
        }
        
        // Apply mapping rules
        $this->_asset = $this->_mapAsset($report);
        $this->_product = $this->_mapProduct($report);
        $this->_findings = $this->_mapFindings($report);

        // Commit all data
        $numberFindingsCreated = $this->_commit();
        
        return $numberFindingsCreated;
    }
    
    /**
     * _mapAsset() - Performs mapping rules for the asset object. If the asset already exists, then the existing asset
     * id will be used to populate the finding. If no asset is found, then a new asset will be created using the
     * information provided in the report.
     *
     * @param SimpleXMLElement $report The full AppDetective report
     * @return array Asset information
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
            throw new Exception_InvalidFileFormat('Expected 1 appName field, but found ' . count($reportAppName));
        }
        $asset['name'] = $reportAppName;
        
        // Parse out IP Address
        $ipAddress = array();
        if (!preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $reportAppName, $ipAddress)) {
            throw new Exception_InvalidFileFormat(
                "Unable to parse the IP address from the appName field: \"$reportAppName\""
            );
        }
        $asset['address_ip'] = $ipAddress[0]; // the regex only has one match by its definition
        
        // Parse out port number
        $port = array();
        if (!preg_match('/\bport (\d{1,5})\b/i', $reportAppName, $port)) {
            throw new Exception_InvalidFileFormat(
                "Unable to parse the port number from the appName field: \"$reportAppName\""
            );
        }
        $asset['address_port'] = $port[1]; // match the parenthesized part of the regex

        // Remaining mappings
        $asset['network_id'] = $this->_networkId;
        $asset['system_id'] = $this->_systemId;
        $asset['create_ts'] = new Zend_Date();
        $asset['source'] = 'SCAN';
        
        // Verify whether asset exists or not
        $asset['id'] = Asset::getAssetId($asset['network_id'],
                                         $asset['address_ip'],
                                         $asset['address_port']);
        
        return $asset;
    }

    /**
     * _mapProduct() - Performs mapping rules for the product object. If the asset does not already have a product
     * defined, then create a new product. If the asset does have a product but the CPE is not defined, then
     * update the CPE but do not change any other fields.
     *
     * @param SimpleXMLElement $report The full AppDetective report
     * @return array Product information
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
            throw new Exception_InvalidFileFormat('Expected 1 cpe-item field, but found ' . count($reportCpeItem));
        }
        $product['name'] = $reportCpeItem->title;
        $product['cpe_name'] = $reportCpeItem->attributes()->name;

        // Create a CPE object and use that to map the remaining fields
        $cpe = new Cpe($product['cpe_name']);
        $product['vendor'] = $cpe->vendor;
        $product['version'] = $cpe->version;

        return $product;
    }
    
    /**
     * _mapFindings() - Perform mapping rules for all of the findings contained in this report. Only findings with
     * risk level HIGH, MEDIUM, or LOW are considered.
     *
     * @param SimpleXMLElement $report The full AppDetective report
     * @return array An array of arrays contain one row for each new finding
     */
    private function _mapFindings($report)
    {
        $findings = array();
        
        // Parse the discovered date/time out of the testDate field
        $testDateString = $report->xpath('/root/root_header/testDate');
        if (count($testDateString) == 1) {
            $testDateString = $testDateString[0];
        } else {
            throw new Exception_InvalidFileFormat('Expected 1 testDate field, but found ' . count($testDateString));
        }
        $testDate = array();
        if (!preg_match('/\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{1,2}:\d{1,2} [AP]M/', $testDateString, $testDate)) {
            throw new Exception_InvalidFileFormat(
                "Unable to parse the date from the testDate field: \"$testDateString\""
            );
        }
        // Notice that the format in the report is the same format that Zend_Date expects
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
                $finding['status'] = 'PEND';
                $finding['discover_ts'] = $discoveredDate;
                $finding['create_ts'] = $creationDate;
                $finding['source_id'] = $this->_findingSourceId;
                $finding['system_id'] = $this->_systemId;
                $finding['action_suggested'] = $reportFinding->fix;
                $finding['threat_level'] = $reportFinding->overview;

                // The mapping for finding_data is a little more complicated
                $findingData = "<p>{$reportFinding->description}</p>";
                if (isset($reportFinding->details)) {
                    $findingData .= '<ul>';
                    foreach ($reportFinding->details as $vulnerability) {
                        $findingData .= "<li>{$vulnerability->vulnDetail}";
                    }
                    $findingData .= '</ul>';
                }
                $finding['finding_data'] = $findingData;
                
                // Add this finding to the total findings array
                $findings[] = $finding;
            }
        }
        
        return $findings;
    }
    
    /**
     * _commit() - Commits all of the data which has been mapped from the report.
     *
     * @todo This function needs to wrap a transaction around its queries
     */
    private function _commit()
    {
        // If the asset id is null, then create a new asset with the specified asset information. Save the asset Id
        // in order to persist the findings.
        $assetId = $this->_asset['id'];
        if (!isset($assetId)) {
            $assetTable = new Asset();
            $assetId = $assetTable->insert($this->_asset);
        }
        $assetTable = new Asset();
        $asset = $assetTable->find($assetId);

        // If the asset does not have a product associated with it, then create a new product and associate it with the
        // asset. Otherwise, update the product's CPE if it has not been defined yet.
        if (!isset($asset->prodId)) {
            $productTable = new Product();
            $productId = $productTable->insert($this->_product);
            $assetTable->update(array('prod_id' => $productId), "id = $assetId");
        }

        // Commit the findings
        foreach ($this->_findings as $finding) {
            // First set the asset ID
            $finding['asset_id'] = $assetId;
            
            // Now persist the finding
            $findingTable = new Finding();
            $id = $findingTable->insert($finding);
            $findingTable->checkForDuplicate($id);
        }
        
        return count($this->_findings);
    }
}
