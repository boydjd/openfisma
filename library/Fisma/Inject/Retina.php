<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * A scan result injection plugin for injecting Retina XML output directly into OpenFISMA.
 * 
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 */
class Fisma_Inject_Retina extends Fisma_Inject_Abstract
{
    /**
     * Implements the required function in the Inject_Abstract interface.
     * This parses the report and commits all data to the database.
     * 
     * @param string $uploadId The id of upload Retina xml file
     */
    protected function _parse($uploadId)
    {
        $report  = new XMLReader();
        
        if (!$report->open($this->_file, NULL, LIBXML_PARSEHUGE)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat('Cannot open the XML file.');
        }

        try {
            $this->_persist($report, $uploadId);
        } catch (Exception $e) {
            throw new Fisma_Zend_Exception('An error occured while processing the XML file.', 0, $e);
        }

        $report->close();
    }

    /**
     * Save vulnerabilities and assets which are recorded in the report.
     *
     * @param XMLReader $oXml The full Retina report
     * @param int $uploadId The specific scanner file id
     */
    private function _persist(XMLReader $oXml, $uploadId)
    {
        $parsedData = array();
        $asset = array();
        $rthIds = array();
        $itemCounter = 0;

        while ($oXml->read()) {

            // The elements of the XML that we care about don't occur until we reach a depth of 2
            if ($oXml->depth >= 2 && $oXml->nodeType == XMLReader::ELEMENT) {
                if ($oXml->name == 'ip') {
                    $asset['addressIp'] = $oXml->readString();
                } elseif ($oXml->name == 'dnsName') {
                    $asset['name'] = $oXml->readString();
                } elseif ($oXml->name == 'name') {
                    $parsedData[$itemCounter]['name'] = $oXml->readString();
                } elseif ($oXml->name == 'description') {
                    $parsedData[$itemCounter]['description'] = $oXml->readString();
                } elseif ($oXml->name == 'date') {
                    $parsedData[$itemCounter]['date'] = $oXml->readString();
                } elseif ($oXml->name == 'risk') {
                    $risk = $oXml->readString();
                    switch($risk) {
                        case "Low": 
                            $risk = 'LOW';
                            break;
                        case "Medium":
                            $risk = 'MODERATE';
                            break;
                        case "Critical":
                            $risk = 'HIGH';
                            break;
                        default:
                            $risk = 'NONE';
                            break;
                    }

                    $parsedData[$itemCounter]['risk'] = $risk;
                } elseif ($oXml->name == 'cvssScore') {
                    $parsedData[$itemCounter]['cvssScore'] = $oXml->readString();
                } elseif ($oXml->name == 'fixInformation') {
                    $parsedData[$itemCounter]['fixInformation'] = $oXml->readString();
                } elseif ($oXml->name == 'rthID') {

                    //Retina (eeye) unique ID within eEye products, and doesn't track with other vulnerability type IDs
                    // The findings with the same rthIds are identical. So, parse out only one finding. 
                    // If save all identical findings to database, it causes error when upload the same file again.  
                    $rthId = $oXml->readString();
                    if (!in_array($rthId, $rthIds)) {
                        array_push($rthIds, $rthId);
                    } else {
                        $oXml->next('audit'); 
                    }
                }   
            } elseif ($oXml->nodeType == XMLReader::END_ELEMENT) {
                if ($oXml->name == 'audit') {
                    $itemCounter = count($rthIds);
                }
            }
        }

        foreach ($parsedData as $finding) {
            if (($finding['risk'] != 'NONE')) {

                // Prepare finding
                $findingInstance = array();
                $findingInstance['uploadId'] = (int) $uploadId;
                $discoveredDate = new Zend_Date(strtotime($finding['date']));
                $findingInstance['discoveredDate'] = (!empty($discoveredDate)) ? 
                                                     $discoveredDate->toString(Fisma_Date::FORMAT_DATE) : NULL;
                $findingInstance['sourceId'] = (int) $this->_findingSourceId;
                $findingInstance['responsibleOrganizationId'] = (int) $this->_orgSystemId;
                $findingInstance['description'] = Fisma_String::textToHtml($finding['name']);
                $findingInstance['threat'] = (!empty($finding['description'])) ? 
                                             Fisma_String::textToHtml($finding['description']) : NULL;
                $findingInstance['recommendation'] = (!empty($finding['fixInformation'])) ? 
                                                     Fisma_String::textToHtml($finding['fixInformation']) : NULL;
                $findingInstance['threatLevel'] = (!empty($finding['risk'])) ? $finding['risk'] : NULL;

                if ($finding['cvssScore'] != 'N/A') {
                    $score = array();
                    $vector = array();

                    // Parse out cvss score which is range from 0-10
                    if (preg_match('/(10|[0-9])(\.[0-9]+)?/', $finding['cvssScore'], $score)) {
                        $findingInstance['cvssBaseScore'] = $score[0];
                    } else {
                        $findingInstance['cvssBaseScore'] = NULL;
                    }
          
                    // Parse out cvss vector which is the string in one []
                    if (preg_match('/\[(.*?)\]/', $finding['cvssScore'], $vector)) {
                        $findingInstance['cvssVector'] = $vector[1];
                    } else {
                        $findingInstance['cvssVector'] = NULL;
                    }
                } else {
                    $findingInstance['cvssBaseScore'] = NULL;
                    $findingInstance['cvssVector'] = NULL;
                }

                $asset['name'] = (!empty($asset['name'])) ? $asset['name'] : $asset['addressIp'];            
                $asset['source'] = 'scan'; 

                // Save finding and asset
                $this->_save($findingInstance, $asset);
            }
        }

        // Commit all data
        $this->_commit();
    }
}
