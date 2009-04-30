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
 * @version   $Id: Excel.php 1523 2009-03-26 17:01:44Z mehaase $
 */

/**
 * This class injects findings from a system-generated Excel template. It is not a true injection plug-in since it does
 * not subclass Inject_Abstract, but it is placed in the same package because it serves a similar function.
 *
 * This plug-in makes heavy use of the SimpleXML xpath() function, which makes code easier to maintain, but could also
 * be a performance bottleneck for large spreadsheets. Currently there has not been any load-testing for this plugin.
 *
 * @package   Inject
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License\
 */
class Fisma_Inject_Excel
{
    /**
     * The name of the template file which gets sent to the client
     */
    const TEMPLATE_NAME = 'Finding_Upload_Template.xls';

    /**
     * The template version is used to make sure that we don't try to process a template which was produced by a
     * previous version of OpenFISMA. This number should be incremented whenever the template file or processing code
     * is modified.
     */
    const TEMPLATE_VERSION = 1;
                  
    /**
     * Maps numerical indexes corresponding to column numbers in the excel upload template onto those
     * column's logical names. Excel starts indexes at 1 instead of 0.
     *
     * @todo Move this definition and related items into a separate classs... this is too much stuff to put into the
     * controller
     */
    private $_excelTemplateColumns = array(
        1 => 'system_nickname',
        'date_discovered',
        'network',
        'asset_name',
        'asset_ip',
        'asset_port',
        'product_name',
        'product_vendor',
        'product_version',
        'finding_source',
        'finding_description',
        'finding_recommendation',
        'course_of_action_type',
        'course_of_action_description',
        'expected_completion_date',
        'security_control',
        'threat_level',
        'threat_description',
        'countermeasure_effectiveness',
        'countermeasure_description',
        'contact_info'
    );

    /**
     * Indicates which columns are required in the excel template. Human readable names are included so that meaningful
     * error messages can be provided for missing columns.
     */
    private $_requiredExcelTemplateColumns = array (
        'system_nickname' => 'System',
        'date_discovered' => 'Date Discovered',
        'finding_source' => 'Finding Source',
        'finding_description' => 'Finding Description',
        'finding_recommendation' => 'Finding Recommendation'
    );

    /**
     * The row to start on in the excel template. The template has 3 header rows, so start at the 4th row.
     */
    private $_excelTemplateStartRow = 4;

	/**
	 * insert finding ids
	 */
	public $_findingIds = array();
    
