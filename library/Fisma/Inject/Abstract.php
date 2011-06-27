<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * An abstract class for creating injection plug-ins
 *
 * This class (and it's subclasses) use the array key "finding" throughout.  However, this injection actually creates
 * vulnerabilities; we maintain the use of the term "finding" due to legacy code using this convention.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 */
abstract class Fisma_Inject_Abstract
{
    /**
     * The full xml file path to be used to the injection plugin
     * 
     * @var string
     */
    protected $_file;
    
    /**
     * The network id to be used for injection
     * 
     * @var string
     */
    protected $_networkId;
    
    /**
     * The organization id to be used for injection
     * 
     * @var string
     */
    protected $_orgSystemId;

    /**
     * The finding source id to be used for injected 
     * 
     * @var string
     */
    protected $_findingSourceId;
    
    /**
     * The summary counts array
     * 
     * @var array
     */
    private $_totals = array('reopened' => 0, 'created' => 0, 'deleted' => 0, 'reviewed' => 0);

    /**
     * collection of findings to be created 
     * 
     * @var array
     */
    private $_findings = array();

    /**
     * collection of duplicates to be logged
     * 
     * @var array
     */
    private $_duplicates = array();

    /**
     * Keep track of the uploadId passed into parse()
     *
     * @var integer
     */
    protected $_uploadId;

    /** 
     * Parse all the data from the specified file, and save it to the instance of the object by calling _save(), and 
     * then _commit() to commit to database.
     *
     * This method wraps the protected override _parse()
     *
     * Throws an exception if the file is an invalid format.
     *
     * @param string $uploadId The primary key for the upload object associated with this file
     * @throws Fisma_Inject_Exception
     */
    public function parse($uploadId)
    {
        $this->_uploadId = $uploadId;
        return $this->_parse($uploadId);
    }

    /** 
     * Parse all the data from the specified file, and save it to the instance of the object by calling _save(), and 
     * then _commit() to commit to database.
     *
     * Throws an exception if the file is an invalid format.
     *
     * @param string $uploadId The primary key for the upload object associated with this file
     * @throws Fisma_Inject_Exception
     */
    abstract protected function _parse($uploadId);

    /**
     * Create and initialize a new plug-in instance for the specified file
     * 
     * @param string $file The specified xml file path
     * @param string $networkId The specified network id
     * @param string $systemId The specified organization id
     * @param string $findingSourceId The specified finding source id
     */
    public function __construct($file, $networkId) 
    {
        $this->_file            = $file;
        $this->_networkId       = $networkId;
    }

    /**
     * The get handler method is overridden in order to provide read-only access to the summary counts for
     * this plug-in.
     *
     * Example: echo "Created {$plugin->created} findings";
     * 
     * @param string $field The specified summary counts key
     * @return int The summary count value of the specified key
     */
    public function __get($field) 
    {
        return (!empty($this->_totals[$field])) ? $this->_totals[$field] : 0;
    }

    /**
     * Save data to instance 
     * 
     * @param array $findingData 
     * @param array $assetData 
     * @param array $productData 
     */
    protected function _save($findingData, $assetData = NULL, $productData = NULL)
    {
        if (empty($findingData)) {
            throw new Fisma_Inject_Exception('Save cannot be called without finding data!');
        }

        // Add data to provided assetData
        if (!empty($assetData)) {
            $assetData['networkId'] = $this->_networkId;
            $assetData['id'] = $this->_prepareAsset($assetData);
            $findingData['assetId'] = $assetData['id'];
        }

        // Add data to provided productData
        if (!empty($productData)) {
            $assetData['productId'] = $this->_prepareProduct($productData);
        }

        // Prepare finding
        $finding = new Vulnerability();
        $finding->merge($findingData);

        // Handle related objects, since merge doesn't
        if (!empty($findingData['cve'])) {
            foreach ($findingData['cve'] as $cve) {
                $finding->Cves[]->value = $cve;
            }
        }

        if (!empty($findingData['bugtraq'])) {
            foreach ($findingData['bugtraq'] as $bugtraq) {
                $finding->Bugtraqs[]->value = $bugtraq;
            }
        }

        if (!empty($findingData['xref'])) {
            foreach ($findingData['xref'] as $xref) {
                $finding->Xrefs[]->value = $xref;
            }
        }

        // Handle duplicated findings
        $duplicateFinding = $this->_getDuplicateFinding($finding);
        if ($duplicateFinding) {
            $this->_duplicates[] = array(
                'vulnerability' => $duplicateFinding,
                'action' => $duplicateFinding->status == 'FIXED' ? 'REOPEN' : 'SUPPRESS',
                'message' => 'This vulnerability was discovered again during a subsequent scan.'
            );
            // Deleted findings are not saved, so we exit the _save routine
            $finding->free();
            unset($finding);
            return;
        } else {
            // Store data in instance to be committed later
            $this->_findings[] = array('finding' => $finding, 'asset' => $assetData, 'product' => $productData);
        }
    }

    /**
     * Commit all data that has been saved 
     *
     * Subclasses should call this function to commit findings rather than committing new findings directly.
     */
    protected function _commit() 
    {
        Doctrine_Manager::connection()->beginTransaction();

        try {
            // commit the new vulnerabilities
            foreach ($this->_findings as &$findingData) {
                if (@!$findingData['asset']['productId'] && !empty($findingData['product'])) {
                    $findingData['asset']['productId'] = $this->_saveProduct($findingData['product']);
                }

                if (!$findingData['asset']['id']) {
                    $findingData['asset']['id'] = $this->_saveAsset($findingData['asset']);
                }

                $findingData['finding']->assetId = $findingData['asset']['id'];
                $findingData['finding']->save();
                $this->_totals['created']++;

                $vUpload = new VulnerabilityUpload();
                $vUpload->vulnerabilityId = $findingData['finding']->id;
                $vUpload->uploadId = $this->_uploadId;
                $vUpload->action = 'CREATE';
                $vUpload->save();
                $vUpload->free();
                unset($vUpload);

                $findingData['finding']->free();
                unset($findingData['finding']);
            }

            // append audit log messages
            foreach ($this->_duplicates as $duplicate) {
                $vuln = $duplicate['vulnerability'];
                $mesg = $duplicate['message'];
                $action = $duplicate['action'];
                $vuln->getAuditLog()->write($mesg);
                if ($action == 'REOPEN') {
                    $this->_totals['reopened']++;
                    $vuln->status = 'OPEN';
                    $vuln->save();
                } else {
                    if (!isset($this->_totals['suppressed'])) { 
                        $this->_totals['suppressed'] = 0;
                    }
                    $this->_totals['suppressed']++;
                }

                $vUpload = new VulnerabilityUpload();
                $vUpload->vulnerabilityId = $vuln->id;
                $vUpload->uploadId = $this->_uploadId;
                $vUpload->action = $action;
                $vUpload->save();
                $vUpload->free();
                unset($vUpload);

                $vuln->free();
                unset($vuln);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }
    }

    /**
     * Get a duplicate of the specified finding
     * 
     * @param $finding A finding to check for duplicates
     * @return bool|Vulnerability Return a duplicate finding or FALSE if none exists
     */
    private function _getDuplicateFinding($finding)
    {
        // a vulnerability can't be a duplicate if it has no assetId
        if (empty($finding->assetId)) {
            return false;
        }

        /**
         * In order to properly compare the current finding against persisted findings, we need to apply the same html
         * purification that the Xss Listener applies
         */
        $xssListener = new XssListener();
        $cleanDescription = $xssListener->getPurifier()->purify($finding->description);
        
        $duplicateFindings = Doctrine_Query::create()
            ->select('v.id, v.status')
            ->from('Vulnerability v')
            ->where('v.description LIKE ?', $cleanDescription)
            ->andWhere('v.assetId = ?', $finding->assetId)
            ->execute();

        return $duplicateFindings->count() > 0 ? $duplicateFindings[0] : FALSE;
    }
    
    /**
     * Get the existing asset id if it exists 
     * 
     * @param mixed $passetData 
     * @return int|boolean 
     */
    private function _prepareAsset($assetData)
    {
        // Verify whether asset exists or not
        $assetQuery = Doctrine_Query::create()
                      ->select('id, deleted_at')
                      ->from('Asset a')
                      ->where('a.networkId = ?', $assetData['networkId']);
        if (empty($assetData['addressIp'])) {
            $assetQuery->andWhere('a.addressIp IS NULL');
        } else {
            $assetQuery->andWhere('a.addressIp = ?', $assetData['addressIp']);
        }
        if (empty($assetData['addressPort'])) {
            $assetQuery->andWhere('a.addressPort IS NULL');
        } else {
            $assetQuery->andWhere('a.addressPort = ?', $assetData['addressPort']);
        }
        $assetRecord = $assetQuery->orWhere('a.deleted_at IS NOT NULL')
                                  ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                                  ->execute();

        //  If the vulnerability references to the existing and soft delete'd asset, then active asset.
        $deletedAt = ($assetRecord) ? $assetRecord[0]['deleted_at'] : FALSE;
        if ($deletedAt) {
            $query = Doctrine_Query::create()
                     ->update('Asset a')
                     ->set('a.deleted_at', 'NULL')
                     ->where('a.id = ?', $assetRecord[0]['id'])
                     ->execute();
        }

        return ($assetRecord) ? $assetRecord[0]['id'] : null;
    }

    /**
     * Save the asset
     *
     * @param array $assetData The asset data to save
     * @return int id of saved asset 
     */
    private function _saveAsset($assetData)
    {
        $asset = new Asset();

        $asset->merge($assetData);
        $asset->save();
        
        $id = $asset->id;

        // Check to see if any of the pending assets are duplicates, if so, update the finding to point to the correct 
        // asset id
        foreach ($this->_findings as &$findingData) {
            if (empty($findingData['finding']->Asset) && $findingData['asset'] == $assetData) {
                $findingData['asset']['id'] = $id;
            }
        }
        // Free object
        $asset->free();
        unset($asset);

        return $id;
    }

    /**
     * Get the existing product id if it exists_
     * 
     * @param array $productData 
     * @return int|boolean 
     */
    private function _prepareProduct($productData)
    {
        // Verify whether product exists or not
        $productRecordQuery = Doctrine_Query::create()
                              ->select('id')
                              ->from('Product p')
                              ->setHydrationMode(Doctrine::HYDRATE_NONE);

        // Match existing products on the CPE ID if it is available, otherwise match on name, vendor, and version
        if (isset($productData['cpeName'])) {
            $productRecordQuery->where('p.cpename = ?', $productData['cpeName']);
        } else {
            if (empty($productData['name'])) {
                $productRecordQuery->andWhere('p.name IS NULL');
            } else {
                $productRecordQuery->andWhere('p.name = ?', $productData['name']);
            }
            
            if (empty($productData['vendor'])) {
                $productRecordQuery->andWhere('p.vendor IS NULL');
            } else {
                $productRecordQuery->andWhere('p.vendor = ?', $productData['vendor']);
            }

            if (empty($productData['version'])) {
                $productRecordQuery->andWhere('p.version IS NULL');
            } else {
                $productRecordQuery->andWhere('p.version = ?', $productData['version']);
            }
        }

        $productRecord = $productRecordQuery->execute();

        return ($productRecord) ? $productRecord[0][0] : FALSE;
    }
    
    /**
     * Save product and update asset's product
     *
     * @param array $productData The product data to save
     * @return void
     */
    private function _saveProduct($productData)
    {
        $product = new Product();
        $product->merge($productData);
        $product->save();

        $id = $product->id;

        $product->free();
        unset($product);

        // Check to see if any of the pending products are duplicates, if so, update the finding to point to the
        // correct product id
        foreach ($this->_findings as &$findingData) {
            if (empty($findingData['asset']['productId']) && $findingData['product'] == $productData) {
                $findingData['asset']['productId'] = $id;
            }
        }

        return $id;
    }
}
