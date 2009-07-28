<?php 
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: Abstract.php 1560 2009-04-13 14:59:12Z mehaase $
 * @package   Fisma_Inject
 *
 */

/**
 * An abstract class for creating injection plug-ins
 *
 * @package   Fisma_Inject
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
abstract class Fisma_Inject_Abstract
{
    protected $_file;
    protected $_networkId;
    protected $_orgSystemId;
    protected $_findingSourceId;
    protected $_assetId;
    
    /**
     * insert finding ids
     */
    private $_findingIds = array();
    
    private $_totalFindings = array('created' => 0,
                                    'deleted' => 0,
                                    'reviewed' => 0);
    
    /**
     * These constants define possible actions to take on a specific finding.
     *
     * CREATE_FINDING means that the finding should be created and set to NEW status.
     * DELETE_FINDING means that the finding should be deleted (aka "surpressed").
     * REVIEW_FINDING means that the finding should be created and set to PEND status.
     */
    const CREATE_FINDING = 1;
    const DELETE_FINDING = 2;
    const REVIEW_FINDING = 3;
    
    /**
     * __construct() - Create a new plug-in instance for the specified file
     *
     * @param string $file
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
     * Check errors for the upload file
     *
     * @throw errors
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
     * _commit() - Conditionally commit the specific finding.
     *
     * The finding is evaluated with respect to the Injection Filtering rules. The finding may be committed or it may be
     * deleted based on the filter rules.
     *
     * Subclasses must call this function to commit findings rather than committing new findings directly.
     *
     * @param array $findingData Column data for the new finding object
     * action was taken.
     */
    protected function _commit($findingData) {
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
     * __get() - The get handler method is overridden in order to provide read-only access to the summary counts for
     * this plug-in.
     *
     * Example: echo "Created {$plugin->created} findings";
     *
     * @param string $field
     * @return mixed
     */
    public function __get($field) {
        if (array_key_exists($field, $this->_totalFindings)) {
            return $this->_totalFindings[$field];
        } else {
            return null;
        }
    }

    /** 
     * parse() - Parse all the data from the specified file, and load it into the database.
     *
     * Throws an exception if the file is an invalid format.
     *
     * @param string $uploadId The id of this uploading.
     * @return Return the number of findings created.
     */
    abstract public function parse($uploadId);

    /**
     * Save or get the asset id which is associated with the address ip and port
     * If there has the same ip, port and network, get the exist asset id, else create a new one
     *
     * @param array $assetData asset data
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
     * @param array $productData product data
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
     * @todo refactor, put this into a class that is available system-wide
     * @param string $plainText Plain text that needs to be marked up
     * @return string HTML version of $plainText
     */
    protected function textToHtml($plainText) {
        $html = '<p>' . trim($plainText) . '</p>';
        $html = str_replace("\\n\\n", '</p><p>', $html);
        $html = str_replace("\\n", '<br>', $html);
        return $html;
    }
}
