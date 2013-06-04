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
    private $_totals = array('created' => 0, 'workflows' => array());

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
     * Collection of messages
     *
     * @var array
     */

    protected $_messages = array();

    /**
     * _log
     *
     * @var Zend_Log
     */
    protected $_log;

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
    protected function _parse($uploadId)
    {
        $report = new XMLReader();

        if (!$report->open($this->_file, NULL, LIBXML_PARSEHUGE)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat('Cannot open the XML file.');
        }

        try {
            $this->_persist($report, $uploadId);
        } catch (Fisma_Zend_Exception_InvalidFileFormat $e) {
            throw $e;
        } catch (Exception $e) {
            $report->close();
            $this->_log->err($e);
            throw new Fisma_Zend_Exception("An unexpected error has occurred while processing the uploaded scan result."
                                         . "<br/>This error has been logged for administrator review.", 0, $e);
        }

        $report->close();
    }

    /**
     * Save vulnerabilities and assets which are recorded in the report.
     *
     * @param XMLReader $oXml The full Retina report
     * @param int $uploadId The specific scanner file id
     */
    abstract protected function _persist(XMLReader $oXml, $uploadId);

    /**
     * Create and initialize a new plug-in instance for the specified file
     *
     * @param string $file The specified xml file path
     * @param string $networkId The specified network id
     * @param string $orgSystemId The specified organization id
     */
    public function __construct($file, $networkId, $orgSystemId)
    {
        $this->_file        = $file;
        $this->_networkId   = $networkId;
        $this->_orgSystemId = $orgSystemId;
        $this->_log = Zend_Registry::get('Zend_Log');
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
        set_time_limit(180);
        if (empty($findingData)) {
            throw new Fisma_Inject_Exception('Save cannot be called without finding data!');
        }

        // Add data to provided productData
        if (!empty($productData)) {
            $assetData['productId'] = $this->_prepareProduct($productData);
        }

        if (!empty($assetData['AssetServices'])) {
            foreach ($assetData['AssetServices'] as &$service) {
                if (!empty($service['Product'])) {
                    if (!$productId = $this->_prepareProduct($service['Product'])) {
                        $productId = $this->_saveProduct($service['Product']);
                    }
                    $service['productId'] = $productId;
                    unset($service['Product']);
                }
            }
        }

        // Add data to provided assetData
        if (!empty($assetData)) {
            $assetData['networkId'] = $this->_networkId;
            if (!empty($this->_orgSystemId)) {
                $assetData['orgSystemId'] = (int)$this->_orgSystemId;
            }
            $assetData['id'] = $this->_prepareAsset($assetData);
            $findingData['assetId'] = $assetData['id'];
        }

        // Prepare finding
        $finding = new Vulnerability();
        $finding->merge($findingData);
        $finding->createdByUserId = CurrentUser::getAttribute('id');
        $organization = Doctrine::getTable('Organization')->find($this->_orgSystemId);
        if ($organization && $organization->pocId) {
            $finding->pocId = $organization->pocId;
        }

        // Set source property
        $parts = explode('_', get_class($this));
        $finding->source = end($parts);

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

        $this->_findings[] = array('finding' => $finding, 'asset' => $assetData, 'product' => $productData);
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
            // aggregation
            if (Fisma::configuration()->getConfig('vm_aggregation')) {
                for ($i = 0; $i < count($this->_findings) - 1; $i++) {
                    for ($j = $i + 1; $j < count($this->_findings); $j++) {
                        if (isset($this->_findings[$i]['finding']) && isset($this->_findings[$j]['finding'])) {
                            if (
                                $this->_findings[$i]['finding']->summary === $this->_findings[$j]['finding']->summary &&
                                $this->_findings[$i]['asset'] === $this->_findings[$j]['asset']
                            ) {
                                $this->_findings[$i]['finding']->description .= "<p>(aggregating...)</p>" .
                                    $this->_findings[$j]['finding']->description;
                                unset($this->_findings[$j]['finding']);
                            }
                        }
                    }
                }
            }

            // commit the new vulnerabilities
            foreach ($this->_findings as &$findingData) {
                set_time_limit(180);
                if (empty($findingData['finding'])) {
                    continue;
                }

                // Detect duplicated findings
                $duplicateFinding = $this->_getDuplicateFinding($findingData['finding']);
                $reopenStepId = Fisma::configuration()->getConfig('vm_reopen_source');
                if ($duplicateFinding) {
                    $this->_duplicates[] = array(
                        'vulnerability' => $duplicateFinding,
                        'action' => ($duplicateFinding->currentStepId == $reopenStepId) ? 'REOPEN' : 'SUPPRESS',
                        'message' => 'This vulnerability was discovered again during a subsequent scan.'
                    );
                    continue;
                }

                if (empty($findingData['asset']['productId']) && !empty($findingData['product'])) {
                    $findingData['asset']['productId'] = $this->_saveProduct($findingData['product']);
                }

                if (empty($findingData['asset']['id']) && !empty($findingData['asset'])) {
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

            // Handle duplicated findings
            foreach ($this->_duplicates as $duplicate) {
                set_time_limit(180);
                $vuln = $duplicate['vulnerability'];
                $mesg = $duplicate['message'];
                $action = $duplicate['action'];
                if (!isset($vuln->id)) {
                    continue; //skip to avoid a vulnerability from being reopened twice
                }
                $vuln->getAuditLog()->write($mesg);

                if ($vuln->currentStepId) {
                    $step = $vuln->CurrentStep;
                }

                if ($action == 'REOPEN') {
                    $destinationId  = (Fisma::configuration()->getConfig('vm_reopen_destination'))
                                    ? Doctrine::getTable('Workflow')->find(
                                        Fisma::configuration()->getConfig('vm_reopen_destination')
                                    )->getFirstStep()->id
                                    : Doctrine::getTable('Workflow')
                                        ->findDefaultByModule('vulnerability')->getFirstStep()->id;

                    WorkflowStep::completeOnObject(
                        $vuln,
                        'Re-open Vulnerability',
                        'Vulnerability detected in recent scan data',
                        CurrentUser::getAttribute('id'),
                        0,
                        $destinationId
                    );
                }

                $workflowName = $step->Workflow->name;
                if (!isset($this->_totals['workflows'][$workflowName])) {
                    $this->_totals['workflows'][$workflowName] = 0;
                }
                $this->_totals['workflows'][$workflowName]++;

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

            set_time_limit(180);
            Doctrine_Manager::connection()->commit();

            $createdWord = $this->created != 1 ? ' vulnerabilities' : ' vulnerability';
            $baseUrl = rtrim(Fisma_Url::baseUrl(), '/');

            $message = 'Your scan report was successfully uploaded.<br/>'
                     . "<a target='_blank' href='" . Fisma_Url::customUrl(
                            "/vm/vulnerability/list?q=/uploadIds/textExactMatch/{$this->_uploadId}"
                    ). "'>" . $this->created . '</a> new' . $createdWord . ' created.';
            foreach ((array)($this->workflows) as $name => $count) {
                $countWord = $count != 1 ? ' vulnerabilities' : ' vulnerability';
                $count = "<a target='_blank' href='" . Fisma_Url::customUrl(
                            "/vm/vulnerability/list?q=/uploadIds/textContains/{$this->_uploadId}" .
                            "/uploadIds/textNotExactMatch/{$this->_uploadId}" .
                            "/workflow/textExactMatch/{$name}"
                      ). "'>{$count}</a>";
                $message .= "<br/>{$count} mapped to existing {$countWord} in {$name} workflow.";
            }

           $this->_setMessage(array('notice' => $message));

        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollback();
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
        $cleanSummary = $xssListener->getPurifier()->purify($finding->summary);
        $cleanDescription = $xssListener->getPurifier()->purify($finding->description);

        $duplicateFindings = Doctrine_Query::create()
            ->from('Vulnerability v')
            ->where('v.summary LIKE ?', $cleanSummary)
            ->andWhere('SHA1(v.description) = ?', sha1($cleanDescription))
            ->andWhere('v.assetId = ?', $finding->assetId)
            ->andWhere('v.deleted_at is NULL')
            ->execute();

        return $duplicateFindings->count() > 0 ? $duplicateFindings->getFirst() : FALSE;
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
            $assetQuery->andWhere('a.addressIp IS NULL')
                       ->andWhere('a.name = ?', $assetData['name']);
        } else {
            $assetQuery->andWhere('a.addressIp = ?', $assetData['addressIp']);
        }
        $assetRecord = $assetQuery->execute()->getFirst();

        // If asset exists, verify whether service exists
        if ($assetRecord && isset($assetData['AssetServices'])) {
            $existingServices = $assetRecord->AssetServices->toKeyValueArray('addressPort', 'service');
            foreach ($assetData['AssetServices'] as $service) {
                if (!array_key_exists($service['addressPort'], $existingServices)) {
                    $assetService = new AssetService();
                    $assetService->merge($service);
                    $assetRecord->AssetServices[] = $assetService;
                }
            }
            $assetRecord->save();
        }

        return ($assetRecord) ? $assetRecord->id : null;
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

        foreach ($assetData as $key => $value) {
            if (!$value) {
                unset($assetData[$key]);
            }
        }

        $asset->merge($assetData);
        $asset->save();

        $id = $asset->id;

        // Check to see if any of the pending assets are duplicates, if so, update the finding to point to the correct
        // asset id
        foreach ($this->_findings as &$findingData) {
            if (empty($findingData['finding']->Asset)) {
                $ip1 = empty($findingData['asset']['addressIp']) ? '' : $findingData['asset']['addressIp'];
                $ip2 = empty($asset->addressIp) ? '' : $asset->addressIp;
                if ($findingData['asset']['networkId'] === $asset->networkId &&  $ip1 === $ip2) {
                    $findingData['asset']['id'] = $id;
                }
                unset($ip1, $ip2);
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

    /**
     * Return array of messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Add a new message
     *
     * @param string $err
     * @return void
     */
    protected function _setMessage($msg)
    {
        $this->_messages[] = $msg;
    }
}
