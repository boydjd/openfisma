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
 * Interface to nmap
 *
 * @package Fisma_Import_Filter
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Inject_Filter_Nmap
{
    /**
     * Path to file
     *
     * @var string
     */
    private $_filePath;

    /**
     * Organization ID
     *
     * @var int
     */
    private $_orgSystemId;

    /**
     * Network ID
     *
     * @var int
     */
    private $_networkId;

    /**
     * XML loaded into XMLReader
     *
     * @var XMLReader
     * @access private
     */
    private $_report;

    /**
     * Constructor
     *
     * @param string $filePath
     * @param int $orgSystemId
     * @param int $networkId
     * @return void|boolean
     */
    public function __construct($filePath, $orgSystemId, $networkId)
    {
        $this->_filePath = $filePath;
        $this->_orgSystemId = $orgSystemId;
        $this->_networkId = $networkId;

        $report = new XMLReader();

        if (!$report->open($this->_filePath, NULL, 1<<19)) {
            return FALSE;
        }

        $this->_report = $report;
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        $this->_report->close();
    }

    /**
     * Return assets from an nmap report
     *
     * @access public
     * @return array|boolean
     */
    public function getAssets()
    {
        try {
            $assets = array();
            $this->_parseAssets($assets);

            return $assets;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Parse assets out of nmap report
     *
     * @param array $assets
     * @access private
     */
    private function _parseAssets(&$assets)
    {
        $parsedData = array();
        $hostCounter = 0;
        $portCounter = 0;
        $state = 'down';

        while ($this->_report->read()) {
            if ($this->_report->depth >= 1 && $this->_report->nodeType == XMLReader::ELEMENT) {
                if ($this->_report->name == 'host') {
                    $parsedData[$hostCounter] = array();
                    $parsedData[$hostCounter]['ports'] = array();
                } elseif ($this->_report->name == 'address' && $this->_report->getAttribute('addrtype') == 'ipv4') {
                    $parsedData[$hostCounter]['ip'] = $this->_report->getAttribute('addr');
                } elseif ($this->_report->name == 'port') {
                    $parsedData[$hostCounter]['ports'][$portCounter]['port'] = $this->_report->getAttribute('portid');
                    $parsedData[$hostCounter]['ports'][$portCounter]['protocol'] =
                        $this->_report->getAttribute('protocol');
                } elseif ($this->_report->name == 'service') {
                    $parsedData[$hostCounter]['ports'][$portCounter]['service'] =
                        $this->_report->getAttribute('name');
                    $parsedData[$hostCounter]['ports'][$portCounter]['product'] =
                        $this->_report->getAttribute('product');
                    $parsedData[$hostCounter]['ports'][$portCounter]['version'] =
                        $this->_report->getAttribute('version');
                } elseif ($this->_report->name == 'cpe') {
                    $parsedData[$hostCounter]['ports'][$portCounter]['cpe'] = $this->_report->readString();
                } elseif ($this->_report->name == 'osmatch') {
                    $parsedData[$hostCounter]['os'] = $this->_report->getAttribute('name');
                } elseif ($this->_report->name === 'status') {
                    $state = $this->_report->getAttribute('state');
                }
            } elseif ($this->_report->nodeType == XMLReader::END_ELEMENT) {
                if ($this->_report->name == 'host') {
                    $portCounter = 0;
                    if ($state === 'up') {
                        $hostCounter++;
                    } else {
                        // throw out down hosts
                        unset($parsedData[$hostCounter]);
                    }
                } elseif ($this->_report->name == 'port') {
                    $portCounter++;
                }
            }
        }

        $keyPtr = 0;

        foreach ($parsedData as $hosts => $host) {
            $assets[$keyPtr]['name'] = $host['ip'];
            $assets[$keyPtr]['source'] = 'scan';
            $assets[$keyPtr]['addressIp'] = $host['ip'];
            $assets[$keyPtr]['orgSystemId'] = !empty($this->_orgSystemId) ? (int) $this->_orgSystemId : NULL;
            $assets[$keyPtr]['networkId'] = $this->_networkId;
            if (!empty($host['os'])) {
                $assets[$keyPtr]['Product']['name'] = $host['os'];
            }

            // Create a service for each port detected
            $serviceIndex = 0;
            foreach ($host['ports'] as $port) {
                $assets[$keyPtr]['AssetServices'][$serviceIndex]['addressPort'] = $port['port'];

                // Handle create of the product name, since it's built from different report fields depending on
                // what is defined
                if (empty($port['product'])) {
                    if (!empty($port['name'])) {
                        if (empty($port['version'])) {
                            $assets[$keyPtr]['AssetServices'][$serviceIndex]['Product']['name'] = $port['name'];
                        } else {
                            $assets[$keyPtr]['AssetServices'][$serviceIndex]['Product']['name'] = $port['name'] . ' ' . $port['version'];
                        }
                    }
                } else {
                    if (empty($port['version'])) {
                        $assets[$keyPtr]['AssetServices'][$serviceIndex]['Product']['name'] = $port['product'];
                    } else {
                        $assets[$keyPtr]['AssetServices'][$serviceIndex]['Product']['name'] = $port['product'] . ' ' . $port['version'];
                    }
                }

                if (!empty($port['version'])) {
                    $assets[$keyPtr]['AssetServices'][$serviceIndex]['Product']['version'] = $port['version'];
                }

                if (!empty($port['cpe'])) {
                    $assets[$keyPtr]['AssetServices'][$serviceIndex]['Product']['cpeName'] = $port['cpe'];
                }

                if (!empty($port['protocol'])) {
                    $assets[$keyPtr]['AssetServices'][$serviceIndex]['protocol'] = $port['protocol'];
                }

                if (!empty($port['service'])) {
                    $assets[$keyPtr]['AssetServices'][$serviceIndex]['service'] = $port['service'];
                }

                $serviceIndex++;
            }
            $keyPtr++;
        }
    }
}
