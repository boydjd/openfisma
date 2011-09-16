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
 * A scan result injection plugin for injecting Saint XML output directly into OpenFISMA.
 * 
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 */
class Fisma_Inject_Saint extends Fisma_Inject_Abstract
{
    /**
     * Implements the required function in the Inject_Abstract interface.
     * This parses the report and commits all data to the database.
     * 
     * @param string $uploadId The id of upload Saint xml file
     */
    protected function _parse($uploadId)
    {
        $report  = new XMLReader();
        
        if (!$report->open($this->_file, NULL, LIBXML_PARSEHUGE)) {
            $report->close();
            throw new Fisma_Zend_Exception_InvalidFileFormat('Cannot open the XML file.');
        }

        try {
            $this->_persist($report, $uploadId);
        } catch (Exception $e) {
            $report->close();
            throw new Fisma_Zend_Exception('An error occured while processing the XML file.', 0, $e);
        }

        $report->close();
    }

    /**
     * Save vulnerabilities and assets which are recorded in the report.
     *
     * @param XMLReader $oXml The full Saint report
     * @param int $uploadId The specific scanner file id
     */
    private function _persist(XMLReader $oXml, $uploadId)
    {
        $parsedData = array();
        $itemCounter = 0;
        $hostCounter = 0;

        while ($oXml->read()) {

            // The elements of the XML that we care about don't occur until we reach a depth of 2
            if ($oXml->depth >= 2 && $oXml->nodeType == XMLReader::ELEMENT) {
                if ($oXml->name == 'host_info') {
                    $parsedData[$hostCounter] = array();
                    $parsedData[$hostCounter]['findings'] = array();
                } elseif ($oXml->name == 'scan_time') {
                    $parsedData[$hostCounter]['scan_time'] = $oXml->readString();
                } elseif ($oXml->name == 'hostname') {
                    $parsedData[$hostCounter]['name'] = $oXml->readString();
                } elseif ($oXml->name == 'ipaddr') {
                    $parsedData[$hostCounter]['addressIp'] = $oXml->readString();
                } elseif ($oXml->name == 'description') {
                    $parsedData[$hostCounter]['findings'][$itemCounter] = array();
                    $parsedData[$hostCounter]['findings'][$itemCounter]['description'] = $oXml->readString();
                } elseif ($oXml->name == 'vuln_details') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['vuln_details'] = $oXml->readString();
                } elseif ($oXml->name == 'resolution') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['resolution'] = $oXml->readString();
                } elseif ($oXml->name == 'impact') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['impact'] = $oXml->readString();
                } elseif ($oXml->name == 'severity') {
                    $severity = $oXml->readString();
                    switch($severity) {
                        case "Potential Problem": 
                            $severity = 'LOW';
                            break;
                        case "Area of Concern":
                            $severity = 'MODERATE';
                            break;
                        case "Critical Problem":
                            $severity = 'HIGH';
                            break;
                        default:
                            $severity = 'NONE';
                            break;
                    }

                    $parsedData[$hostCounter]['findings'][$itemCounter]['severity'] = $severity;
                } elseif ($oXml->name == 'cve') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['cve'][] = $oXml->readString();
                }   
              
            } elseif ($oXml->nodeType == XMLReader::END_ELEMENT) {
                if ($oXml->name == 'host_info') {
                    $hostCounter++;
                    $itemCounter = 0;
                } elseif ($oXml->name == 'vulnerability') {
                    $itemCounter++;
                }
            }
        }

        foreach ($parsedData as $host) {
            foreach ($host as $findings) {
                if (is_array($findings)) {
                    foreach ($findings as $finding) {
                        if ($finding['severity'] != 'NONE') {

                            // Prepare asset
                            $asset = array();
                            $asset['name'] = $host['name'];
                            $asset['addressIp'] = $host['addressIp'];
                            $asset['source'] = 'scan';

                            // Prepare finding
                            $findingInstance = array();
                            $findingInstance['uploadId'] = (int) $uploadId;
                            $discoveredDate = new Zend_Date(strtotime($host['scan_time']), Zend_Date::TIMESTAMP);
                            $findingInstance['discoveredDate'] = (!empty($discoveredDate)) ?
                                $discoveredDate->toString(Fisma_Date::FORMAT_DATE) : NULL;

                            $findingInstance['sourceId'] = (int) $this->_findingSourceId;
                            $findingInstance['responsibleOrganizationId'] = (int) $this->_orgSystemId;

                            $findingInstance['description'] = Fisma_String::textToHtml($finding['description']) 
                                                              . Fisma_String::textToHtml($finding['vuln_details']);
                            $findingInstance['threat'] = (!empty($finding['impact'])) ? 
                                                         Fisma_String::textToHtml($finding['impact']) : NULL;
                            $findingInstance['recommendation'] = (!empty($finding['resolution'])) ? 
                                                                Fisma_String::textToHtml($finding['resolution']) : NULL;
                            $findingInstance['threatLevel'] = (!empty($finding['severity'])) ? 
                                                              $finding['severity'] : NULL;

                            if (!empty($finding['cve'])) {
                                foreach ($finding['cve'] as $cve) {
                                    $findingInstance['cve'][] = $cve;
                                }
                            }

                            // Save finding and asset
                            $this->_save($findingInstance, $asset);
                        }
                    }
                }
            }
        }

        // Commit all data
        $this->_commit();
    }
}
