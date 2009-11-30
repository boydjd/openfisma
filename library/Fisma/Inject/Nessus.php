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
     */
    public function parse($uploadId)
    {
        // Parse the XML file
        $report = simplexml_load_file($this->_file);
        
        // Make sure that this is an Nessus report, not a Crystal report. 
        $checkCrystalReport = $report->getNamespaces(true);
        if (in_array('urn:crystal-reports:schemas', $checkCrystalReport)) {
            throw new Fisma_Exception_InvalidFileFormat('This is a Crystal Report, not a Nessus report.');
        }
        $this->_persist($report, $uploadId);
    }

    /**
     * Save assets and findings which are recorded in the report.
     *
     * @param SimpleXMLElement $report The full Nessus report
     * @param int $uploadId the specific scanner file id
     */
    private function _persist($report, $uploadId)
    {
        // Parse the discovered date/time out of the starttime field
        $startTime = $report->xpath('/NessusClientData/Report/StartTime');
        if (empty($startTime)) {
            throw new Fisma_Exception_InvalidFileFormat('Expected StartTime field, but found none');
        }
        // Asset information is parsed out of the ReportHost section
        $reportHosts = $report->xpath('/NessusClientData/Report/ReportHost');
        if (empty($reportHosts)) {
            throw new Fisma_Exception_InvalidFileFormat('Expected ReportHost field, but found none');
        }
        foreach ($reportHosts as $key => $host ) {
            $addressIp = $host->HostName;
            if (empty($addressIp)) {
                throw new Fisma_Exception_InvalidFileFormat('Expected HostName field, but found none');
            }
            foreach ($host->ReportItem as $item) {
                if (!isset($item->severity)) {
                    throw new Fisma_Exception_InvalidFileFormat('Expected severity field, but found none');
                }
                if (empty($item->severity)) {
                    continue;
                } else {
                    switch ($item->severity) {
                        case '1':
                            $threatLevel = 'LOW';
                            break;
                        case '2':
                            $threatLevel = 'MODERATE';
                            break;
                        case '3':
                            $threatLevel = 'HIGH';
                            break;
                    }
                }
                if (!isset($item->port)) {
                    throw new Fisma_Exception_InvalidFileFormat('Expected port field, but found none');
                }
                if (preg_match('/\d+/', $item->port, $port)) {
                    $asset['addressIp']   = $addressIp;
                    $asset['addressPort'] = $port[0];
                    $asset['name'] = $asset['addressIp'] . ':' . $asset['addressPort'];
                } else {
                    continue;
                }
                $this->_saveAsset($asset);

                $reportData = $this->textToHtml($item->data);
                if (empty($reportData)) {
                    throw new Fisma_Exception_InvalidFileFormat('Expected data field, but found none');
                }
                $finding['uploadId'] = $uploadId;
                $finding['discoveredDate'] = date('Y-m-d', strtotime($startTime[$key]));
                $finding['sourceId']       = $this->_findingSourceId;
                $finding['responsibleOrganizationId'] = $this->_orgSystemId;
                $finding['description']    = $this->_getSubContent($reportData, 'Synopsis :', 'Description');
                $finding['threat']         = $this->_getSubContent($reportData, 'Description :', 'Solution');
                $finding['recommendation'] = $this->_getSubContent($reportData, 'Solution :', 'Risk factor');
                $finding['threatLevel']    = $threatLevel;
                $finding['assetId']        = $this->_assetId;
                $this->_commit($finding);
            }
        }
    }
    
    /**
     * Get the content from a start string to end string
     *
     * @param string $str orginal content 
     * @param string $start
     * @param string $end
     * @return string
     */
    private function _getSubContent($str, $start, $end)
    {
        if ($start == '' || $end == '') {
               return;
        }
        $str = explode($start, $str);
        $str = explode($end, $str[1]);
        return $str[0];
    }
}
