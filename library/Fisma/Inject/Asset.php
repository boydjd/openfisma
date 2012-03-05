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
 * Imports assets and their related products 
 * 
 * @package Fisma
 * @subpackage Import
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Inject_Asset extends Fisma_Inject_Abstract
{

    /**
     * Array of assets 
     * 
     * @var array
     */
    private $_assets = array();

    /**
     * Array of products 
     * 
     * @var array
     */
    private $_products = array();

    /**
     * Parse assets out of the imported file 
     * 
     * @return boolean 
     */
    protected function _parse($uploadId)
    {
        $type = self::_detectFilterType($this->_file);
        if (!$type || !is_string($type)) {
            $this->_setMessage(array('warning' => 'The uploaded file is not a supported file format.'));
            return FALSE;
        }

        $filterClass = 'Fisma_Inject_Filter_' . $type;
        $filter = new $filterClass($this->_file, $this->_orgSystemId, $this->_networkId);

        if (!$this->_assets = $filter->getAssets()) {
            $this->_setMessage(array('warning' => 'Unable to load assets from XML.'));
            return FALSE;
        }

        foreach ($this->_assets as $key => &$asset) {
            // Mark asset as duplicate if it already exists
            if ($this->_getDuplicateAsset($asset)) {
                $asset['duplicate'] = TRUE;
            } else {
                if (isset($asset['Product'])) {
                    $this->_products[$key] = $asset['Product'];
                    unset($asset['Product']);
                }

                $asset['duplicate'] = FALSE;
            }
        }

        if (!$this->_saveProducts()) {
            $this->_setMessage(array('warning' => 'Unable to save products.'));
            return FALSE;
        } elseif (!$this->_save()) {
            $this->_setMessage(array('warning' => 'Unable to save assets.'));
            return FALSE;
        } elseif (!$this->_commit()) {
            $this->_setMessage(array('warning' => 'Unable to commit assets.'));
            return FALSE;
        } else {
            
            if ($this->_numImported > 0) {
                $this->_setMessage(array('notice' => $this->_numImported . ' asset(s) were imported successfully.'));
            }

            if ($this->_numSuppressed > 0) {
                $this->_setMessage(array('notice' => $this->_numSuppressed . ' asset(s) were not imported.'));
            }

            return TRUE;
        }
    }

    /**
     * Create and commit new products 
     * 
     * @return boolean
     */
    protected function _saveProducts()
    {
        // Save products and assign product id to appropriate asset
        Doctrine_Manager::connection()->beginTransaction();
        try {
            if (!empty($this->_products)) {
                foreach ($this->_products as $key => &$product) {
                    if (!$existingId = $this->_getDuplicateProduct($product)) {
                        $p = new Product();
                        $p->merge($product);
                        $p->save();
                        $existingId = $p->id;
                        $p->free();
                        unset($p);
                    }
                    $this->_assets[$key]['productId'] = $existingId;
                }
            }

            Doctrine_Manager::connection()->commit();
            return TRUE;
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            return FALSE;
        }
    }

    /**
     * Create new assets and save in $this->_assets for commit 
     * 
     * @return boolean
     */
    protected function _save()
    {
        try {
            foreach ($this->_assets as &$asset) {
                if (!$asset['duplicate']) {
                    $assetObj = new Asset();
                    $assetObj->merge($asset);
                    $asset = $assetObj;
                } else {
                    $this->_numSuppressed++;
                }
            }
            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Commit new assets
     * 
     * @return boolean 
     */
    protected function _commit()
    {
        Doctrine_Manager::connection()->beginTransaction();
         
        try {
            foreach ($this->_assets as &$asset) {
                if (!is_array($asset)) {
                    $asset->save();
                    $asset->free();
                    unset($asset);
                    $this->_numImported++;
                }
            }
            Doctrine_Manager::connection()->commit();
            return TRUE;
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            return FALSE;
        }
    }

    /**
     * Search for pre-existing product
     * 
     * @param mixed $product 
     * @return int
     */
    private function _getDuplicateProduct($product)
    {
        $q = Doctrine_Query::create()
            ->select('p.id')
            ->from('Product p')
            ->where('p.name = ?', $product['name'])
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        if (empty($product['version'])) {
            $q->andWhere('p.version IS NULL');
        } else {
            $q->andWhere('p.version = ?', $product['version']);
        }
            
        $result = $q->execute();

        if ($result) {
            $result = array_pop($result);
        }

        return ($result) ? $result['id'] : 0;
    }

    /**
     * Search for pre-existing asset
     * 
     * @param mixed $asset 
     * @return boolean 
     */
    private function _getDuplicateAsset($asset)
    {
        $duplicateAssets = Doctrine_Query::create()
            ->select('a.id')
            ->from('Asset a')
            ->where('addressIp = ?', $asset['addressIp'])
            ->andWhere('networkId = ?', $this->_networkId)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        if (empty($asset['addressPort'])) {
            $duplicateAssets->andWhere('addressPort IS NULL');
        } else {
            $duplicateAssets->andWhere('addressPort = ?', $asset['addressPort']);
        }

        if (empty($asset['orgSystemId'])) {
            $duplicateAssets->andWhere('orgSystemId IS NULL');
        } else {
            $duplicateAssets->andWhere('orgSystemId = ?', $asset['orgSystemId']);
        }

        $duplicateAssets = $duplicateAssets->execute();

        return ($duplicateAssets) ? TRUE : FALSE;
    }

    /**
     * Attempt to detect the type of the file uploaded 
     * 
     * @param string $filename 
     * @return string|boolean 
     */
    private static function _detectFilterType($filename)
    {
        $handle = fopen($filename, "rb");
        $contents = fread($handle, 128);
        fclose($handle);

        if (stristr($contents, 'Nmap')) {
            return 'Nmap';
        } else {
            return FALSE;
        }
    }
}
