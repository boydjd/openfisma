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
class Fisma_Inject_WebInspect extends Fisma_Inject_Abstract
{
    /**
     * Save vulnerabilities and assets which are recorded in the report.
     *
     * @param XMLReader $oXml The full Retina report
     * @param int $uploadId The specific scanner file id
     */
    protected function _persist(XMLReader $oXml, $uploadId)
    {
        $parsedData = array();
        $assets = array();
        $rthIds = array();
        $itemCounter = 0;
        $ignoreCurrent = false;

        $assetMap = array(
            'Host' => 'name',
            'Port' => 'addressPort',
            'Scheme' => 'service'
        );
        $vulnerabilityMap = array(
            'Name' => 'summary',
            'URL' => 'description',
            'Reference Info' => 'recommendation',
            'Fix' => 'recommendation',
            'Summary' => 'threat',
            'Implication' => 'threat',
            'Execution' => 'threat',
            'Severity' => 'threatLevel'
        );

        $discoveredDate = Fisma::now();
        while ($oXml->read()) {
            if ($oXml->name == 'StartTime' && $oXml->nodeType == XMLReader::ELEMENT) {
                $discoveredDate = $oXml->readString();
            }

            // The elements of the XML that we care about don't occur until we reach a depth of 2
            if ($oXml->depth >= 2 && $oXml->nodeType == XMLReader::ELEMENT) {
                $name = $oXml->name;
                $value = $oXml->readString();

                if ($ignoreCurrent) {
                    continue;
                }
                if ($name == 'CheckTypeID') {
                    if ($value != 'Vulnerability') {
                        $ignoreCurrent = true;
                        continue;
                    }
                }

                if (!isset($parsedData[$itemCounter])) {
                    $parsedData[$itemCounter] = array();
                }

                if (!isset($assets[$itemCounter])) {
                    $assets[$itemCounter] = array();
                }

                if ($oXml->getAttribute('id')) {
                    $parsedData[$itemCounter]['debugId'] = $oXml->getAttribute('id'); //Issue ID attribute (if provided)
                }
                //Manually parsing <ReportSection>'s
                if ($name == 'ReportSection') {
                    $nodeType = -1;
                    while ($nodeType != XMLReader::ELEMENT) {
                        $oXml->read();
                        $nodeType = $oXml->nodeType;
                    }
                    $name = $oXml->readString();

                    $nodeType = -1;
                    while ($nodeType != XMLReader::ELEMENT) {
                        $oXml->read();
                        $nodeType = $oXml->nodeType;
                    }
                    $value = $oXml->readString();
                }

                //@TODO: manually parse "Reference Info"

                //Replacing Severity with OpenFISMA standard values
                if ($name == 'Severity') {
                    switch ($value) {
                        case '4':
                            $value = 'HIGH';
                            break;
                        case '3':
                        case '2':
                            $value = 'MODERATE';
                            break;
                        case '1':
                            $value = 'LOW';
                            break;
                        case '0':
                        default:
                            $value = null;
                    }
                }

                if ($name == 'Name' && $oXml->depth > 4) {
                    continue;
                }

                if (in_array($name, array_keys($vulnerabilityMap))) {
                    if (isset($parsedData[$itemCounter][$vulnerabilityMap[$name]])) {
                        $parsedData[$itemCounter][$vulnerabilityMap[$name]] .= $value;
                    } else {
                        $parsedData[$itemCounter][$vulnerabilityMap[$name]] = $value;
                    }
                }

                if (in_array($name, array_keys($assetMap))) {
                    $assets[$itemCounter][$assetMap[$name]] = $value;
                }
            } elseif ($oXml->nodeType == XMLReader::END_ELEMENT) {
                if ($oXml->name == 'Issue') {
                    if ($ignoreCurrent) {
                        unset($parsedData[$itemCounter]);
                        unset($assets[$itemCounter]);
                    } else {
                        if (empty($assets[$itemCounter]) && $itemCounter > 0) {
                            $assets[$itemCounter] = $assets[$itemCounter - 1]; //copy from previous issue (same session)
                        }
                        $itemCounter++;
                    }
                    $ignoreCurrent = false;
                }
            }
        }
        $discoveredDate = new Zend_Date(strtotime($discoveredDate));

        foreach ($parsedData as $key => $finding) {
            if (!empty($finding['threatLevel'])) {
                // Prepare finding
                $finding['uploadId'] = (int) $uploadId;
                $finding['discoveredDate'] = (!empty($discoveredDate)) ?
                                                     $discoveredDate->toString(Fisma_Date::FORMAT_DATE) : NULL;
                $finding['sourceId'] = (int) $this->_findingSourceId;
                $finding['responsibleOrganizationId'] = (int) $this->_orgSystemId;

                // Prepare asset
                $asset = $assets[$key];
                if (empty($asset)) {
                    $asset = NULL;
                } else {
                    $asset['source'] = 'scan';
                }

                if (isset($asset['addressPort']) || isset($asset['service'])) {
                    $asset['AssetServices'] = array(array(
                        'addressPort' => $asset['addressPort'],
                        'service' => $asset['service']
                    ));
                }

                // Save finding and asset
                $this->_save($finding, $asset);
            }
        }

        // Commit all data
        $this->_commit();
    }
}
