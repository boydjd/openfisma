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
 * A scan result injection plugin for injecting QualysGuard XML output directly into OpenFISMA.
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 */
class Fisma_Inject_QualysGuard extends Fisma_Inject_Abstract
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
     * @param XMLReader $oXml The full QualysGuard report
     * @param int $uploadId The specific scanner file id
     */
    private function _persist(XMLReader $oXml, $uploadId)
    {
        $parsedData = array();

        $hostCount = 0;
        $catCount  = 0;
        $itemCount = 0;

        while ($oXml->read()) {
            // The elements of the XML that we care about don't occur until we reach a depth of 1
            if ($oXml->depth >= 1 && $oXml->nodeType == XMLReader::ELEMENT) {
                if ($oXml->name == 'KEY' && $oXml->getAttribute('value') == 'DATE') {
                    $parsedData['discoveredDate'] = $oXml->readString();
                }

                if ($oXml->name == 'IP') {
                    $parsedData[$hostCount] = array();
                    $parsedData[$hostCount][$catCount] = array();
                    $parsedData[$hostCount][$catCount]['findings'] = array();

                    if ($oXml->getAttribute('name')) {
                        $parsedData[$hostCount]['name'] = $oXml->getAttribute('name');
                    }

                    if ($oXml->getAttribute('value')) {
                        $parsedData[$hostCount]['ip'] = $oXml->getAttribute('value');
                    }
                } elseif ($oXml->name == 'CAT') {
                    $parsedData[$hostCount][$catCount]['port'] = $oXml->getAttribute('port');
                } elseif ($oXml->name == 'VULN') {
                    $parsedData[$hostCount][$catCount]['findings'][$itemCount] = array();
                    $severity = $oXml->getAttribute('severity');

                    switch($severity) {
                        case "3": 
                            $severity = 'LOW';
                            break;
                        case "4":
                            $severity = 'MODERATE';
                            break;
                        case "5":
                            $severity = 'HIGH';
                            break;
                        default:
                            $severity = 'NONE';
                            break;
                    }

                    $parsedData[$hostCount][$catCount]['findings'][$itemCount]['severity'] = $severity;
                } elseif ($oXml->name == 'CVE_ID') {
                    $parent = $oXml->expand();
                    $cve = $parent->getElementsByTagName('ID')->item(0)->nodeValue;
                    $parsedData[$hostCount][$catCount]['findings'][$itemCount]['cve'][] = $cve;
                } elseif ($oXml->name == 'BUGTRAQ_ID') {
                    $parent = $oXml->expand();
                    $bid = $parent->getElementsByTagName('ID')->item(0)->nodeValue;
                    $parsedData[$hostCount][$catCount]['findings'][$itemCount]['bid'][] = $bid;
                } elseif ($oXml->name == 'DIAGNOSIS') {
                    $parsedData[$hostCount][$catCount]['findings'][$itemCount]['description'] = $oXml->readString();
                } elseif ($oXml->name == 'CONSEQUENCE') {
                    $parsedData[$hostCount][$catCount]['findings'][$itemCount]['consequence'] = $oXml->readString();
                } elseif ($oXml->name == 'SOLUTION') {
                    $parsedData[$hostCount][$catCount]['findings'][$itemCount]['solution'] = $oXml->readString();
                }
            } elseif ($oXml->nodeType == XMLReader::END_ELEMENT) {
                if ($oXml->name == 'IP') {
                    $hostCount++;
                    $catCount = 0;
                    $itemCount = 0;
                } elseif ($oXml->name == 'CAT') {
                    $catCount++;
                } elseif ($oXml->name == 'VULN') {
                    $itemCount++;
                }
            }
        }

        foreach ($parsedData as $host) {
            if (!is_array($host)) {
                continue;
            }

            foreach ($host as $cats) {
                if (!is_array($cats)) {
                    continue;
                }

                foreach ($cats as $findings) {
                    if (!is_array($findings)) {
                        continue;
                    }

                    foreach ($findings as $finding) {
                        if (!empty($finding['severity']) && 'NONE' != $finding['severity']) {

                            if (!isset($host['ip'])) {
                                $host['ip']  = $host['name'];
                            }

                            // Prepare asset
                            $asset = array();
                            $asset['name'] = (!empty($cats['port'])) ? $host['ip'] . ':' . $cats['port'] : $host['ip'];
                            $asset['networkId'] = (int) $this->_networkId;
                            $asset['addressIp'] = $host['ip'];
                            $asset['addressPort'] = (!empty($cats['port'])) ? (int) $cats['port'] : NULL;
                            $asset['source'] = 'scan';

                            // Prepare finding
                            $findingInstance = array();
                            $findingInstance['uploadId'] = (int) $uploadId;
                            $discoveredDate = new Zend_Date(
                                strtotime($parsedData['discoveredDate']),
                                Zend_Date::TIMESTAMP
                            );
                            $findingInstance['discoveredDate'] = (!empty($discoveredDate)) ? 
                                $discoveredDate->toString(Fisma_Date::FORMAT_DATE) : NULL;

                            $findingInstance['sourceId'] = (int) $this->_findingSourceId;
                            $findingInstance['responsibleOrganizationId'] = (int) $this->_orgSystemId;
                            $findingInstance['description'] = (!empty($finding['description'])) ? 
                                $finding['description'] : NULL;

                            $findingInstance['threat'] = (!empty($finding['consequence'])) ? 
                                $finding['consequence'] : NULL;

                            $findingInstance['recommendation'] = (!empty($finding['solution'])) ? 
                                $finding['solution'] : NULL;

                            $findingInstance['threatLevel'] = (!empty($finding['severity'])) ? $finding['severity'] 
                                : NULL;

                            if (!empty($finding['cve'])) {
                                foreach ($finding['cve'] as $cve) {
                                    $findingInstance['cve'][] = $cve;
                                }
                            }

                            if (!empty($finding['bid'])) {
                                foreach ($finding['bid'] as $bugtraq) {
                                    $findingInstance['bugtraq'][] = $bugtraq;
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
