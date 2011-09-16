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
        $report  = new XMLReader();
        
        // Bug 2596247 - "App Detective plug-in does not work with recent vrsn. of AD"        
        // Make sure that this is an AppDetective report, not a Crystal report. (App Detective can generate both 
        // kinds of report.)
        if ($report->lookupNamespace('urn:crystal-reports:schemas')) {
            throw new Fisma_Zend_Exception_InvalidFileFormat('This is a Crystal Report, not an App Detective report.');
        }

        if (!$report->open($this->_file, NULL, LIBXML_PARSEHUGE)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat('Cannot open the XML file.');
        }

        try {
            $this->_persist($report, $uploadId);
        } catch (Exception $e) {
            $report->close();
            throw new Fisma_Zend_Exception_InvalidFileFormat('An error occured while processing the XML file.', 0, $e);
        }

        $report->close();
    }
    
    /**
     * Get and save findings, assets and products info are recorded in the report.
     *
     * @param XMLReader $oXml The full AppDetective report
     * @param int $uploadId The specific scanner file id
     */
    private function _persist(XMLReader $oXml, $uploadId)
    {
        $itemCounter = 0;
        $detailCounter = 0;

        $asset = array();
        $product = array();
        $findings = array();

        while ($oXml->read()) {

            // The elements of the XML that we care about don't occur until we reach a depth of 2
            if ($oXml->depth >= 2 && $oXml->nodeType == XMLReader::ELEMENT) {
                if ($oXml->name == 'testDate') {
          
                    // Parse the discovered date/time out of the testDate field
                    $testDateString = $oXml->readString();
                    $testDate = array();
                    if (!preg_match('/\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{1,2}:\d{1,2} [AP]M/', 
                                    $testDateString, 
                                    $testDate)) {
                        throw new Fisma_Zend_Exception_InvalidFileFormat(
                            "Unable to parse the date from the testDate field: \"$testDateString\""
                        );
                    }
                   
                    $discoveredDate = new Zend_Date($testDate[0]);
                } elseif ($oXml->name == 'appName') {
                    
                    // Asset information is parsed out of the appName field.
                    // There should only be 1 appName field in the entire report.
                    $reportAppName = $oXml->readString();
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
                } elseif ($oXml->name == 'cpe-item') {

                    // Product information is parsed out of the cpe-item field
                    // There should only be 1 cpe-item field in the entire report.
                    // Create a CPE object and use that to map the fields
                    try {

                        // Bug 2596247 - "App Detective plug-in does not work with recent vrsn. of AD"
                        // App Detective does not follow the CPE specification when it cannot identify the platform. 
                        // It creates a CPE called "cpe:no-match", which is not valid and will cause the Cpe class 
                        // to throw an exception.
                        $cpe = new Fisma_Cpe($oXml->getAttribute('name'));
                        $product['cpeName'] = $cpe->cpeName;
                    } catch (Fisma_Zend_Exception_InvalidFileFormat $e) {
                        // If the CPE is not valid, then return NULL for the product object
                        $product = null;
                    }
                } elseif ($oXml->name == 'risk') {
                    $findings[$itemCounter]['risk']=$oXml->readString();
                } elseif ($oXml->name == 'description') {
                    $findings[$itemCounter]['findingData'] = $oXml->readString();
                } elseif ($oXml->name == 'overview') {
                    $findings[$itemCounter]['threat'] = $oXml->readString();
                } elseif ($oXml->name == 'fix') {
                    $findings[$itemCounter]['recommendation'] = $oXml->readString();
                } elseif ($oXml->name == 'details') {
                    $findings[$itemCounter]['findingDetail'][$detailCounter] = $oXml->readString();
                    
                } 
            } elseif ($oXml->nodeType == XMLReader::END_ELEMENT) {
                if ($oXml->name == 'data') {
                    $itemCounter++;
                    $detailCounter = 0;
                } elseif ($oXml->name =='details') {
                    $detailCounter++;
                }
            }
        }
    
        $this->_saveData($uploadId, $findings, $asset, $product); 
    }

    /**
     * Save assets and findings which are recorded in the report.
     *
     * @param int $uploadId The specific scanner file id
     * @param array findings info
     * @param array asset info
     * @param array product info
     */
    private function _saveData($uploadId, $findings, $asset, $product)
    {
        foreach ($findings as $finding) {
        
            // Some "findings" are empty or other levels such as Informational. We test for emptiness by 
            // looking at the risk element -- something which all the findings should have. If a finding is missing 
            // a risk element or its level is not HIGH or MEDIUM or LOW, then we silently skip it.
            $threatLevel = strtoupper($finding['risk']);
            if (!empty($threatLevel) && ($threatLevel == 'HIGH' || $threatLevel == 'MEDIUM' || $threatLevel == 'LOW')) {
                $findingInstance = array();

                // The finding's asset ID is set during the commit, since the asset may not exist yet.
                $findingInstance['uploadId'] = (int) $uploadId;
                $findingInstance['discoveredDate'] = (!empty($discoveredDate)) ?
                                                     $discoveredDate->toString(Fisma_Date::FORMAT_DATETIME) : NULL;
                $findingInstance['sourceId'] = $this->_findingSourceId;
                $findingInstance['responsibleOrganizationId'] = $this->_orgSystemId;
                $findingInstance['threatLevel'] = $threatLevel;

                //todo english translate "medium" into "MODERATE" to adapt OpenFISMA
                if ('MEDIUM' == $threatLevel) {
                    $findingInstance['threatLevel'] = 'MODERATE';
                }   

                $findingInstance['threat'] = (!empty($finding['threat'])) ? 
                                             Fisma_String::textToHtml($finding['threat']) : NULL;
                if (!empty($finding['recommendation'])) {
                    $findingInstance['recommendation'] = preg_replace(self::REMOVE_PHRASE, 
                                                                      '', 
                                                                      $finding['recommendation']);
                    $findingInstance['recommendation'] = Fisma_String::textToHtml($findingInstance['recommendation']);
                } else {
                    $findingInstance['recommendation'] = NULL;
                }

                // The mapping for finding_data is a little more complicated
                // WARNING: Because duplicate matching is perfomed on this field, modifications to the markup used in
                // this mapping rule must be approved by a project manager.
                $findingData = $finding['findingData'];
                if (is_array($finding['findingDetail']) && !empty($finding['findingDetail'])) {
                    $findingData .= '<ul>';
                    $vulnDetails = 0;

                    foreach ($finding['findingDetail'] as $vulnerability) {
                        $findingData .= "<li>$vulnerability";
                        $vulnDetails++;
                        if ($vulnDetails > self::MAX_VULN_DETAILS_PER_FINDING) {
                            $vulnDetailsOmitted = count($finding['findingDetail']) - self::MAX_VULN_DETAILS_PER_FINDING;
                            $findingData .= "<li><i>WARNING: $vulnDetailsOmitted additional vulnerability details were"
                                         . ' truncated when this finding was injected due to storage constraints.</i>';
                            break;
                        }
                    }        
                    $findingData .= '</ul>';
                }
                $findingInstance['description'] =  Fisma_String::textToHtml($findingData);

                // Save finding, asset and product
                $this->_save($findingInstance, $asset, $product);
            }
        }
        $this->_commit();
    } 
}
