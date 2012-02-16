<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * A scan result injection plugin for injecting Greenbone Security XML output directly into OpenFISMA.
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 */
class Fisma_Inject_Greenbone extends Fisma_Inject_Abstract
{
    /**
     * Implements the required function in the Inject_Abstract interface.
     * This parses the report and commits all data to the database.
     * 
     * @param string $uploadId The id of upload QualysGuard xml file
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
     * Save assets and findings which are recorded in the report.
     *
     * @param XMLReader $oXml The full Greenbone Security report
     * @param int $uploadId The specific scanner file id
     */
    private function _persist(XMLReader $oXml, $uploadId)
    {
        $parsedData = array();

        $hostCounter = 0;

        while ($oXml->read()) {
            // The elements of the XML that we care about don't occur until we reach a depth of 1
            if ($oXml->depth >= 1 && $oXml->nodeType == XMLReader::ELEMENT) {
                if ($oXml->name == 'scan_start') {
                    $discoveredDate = $oXml->readString();
                }

                if ($oXml->name == 'result') {
                    $parsedData[$hostCounter] = array();
                } elseif ($oXml->name == 'host') {
                    $parsedData[$hostCounter]['ip'] = $oXml->readString();
                } elseif ($oXml->name == 'port') {
                    $port = array();
                    if (preg_match('/(\d{1,5})/', $oXml->readString(), $port)) {
                        $parsedData[$hostCounter]['port'] = $port[1];
                    } else {
                        $parsedData[$hostCounter]['port'] = null;
                    }
                } elseif ($oXml->name == 'name') {
                    $parsedData[$hostCounter]['threat'] = $oXml->readString();
                } elseif ($oXml->name == 'cvss_base') {
                    $parsedData[$hostCounter]['cvssBaseScore'] = $oXml->readString();
                } elseif ($oXml->name == 'risk_factor') {
                    $riskFactor = $oXml->readString();

                    switch($riskFactor) {
                        case "Low": 
                            $severity = 'LOW';
                            break;
                        case "Medium":
                            $severity = 'MODERATE';
                            break;
                        case "High":
                            $severity = 'HIGH';
                            break;
                        default:
                            $severity = 'NONE';
                            break;
                    }

                    $parsedData[$hostCounter]['severity'] = $severity;
                } elseif ($oXml->name == 'cve') {
                    $parsedData[$hostCounter]['cve'] = $oXml->readString();
                } elseif ($oXml->name == 'bid') {
                    $parsedData[$hostCounter]['bid'] = $oXml->readString();
                } elseif ($oXml->name == 'description') {
                    $parsedData[$hostCounter]['description'] = $oXml->readString();
                }
            } elseif ($oXml->nodeType == XMLReader::END_ELEMENT) {
                if ($oXml->name == 'result') {
                    $hostCounter++;
                }
            }
        }

        foreach ($parsedData as $host) {
            if (!empty($host['severity']) && 'NONE' != $host['severity']) {

                // Prepare asset
                $asset = array();
                $asset['name'] = (!empty($host['port'])) ? $host['ip'] . ':' . $host['port'] : $host['ip'];
                $asset['networkId'] = (int) $this->_networkId;
                $asset['addressIp'] = $host['ip'];
                $asset['addressPort'] = (!empty($host['port'])) ? (int) $host['port'] : NULL;
                $asset['source'] = 'scan';

                // Prepare finding
                $findingInstance = array();
                $findingInstance['uploadId'] = (int) $uploadId;
                $discoveredDate = new Zend_Date(
                    strtotime($discoveredDate),
                    Zend_Date::TIMESTAMP
                );
                $findingInstance['discoveredDate'] = (!empty($discoveredDate)) ? 
                    $discoveredDate->toString(Fisma_Date::FORMAT_DATE) : NULL;

                $findingInstance['sourceId'] = (int) $this->_findingSourceId;
                $findingInstance['responsibleOrganizationId'] = (int) $this->_orgSystemId;
                $findingInstance['description'] = (!empty($host['description'])) ? 
                    Fisma_String::textToHtml($host['description']) : NULL;

                $findingInstance['threat'] = (!empty($host['threat'])) ? 
                    Fisma_String::textToHtml($host['threat']) : NULL;

                $findingInstance['threatLevel'] = (!empty($host['severity'])) ? $host['severity'] 
                    : NULL;

                $findingInstance['cvssBaseScore'] = (!empty($host['cvssBaseScore'])) ? 
                            $host['cvssBaseScore'] : NULL;

                if (!empty($host['cve']) && 'NOCVE' != $host['cve']) {
                    $cves = explode(',', $host['cve']);
                    foreach ($cves as $cve) {
                        $findingInstance['cve'][] = trim($cve);
                    }
                }

                if (!empty($host['bid']) && 'NOBID' != $host['bid']) {
                    $bugtraqs = explode(',', $host['bid']);
                    foreach ($bugtraqs as $bugtraq) {
                        $findingInstance['bugtraq'][] = trim($bugtraq);
                    }
                }

                // Save finding and asset
                $this->_save($findingInstance, $asset);
            }
        }

        // Commit all data
        $this->_commit();
    }
}
