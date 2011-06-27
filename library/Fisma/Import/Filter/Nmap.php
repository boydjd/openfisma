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
class Fisma_Import_Filter_Nmap
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
            $this->_parseAssets(&$assets);

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

        while ($this->_report->read()) {
            if ($this->_report->depth >= 1 && $this->_report->nodeType == XMLReader::ELEMENT) {
                if ($this->_report->name == 'host') {
                    $parsedData[$hostCounter] = array();
                    $parsedData[$hostCounter]['ports'] = array();
                } elseif ($this->_report->name == 'address' && $this->_report->getAttribute('addrtype') == 'ipv4') {
                    $parsedData[$hostCounter]['ip'] = $this->_report->getAttribute('addr');
                } elseif ($this->_report->name == 'port') {
                    $parsedData[$hostCounter]['ports'][$portCounter]['port'] = $this->_report->getAttribute('portid');
                } elseif ($this->_report->name == 'service') {
                    $parsedData[$hostCounter]['ports'][$portCounter]['product'] =
                        $this->_report->getAttribute('product');
                    $parsedData[$hostCounter]['ports'][$portCounter]['version'] =
                        $this->_report->getAttribute('version');
                    $parsedData[$hostCounter]['ports'][$portCounter]['name'] = $this->_report->getAttribute('name');
                } elseif ($this->_report->name == 'osmatch') {
                    $parsedData[$hostCounter]['os'] = $this->_report->getAttribute('name');
                }
            } elseif ($this->_report->nodeType == XMLReader::END_ELEMENT) {
                if ($this->_report->name == 'host') {
                    $hostCounter++;
                    $portCounter = 0;
                } elseif ($this->_report->name == 'port') {
                    $portCounter++;
                }
            }
        }

        $keyPtr = 0;

        foreach ($parsedData as $hosts => $host) {
            // Create an asset for the host if the OS of the host has been detected
            if (!empty($host['os'])) {
                $assets[$keyPtr]['name'] = $host['ip'];
                $assets[$keyPtr]['source'] = 'scan';
                $assets[$keyPtr]['addressIp'] = $host['ip'];
                $assets[$keyPtr]['orgSystemId'] = $this->_orgSystemId;
                $assets[$keyPtr]['networkId'] = $this->_networkId;
                $assets[$keyPtr]['Product']['name'] = $host['os'];
                $keyPtr++;
            }

            // Create an asset for each port detected   
            foreach ($host['ports'] as $port) {
                $assets[$keyPtr]['name'] = $host['ip'];
                $assets[$keyPtr]['source'] = 'scan';
                $assets[$keyPtr]['addressIp'] = $host['ip'];
                $assets[$keyPtr]['addressPort'] = $port['port'];
                $assets[$keyPtr]['orgSystemId'] = $this->_orgSystemId;
                $assets[$keyPtr]['networkId'] = $this->_networkId;

                // Handle create of the product name, since it's built from different report fields depending on 
                // what is defined
                if (empty($port['product'])) {
                    if (!empty($port['name'])) {
                        if (empty($port['version'])) {
                            $assets[$keyPtr]['Product']['name'] = $port['name'];
                        } else {
                            $assets[$keyPtr]['Product']['name'] = $port['name'] . ' ' . $port['version'];
                        }
                    }
                } else {
                    if (empty($port['version'])) {
                        $assets[$keyPtr]['Product']['name'] = $port['product'];
                    } else {
                        $assets[$keyPtr]['Product']['name'] = $port['product'] . ' ' . $port['version'];
                    }
                }

                if (!empty($port['version'])) {
                    $assets[$keyPtr]['Product']['version'] = $port['version'];
                }

                $keyPtr++;
            }
        }
    }
}
