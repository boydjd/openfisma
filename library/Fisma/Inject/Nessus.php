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
 * A scan result injection plugin for injecting Nessus XML output directly into OpenFISMA.
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 * @version    $Id$
 */
class Fisma_Inject_Nessus extends Fisma_Inject_Abstract
{
    /**
     * Implements the required function in the Inject_Abstract interface.
     * This parses the report and commits all data to the database.
     * 
     * @param string $uploadId The id of upload Nessus xml file
     */
    public function parse($uploadId)
    {
        $grammar = new Fisma_Inject_Grammar('Nessus');
        $report  = new XMLReader();

        // The third parameter is the constant LIBXML_PARSEHUGE from libxml, which is not exposed to XMLReader. 
        // This is fixed in SVN of PHP as of 12/1/09, but until it hits a release version this hack will stay.
        // @TODO Change 1<<19 to LIBXML_PARSEHUGE once it is visible
        if (!$report->open($this->_file, NULL, 1<<19)) {
            throw new Fisma_Exception('Cannot open the XML file.');
        }

        $report->setRelaxNGSchemaSource($grammar);

        try {
            $this->_persist($report, $uploadId);
        } catch (Exception $e) {
            throw $e;
            throw new Fisma_Exception('An error occured while processing the XML file.');
        }

        $report->close();
    }

    /**
     * Save assets and findings which are recorded in the report.
     *
     * @param XMLReader $oXml The full Nessus report
     * @param int $uploadId The specific scanner file id
     */
    private function _persist(XMLReader $oXml, $uploadId)
    {
        $parsedData = array();

        $hostCounter = 0;
        $itemCounter = 0;

        while ($oXml->read()) {
            // The elements of the XML that we care about don't occur until we reach a depth of 2
            if ($oXml->depth >= 2 && $oXml->nodeType == XMLReader::ELEMENT) {
                if ($oXml->name == 'ReportHost') {
                    $parsedData[$hostCounter] = array();
                    $parsedData[$hostCounter]['findings'] = array();
                    $parsedData[$hostCounter]['ip'] = $oXml->getAttribute('name');
                } elseif ($oXml->name == 'tag' && $oXml->getAttribute('name') == 'HOST_END') {
                    $parsedData[$hostCounter]['startTime'] = $oXml->readString();
                } elseif ($oXml->name == 'ReportItem') {
                    $parsedData[$hostCounter]['findings'][$itemCounter] = array();
                    $severity = $oXml->getAttribute('severity');
                    $parsedData[$hostCounter]['findings'][$itemCounter]['port'] = $oXml->getAttribute('port');

                    switch($severity) {
                        case "1": 
                            $severity = 'LOW';
                            break;
                        case "2":
                            $severity = 'MODERATE';
                            break;
                        case "3":
                            $severity = 'HIGH';
                            break;
                        default:
                            $severity = 'NONE';
                            break;
                    }

                    $parsedData[$hostCounter]['findings'][$itemCounter]['severity'] = $severity;
                } elseif ($oXml->name == 'solution') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['solution'] = $oXml->readString();
                } elseif ($oXml->name == 'description') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['description'] = $oXml->readString();
                } elseif ($oXml->name == 'cve') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['cve'][] = $oXml->readString();
                } elseif ($oXml->name == 'bid') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['bid'][] = $oXml->readString();
                } elseif ($oXml->name == 'xref') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['xref'][] = $oXml->readString();
                } elseif ($oXml->name == 'synopsis') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['synopsis'] = $oXml->readString();
                } elseif ($oXml->name == 'cvss_base_score') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['cvss_base_score'] = $oXml->readString();
                } elseif ($oXml->name == 'cvss_vector') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['cvss_vector'] = $oXml->readString();
                } elseif ($oXml->name == 'plugin_output') {
                    $parsedData[$hostCounter]['findings'][$itemCounter]['plugin_output'] = $oXml->readString();
                }
            } elseif ($oXml->nodeType == XMLReader::END_ELEMENT) {
                if ($oXml->name == 'ReportHost') {
                    $hostCounter++;
                    $itemCounter = 0;
                } elseif ($oXml->name == 'ReportItem') {
                    $itemCounter++;
                }
            }
        }

        // Make sure that the XML is valid before continuing. Since XMLReader is stream based, we can't check for
        // validity until after the XML is completely parsed.
        if (!$oXml->isValid()) {
            throw new Fisma_Inject_Exception('XML is not valid.');
        }

        foreach ($parsedData as $host) {
            foreach ($host as $findings) {
                if (is_array($findings)) {
                    foreach ($findings as $finding) {
                        if (($finding['severity'] != 'NONE') && ($finding['severity'] != 'LOW')) {
                            // Prepare asset
                            $asset = array();
                            $asset['name'] = (!empty($finding['port'])) ? $host['ip'] . ':' . $finding['port'] : 
                                $host['ip'];
                            $asset['networkId'] = (int) $this->_networkId;
                            $asset['addressIp'] = $host['ip'];
                            $asset['addressPort'] = (!empty($finding['port'])) ? (int) $finding['port'] : NULL;

                            // Prepare finding
                            $finding['plugin_output'] = (!empty($finding['plugin_output'])) ? $finding['plugin_output']
                                : '';

                            $findingInstance = array();
                            $findingInstance['uploadId'] = (int) $uploadId;
                            $findingInstance['discoveredDate'] = date('Y-m-d', strtotime($host['startTime']));
                            $findingInstance['sourceId'] = (int) $this->_findingSourceId;
                            $findingInstance['responsibleOrganizationId'] = (int) $this->_orgSystemId;
                            $findingInstance['description'] = $finding['description'] . $finding['plugin_output'];
                            $findingInstance['threat'] = (!empty($finding['synopsis'])) ? $finding['synopsis'] : NULL;
                            $findingInstance['recommendation'] = (!empty($finding['solution'])) ? $finding['solution']
                                : NULL;
                            $findingInstance['threatLevel'] = (!empty($finding['severity'])) ? $finding['severity'] 
                                : NULL;
    
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
