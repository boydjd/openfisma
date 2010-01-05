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
 * @author     Ryan yang <ryanyang@users.sourceforge.net>
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
     * @return void
     * @throws Fisma_Exception_InvalidFileFormat if the file is not a Nessus report
     */
    public function parse($uploadId)
    {
        $grammar = new Fisma_Inject_Grammar('Nessus');
        $report  = new XMLReader();

        // The third parameter is the constant LIBXML_PARSEHUGE from libxml, which is not exposed to XMLReader. 
        // This is fixed in SVN of PHP as of 12/1/09, but until it hits a release version this hack will stay.
        if (!$report->open($this->_file, NULL, 1<<19)) {
            throw new Fisma_Exception('Cannot open the XML file.');
        }

        $report->setRelaxNGSchemaSource($grammar);

        try {
            $this->_persist($report, $uploadId);
        } catch (Exception $e) {
            throw new Fisma_Exception('An error occured while processing the XML file.' . $e->getMessage());
        }

        $report->close();
    }

    /**
     * Save assets and findings which are recorded in the report.
     *
     * @param XMLReader $report The full Nessus report
     * @param int $uploadId The specific scanner file id
     * @return void
     * @throws Fisma_Exception_InvalidFileFormat if not found expected fields
     */
    private function _persist(XMLReader $report, $uploadId)
    {
        $findingList = array();

        $hostCounter = 0;
        $itemCounter = 0;

        while ($report->read()) {
            // The elements of the XML that we care about don't occur until we reach a depth of 2
            if ($report->depth >= 2 && $report->nodeType == XMLReader::ELEMENT) {
                if ($report->name == 'ReportHost') {
                    $findingList[$hostCounter] = array();
                    $findingList[$hostCounter]['findings'] = array();
                    $findingList[$hostCounter]['ip'] = $report->getAttribute('name');
                } elseif ($report->name == 'tag' && $report->getAttribute('name') == 'HOST_END') {
                    $findingList[$hostCounter]['startTime'] = $report->readString();
                } elseif ($report->name == 'ReportItem') {
                    $findingList[$hostCounter]['findings'][$itemCounter] = array();
                    $severity = $report->getAttribute('severity');
                    $findingList[$hostCounter]['findings'][$itemCounter]['port'] = $report->getAttribute('port');

                    switch($severity) {
                        case '1': 
                            $severity = 'LOW';
                            break;
                        case '2':
                            $severity = 'MODERATE';
                            break;
                        case '3':
                            $severity = 'HIGH';
                            break;
                    }

                    $findingList[$hostCounter]['findings'][$itemCounter]['severity'] = $severity;
                } elseif ($report->name == 'solution') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['solution'] = $report->readString();
                } elseif ($report->name == 'description') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['description'] = $report->readString();
                } elseif ($report->name == 'cve') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['cve'][] = $report->readString();
                } elseif ($report->name == 'bid') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['bid'][] = $report->readString();
                } elseif ($report->name == 'xref') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['xref'][] = $report->readString();
                } elseif ($report->name == 'synopsis') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['synopsis'] = $report->readString();
                } elseif ($report->name == 'cvss_base_score') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['cvss_base_score'] = $report->readString();
                } elseif ($report->name == 'cvss_vector') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['cvss_vector'] = $report->readString();
                } elseif ($report->name == 'plugin_output') {
                    $findingList[$hostCounter]['findings'][$itemCounter]['plugin_output'] = $report->readString();
                }
            } elseif ($report->nodeType == XMLReader::END_ELEMENT) {
                if ($report->name == 'ReportHost') {
                    $hostCounter++;
                    $itemCounter = 0;
                } elseif ($report->name == 'ReportItem') {
                    $itemCounter++;
                }
            }
        }

        foreach ($findingList as $host) {
            foreach ($host as $findings) {
                foreach ($findings as $finding) {
                    // Prepare asset
                    $asset = array();
                    $asset['name'] = $host['ip'] . ':' . $finding['port'];
                    $asset['networkId'] = (int) $this->_networkId;
                    $asset['addressIp'] = $host['ip'];
                    $asset['addressPort'] = (int) $finding['port'];

                    // Save asset
                    $this->_saveAsset($asset);

                    // Prepare finding
                    $findingInstance = array();
                    foreach ($finding as &$data) {
                        if (!is_array($data)) {
                            $data = Fisma_String::textToHtml($data);
                        }
                    }

                    $findingInstance = array();
                    $findingInstance['uploadId'] = (int) $uploadId;
                    $findingInstance['discoveredDate'] = date('Y-m-d', strtotime($host['startTime']));
                    $findingInstance['sourceId'] = (int) $this->_findingSourceId;
                    $findingInstance['responsibleOrganizationId'] = (int) $this->_orgSystemId;
                    $findingInstance['description'] = $finding['description'] . $finding['plugin_output'];
                    $findingInstance['threat'] = $finding['synopsis'];
                    $findingInstance['recommendation'] = $finding['solution'];
                    $findingInstance['threatLevel'] = $finding['severity'];
                    $findingInstance['assetId'] = (int) $this->_assetId;

                    // Save finding
                    $this->_commit($findingInstance);
                }
            }
        }
    }
}
