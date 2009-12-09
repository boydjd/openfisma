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
 * An abstract class for creating injection plug-ins
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 * @version    $Id$
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
     * The finding source id to be used for injection
     * 
     * @var string
     */
    protected $_findingSourceId;
    
    /**
     * The asset id to be used for injection
     * 
     * @var string
     */
    protected $_assetId;
    
    /**
     * insert finding ids
     * 
     * @var array
     */
    private $_findingIds = array();
    
    /**
     * The summary counts array
     * 
     * @var array
     */
    private $_totalFindings = array('created' => 0,
                                    'deleted' => 0,
                                    'reviewed' => 0);
    
    /**
     * The constant defines the possible specific finding action the finding should be created and set to NEW status.
     */
    const CREATE_FINDING = 1;
    
    /**
     * The constant defines the possible specific finding action the finding should be deleted.
     */
    const DELETE_FINDING = 2;
    
    /**
     * The constant defines the possible specific finding action the finding should be created and set to PEND status.
     */
    const REVIEW_FINDING = 3;
    
    /**
     * Create and initialize a new plug-in instance for the specified file
     * 
     * @param string $file The specified xml file path
     * @param string $networkId The specified network id
     * @param string $systemId The specified organization id
     * @param string $findingSourceId The specified finding source id
     * @return void
     */
    public function __construct($file, $networkId, $systemId, $findingSourceId) 
    {
        $this->_file = $file;
        $this->_networkId = $networkId;
        $this->_orgSystemId = $systemId;
        $this->_findingSourceId = $findingSourceId;
        $this->_checkFile();
    }

    /**
     * Check errors for the upload file and verify if the upload file is valid xml format
     * 
     * @return void
     * @throws Fisma_Exception_InvalidFileFormat if the upload file is not a valid XML format
     */
    protected function _checkFile()
    {
        libxml_use_internal_errors(true);
        $report = simplexml_load_file($this->_file);
        if ($report === false) {
            // libxml and simplexml are interoperable:
            $errors = libxml_get_errors();
            $errorHtml = '<p>Parse Errors:<br>';
            foreach ($errors as $error) {
                $errorHtml .= "\"{$error->message}\" at line {$error->line}, column {$error->column}<br>"; 
            }
            $errorHtml .= '</p>';
            throw new Fisma_Exception_InvalidFileFormat('This file is not a valid XML format. 
                                                   Please ensure that you selected the correct file.'
                                                . $errorHtml);
        }
    }

    /**
     * Conditionally commit the specific finding.
     * 
     * The finding is evaluated with respect to the Injection Filtering rules. The finding may be committed or it may be
     * deleted based on the filter rules.
     * 
     * Subclasses must call this function to commit findings rather than committing new findings directly.
     * 
     * @param array $findingData Column data for the new finding object action was taken.
     * @return void
     */
    protected function _commit($findingData) 
    {
        Doctrine_Manager::connection()->beginTransaction();
        $finding = new Finding();
        $finding->merge($findingData);
        $finding->save();
        if ($finding->status == 'PEND') {
            $duplicate = $finding->DuplicateFinding;
            // If a duplicate exists, then run the Injection Filtering rules
            if ($duplicate->type == 'NONE' || $duplicate->type == 'CAP' || $duplicate->type == 'FP') {
                if ($finding->responsibleOrganizationId == $duplicate->responsibleOrganizationId) {
                    if ($duplicate->status == 'CLOSED') {
                        $this->_totalFindings['created']++;
                        Doctrine_Manager::connection()->commit();
                    } else {
                        $this->_totalFindings['deleted']++;
                        Doctrine_Manager::connection()->rollback();
                    }
                } else {
                    $this->_totalFindings['reviewed']++;
                    Doctrine_Manager::connection()->commit();
                }
            } elseif ($duplicate->type == 'AR') {
                if ($duplicate->responsibleOrganizationId == $finding->responsibleOrganizationId) {
                    $this->_totalFindings['deleted']++;
                    Doctrine_Manager::connection()->rollback();
                } else {
                    $this->_totalFindings['reviewed']++;
                    Doctrine_Manager::connection()->commit();
                }
            }
        } else {
            $this->_totalFindings['created']++;
            Doctrine_Manager::connection()->commit();
        }
    }
    
    /**
     * The get handler method is overridden in order to provide read-only access to the summary counts for
     * this plug-in.
     *
     * Example: echo "Created {$plugin->created} findings";
     * 
     * @param string $field The specified summary counts key
     * @return int|null The summary count value of the specified key
     */
    public function __get($field) 
    {
        if (array_key_exists($field, $this->_totalFindings)) {
            return $this->_totalFindings[$field];
        } else {
            return null;
        }
    }

    /** 
     * Parse all the data from the specified file, and load it into the database.
     *
     * Throws an exception if the file is an invalid format.
     *
     * @param string $uploadId The id of the upload file.
     * @return void
     * @throws Fisma_Exception_InvalidFileFormat if the file is an invalid format
     */
    abstract public function parse($uploadId);

    /**
     * Save or get the asset id which is associated with the address ip and port
     * If there has the same ip, port and network, get the exist asset id, else create a new one
     *
     * @param array $assetData The asset data to save
     * @return void
     */
    protected function _saveAsset($assetData)
    {
        $assetData['networkId'] = $this->_networkId;
        $assetData['orgSystemId'] = $this->_orgSystemId;
        $assetData['source'] = 'SCAN';
        // Verify whether asset exists or not
        $assetRecord  = Doctrine_Query::create()
                         ->select()
                         ->from('Asset a')
                         ->where('a.networkId = ?', $assetData['networkId'])
                         ->andWhere('a.addressIp = ?', $assetData['addressIp'])
                         ->andWhere('a.addressPort = ?', $assetData['addressPort'])
                         ->execute()
                         ->toArray();
        if ($assetRecord) {
            $this->_assetId = $assetRecord[0]['id'];
        } else {
            $asset = new Asset();
            $asset->merge($assetData);
            $asset->save();
            $this->_assetId = $asset->id;
        }
    }
    
    /**
     * Save product and update asset's product
     *
     * @param array $productData The product data to save
     * @return void
     */
    protected function _saveProduct($productData)
    {
        $product = new Product();
        $asset   = new Asset();
        $asset   = $asset->getTable()->find($this->_assetId);
        if (empty($asset->productId)) {
            $existedProduct = $product->getTable('Product')->findOneByCpeName($productData['cpeName']);
            if ($existedProduct) {
                // Use the existing product if one is found
                $asset->productId = $existedProduct->id;
            } else {
                // If no existing product, create a new one
                $product->merge($productData);
                $product->save();
                $asset->productId = $product->id;
            }
            $asset->getTable()->getRecordListener()->setOption('disabled', true);
            $asset->save();
        } else {
            // If the asset does have a product, then do not modify it unless the CPE name is null,
            // in which case update the CPE name.
            $product = $product->getTable()->find($asset->productId);
            if ($product && empty($product->cpeName)) {
                $product->cpeName = $productData['cpeName'];
                $product->save();
            }
        }
    }

    /**
     * Convert plain text into a similar HTML representation.
     * 
     * @param string $plainText The plain text that needs to be marked up
     * @return string The rendered HTML snippet of the plain text
     * @todo refactor, put this into a class that is available system-wide, that means make this method reusable
     */
    protected function textToHtml($plainText) 
    {
        $html = '<p>' . trim($plainText) . '</p>';
        $html = str_replace("\\n\\n", '</p><p>', $html);
        $html = str_replace("\\n", '<br>', $html);
        return $html;
    }
}