    /**
     * Parses and loads the findings in the specified excel file. Expects XML spreadsheet format from Excel 2007.
     * Compatible with older versions of Excel through the Office Compatibility Pack.
     *
     * @param string $filePath
     * @return int The number of findings processed in the file
     */
    function inject($filePath, $uploadId) {
        // Parse the file using SimpleXML. The finding data is located on the first worksheet.
        $spreadsheet = @simplexml_load_file($filePath);
        if ($spreadsheet === false) {
            throw new Exception_InvalidFileFormat(
                "The file is not a valid Excel spreadsheet. Make sure that the file is saved as an XML spreadsheet."
            );
        }

        // Check that the template version matches the version of OpenFISMA which is running.
        $templateVersion = (int)$spreadsheet->CustomDocumentProperties->FismaTemplateVersion;
        if ($templateVersion != self::TEMPLATE_VERSION) {
            throw new Exception_InvalidFileFormat(
                "This template was created by a previous version of OpenFISMA and is not compatible with the current"
                . " version. Download a new copy of the template and transfer your data into it."
            );
        }
                
        // Have to do some namespace manipulation to make the spreadsheet searchable by xpath.
        $namespaces = $spreadsheet->getNamespaces(true);
        $spreadsheet->registerXPathNamespace('s', $namespaces['']);
        $findingData = $spreadsheet->xpath('/s:Workbook/s:Worksheet[1]/s:Table/s:Row');
        if ($findingData === false) {
            throw new Exception_InvalidFileFormat(
                "The file format is not recognized. Your version of Excel might be incompatible."
            );
        }
        
        // $findingData is an array of rows in the first worksheet. The first three rows on this worksheet contain
        // headers, so skip them.
        array_shift($findingData);
        array_shift($findingData);
        array_shift($findingData);
        
        // Now process each remaining row
        /**
         * @todo Perform these commits in a single transaction.
         */
        $rowNumber = $this->_excelTemplateStartRow;
        foreach ($findingData as $row) {
            // Copy the row data into a local array
            $finding = array();
            $column = 1;
            foreach ($row as $cell) {
                // If Excel skips a cell that has no data, then the next cell that has data will contain an
                // 'ss:Index' attribute to indicate which column it is in.
                $cellAttributes = $cell->attributes('ss',true);
                if (isset($cellAttributes['Index'])) {
                    $column = (int)$cellAttributes['Index'];
                }
                $cellChildren = $cell->children('urn:schemas-microsoft-com:office:spreadsheet');
                $finding[$this->_excelTemplateColumns[$column]] = $cellChildren->Data->asXml();
                $column++;
            }
            /**
             * @todo improved input sanitzation. use a better html filter than strip_tags and attempt to preserve the
             * formatting of the text. this filtering should be done in the model classes.
             */                                      
            // Basic input sanitzation: remove HTML tags and then encode any remaining characters
            $finding = array_map('strip_tags', $finding);
            $finding = array_map('htmlspecialchars', $finding);

            // Validate that required row attributes are filled in:
            foreach ($this->_requiredExcelTemplateColumns as $columnName => $columnDescription) {
                if (empty($finding[$columnName])) {
                    throw new Fisma_Exception_InvalidFileFormat("Row $rowNumber: Required column \"$columnDescription\"
                                                          is empty");
                }
            }

            // Map the row data into logical objects. Notice suppression is used heavily here to keep the code
            // from turning into spaghetti. When debugging this code, it will probably be helpful to remove these
            // suppressions.
            $poam = array();
            $systemTable = new System();
            $poam['upload_id'] = $uploadId;
            $poam['system_id'] = @$systemTable->fetchRow("nickname = '{$finding['system_nickname']}'")->id;
            if (empty($poam['system_id'])) {
                throw new Fisma_Exception_InvalidFileFormat("Row $rowNumber: Invalid system selected. Your template may
                                                      be out of date. Please try downloading it again.");
            }
            $poam['discover_ts'] = $finding['date_discovered'];
            $sourceTable = new Source();
            $poam['source_id'] = @$sourceTable->fetchRow("nickname = '{$finding['finding_source']}'")->id;
            if (empty($poam['source_id'])) {
                throw new Fisma_Exception_InvalidFileFormat("Row $rowNumber: Invalid finding source selected. Your
                                                      template may
                                                      be out of date. Please try downloading it again.");
            }
            $poam['finding_data'] = $finding['finding_description'];
            if (!empty($finding['contact_info'])) {
                $poam['finding_data'] .= "<br>Point of Contact: {$finding['contact_info']}";
            }
            $poam['action_suggested'] = $finding['finding_recommendation'];
            $poam['type'] = @$finding['course_of_action_type'];
            if (empty($poam['type'])) {
                $poam['type'] = 'NONE';
            }
            $poam['action_planned'] = @$finding['course_of_action_description'];
            $poam['action_current_date'] = @$finding['expected_completion_date'];
            $poam['blscr_id'] = @$finding['security_control'];
            $poam['threat_level'] = @$finding['threat_level'];
            if (empty($poam['threat_level'])) {
                $poam['threat_level'] = 'NONE';
            }
            $poam['threat_source'] = @$finding['threat_description'];
            $poam['cmeasure_effectiveness'] = @$finding['countermeasure_effectiveness'];
            if (empty($poam['cmeasure_effectiveness'])) {
                $poam['cmeasure_effectiveness'] = 'NONE';
            }
            $poam['cmeasure'] = @$finding['countermeasure_description'];
            $poam['create_ts'] = new Zend_Db_Expr('NOW()');
            $poam['action_resources'] = 'None';

            $asset = array();
            $networkTable = new Network();
            $asset['network_id'] = @$networkTable->fetchRow("nickname = '{$finding['network']}'")->id;
            $asset['address_ip'] = @$finding['asset_ip'];
            $asset['address_port'] = @$finding['asset_port'];
            if (!empty($asset['address_port']) && !is_numeric($asset['address_port'])) {
                throw new Exception_InvalidFileFormat("Row $rowNumber: The port number is not numeric.");
            }

            $asset['name'] = @$finding['asset_name'];
            if (empty($asset['name'])) {
                $asset['name'] = "{$asset['address_ip']}:{$asset['address_port']}";
            }
            $asset['create_ts'] = new Zend_Db_Expr('NOW()');
            $asset['system_id'] = $poam['system_id'];

            $product = array();
            $product['name'] = @$finding['product_name'];
            $product['vendor'] = @$finding['product_vendor'];
            $product['version'] = @$finding['product_version'];
            
            // Now persist these objects. Check assets and products to see whether they exist before creating new
            // ones.
            if (!empty($product['name']) && !empty($product['vendor']) && !empty($product['version'])) {
                /** @todo this isn't a very efficient way to lookup products, but there might be no good alternative */
                $productTable = new Product();
                $query = $productTable->select()->from($productTable, 'id')
                                      ->where("name like ?", "%$product[name]%")
                                      ->where("vendor like ?", "%$product[vendor]%")
                                      ->where("version like ?", "%$product[version]%");
                $productId = @$productTable->fetchRow($query)->id;
                if (empty($productId) && !empty($product['name'])) {
                    $productId = @$productTable->insert($product);
                }
            }

            // Persist the asset, if necessary
            if (!empty($asset['network_id']) && !empty($asset['address_ip']) && !empty($asset['address_port'])) {
                $asset['prod_id'] = @$productId;
                $assetTable = new Asset();
                $query = $assetTable->select()->from($assetTable, 'id')
                                    ->where("network_id = ?", $asset['network_id'])
                                    ->where("address_port = ?", $asset['address_port'])
                                    ->where("address_ip like ?", "%$asset[address_ip]%");
                $assetId = @$assetTable->fetchRow($query)->id;
                if (empty($assetId)) {
                    $assetId = $assetTable->insert($asset);
                }
            }

            // Finally, create the finding
            $poam['asset_id'] = @$assetId;
            $poamTable = new Poam();
            $this->_findingIds[] = $poamTable->insert($poam);

            $rowNumber++;
        }
        
        return $rowNumber - $this->_excelTemplateStartRow;
    }
}

