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
 * This class injects findings from a system-generated Excel template. It is not a true injection plug-in since it does
 * not subclass Inject_Abstract, but it is placed in the same package because it serves a similar function.
 *
 * This plug-in makes heavy use of the SimpleXML xpath() function, which makes code easier to maintain, but could also
 * be a performance bottleneck for large spreadsheets. Currently there has not been any load-testing for this plugin.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 * @version    $Id$
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
     * 
     * Version history:
     * v1 2009-04-30 Introduce versioning of excel template
     * v2 2010-06-28 Add metadata regarding which security control catalog was used to produce the template
     * v3 2011-02-02 Removed asset related fields
     * v4 2011-03-25 threatLevel and threatDescription are now requiered fields
     */
    const TEMPLATE_VERSION = 4;
    
    /**
     * Maps numerical indexes corresponding to column numbers in the excel upload template onto those
     * column's logical names. Excel starts indexes at 1 instead of 0.
     * 
     * @var array
     * @todo Move this definition and related items into a separate classs... this is too much stuff to put into the
     * controller
     */
    private $_excelTemplateColumns = array(
        1 => 'systemNickname',
        'discoveredDate',
        'findingSource',
        'findingDescription',
        'findingRecommendation',
        'findingType',
        'findingMitigationStrategy',
        'ecdDate',
        'securityControl',
        'threatLevel',
        'threatDescription',
        'countermeasuresEffectiveness',
        'countermeasureDescription',
        'contactInfo'
    );

    /**
     * Indicates which columns are required in the excel template. Human readable names are included so that meaningful
     * error messages can be provided for missing columns.
     * 
     * @var array
     */
    private $_requiredExcelTemplateColumns = array (
        'systemNickname' => 'System',
        'discoveredDate' => 'Date Discovered',
        'findingSource' => 'Finding Source',
        'findingDescription' => 'Finding Description',
        'findingRecommendation' => 'Finding Recommendation',
        'threatLevel' => 'Threat Level',
        'threatDescription' => 'Threat Description'
    );

    /**
     * The row to start on in the excel template. The template has 3 header rows, so start at the 4th row.
     * 
     * @var int
     */
    private $_excelTemplateStartRow = 4;
    
    /**
     * Parses and loads the findings in the specified excel file. Expects XML spreadsheet format from Excel 2007.
     * Compatible with older versions of Excel through the Office Compatibility Pack.
     * 
     * @param string $filePath The specified excel file path
     * @param string $uploadId The id of upload excel
     * @return int The number of findings processed in the file
     * @throws Fisma_Zend_Exception_InvalidFileFormat if the file is not a valid Excel spreadsheet, 
     * or the excel template is out-of-date or imcompatible, 
     * or the some required or used columns are empty or invalid
     */
    function inject($filePath, $uploadId) 
    {
        // Parse the file using SimpleXML. The finding data is located on the first worksheet.
        $spreadsheet = @simplexml_load_file($filePath);
        if ($spreadsheet === false) {
            throw new Fisma_Zend_Exception_InvalidFileFormat(
                "The file is not a valid Excel spreadsheet. Make sure that the file is saved as an XML spreadsheet."
            );
        }
        
        // Check that the template version matches the version of OpenFISMA which is running.
        $templateVersion = (int)$spreadsheet->CustomDocumentProperties->FismaTemplateVersion;
        if ($templateVersion != self::TEMPLATE_VERSION) {
            throw new Fisma_Zend_Exception_InvalidFileFormat(
                "This template was created by a previous version of OpenFISMA and is not compatible with the current"
                . " version. Download a new copy of the template and transfer your data into it."
            );
        }
        
        // Look up the control catalog ID for this template in the spreadsheet properties. This is used later.
        $securityControlCatalogId = (int)$spreadsheet->CustomDocumentProperties->SecurityControlCatalogId;

        // Have to do some namespace manipulation to make the spreadsheet searchable by xpath.
        $namespaces = $spreadsheet->getNamespaces(true);
        $spreadsheet->registerXPathNamespace('s', $namespaces['']);
        $findingData = $spreadsheet->xpath('/s:Workbook/s:Worksheet[1]/s:Table/s:Row');
        if ($findingData === false) {
            throw new Fisma_Zend_Exception_InvalidFileFormat(
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
                $cellAttributes = $cell->attributes('ss', true);
                if (isset($cellAttributes['Index'])) {
                    $column = (int)$cellAttributes['Index'];
                }
                $cellChildren = $cell->children('urn:schemas-microsoft-com:office:spreadsheet');
                $finding[$this->_excelTemplateColumns[$column]] = $cellChildren->Data->asXml();
                $column++;
            }
            
            /**
             * @todo i realized that simplexml can not handle mixed content (an xml text node that also
             * contains xml tags)... so this whole thing needs to be re-written in DOM or some other API
             * that CAN read mixed content. until then -- formatting in excel is not preserved -- all
             * tags are stripped out and remaining special chars are encoded.
             */                
            $finding = array_map('strip_tags', $finding);
            $finding = array_map('html_entity_decode', $finding);
            
            // Validate that required row attributes are filled in:
            foreach ($this->_requiredExcelTemplateColumns as $columnName => $columnDescription) {
                if (empty($finding[$columnName])) {
                    throw new Fisma_Zend_Exception_InvalidFileFormat(
                        "Row $rowNumber: Required column \"$columnDescription\" is empty"
                    );
                }
            }
            
            // Map the row data into logical objects. Notice suppression is used heavily here to keep the code
            // from turning into spaghetti. When debugging this code, it will probably be helpful to remove these
            // suppressions.
            $poam = array();
            $poam['uploadId'] = $uploadId;
            $organization = Doctrine::getTable('Organization')->findOneByNickname($finding['systemNickname']);
            if (!$organization) {
                throw new Fisma_Zend_Exception_InvalidFileFormat(
                    "Row $rowNumber: Invalid system selected. Your template may be out of date. Please try downloading 
                    it again."
                );
            }
            $poam['responsibleOrganizationId'] = $organization->id;
            
            $sourceTable = Doctrine::getTable('Source')->findOneByNickname($finding['findingSource']);
            if (!$sourceTable) {
                throw new Fisma_Zend_Exception_InvalidFileFormat("Row $rowNumber: Invalid finding source selected. Your
                                                      template may
                                                      be out of date. Please try downloading it again.");
            }
            $poam['sourceId'] = $sourceTable->id;
                        
            // Match controls by code (e.g. "AC-01") and security control catalog ID
            if (!empty($finding['securityControl'])) {
                $securityControlTable = Doctrine::getTable('SecurityControl');
                
                $conditions = 'code = ? and securityControlCatalogId = ?';
                $parameters = array($finding['securityControl'], $securityControlCatalogId);

                $securityControls = $securityControlTable->findByDql($conditions, $parameters);

                if (count($securityControls) != 1) {
                    $error = "Row $rowNumber: Invalid security control selected. Your template may be out of date."
                           . 'Please try downloading it again.';
                    throw new Fisma_Zend_Exception_InvalidFileFormat($error);
                }
                $poam['securityControlId'] = $securityControls[0]->id;
            } else {
                $poam['securityControlId'] = null;
            }

            $poam['description'] = "<p>{$finding['findingDescription']}</p>";
            if (!empty($finding['contactInfo'])) {
                $poam['description'] .= "<p>Point of Contact: {$finding['contactInfo']}</p>";
            }
            $poam['recommendation'] = $finding['findingRecommendation'];
            if (empty($finding['findingType'])) {
                $poam['type'] = 'NONE';
            } else {
                $poam['type'] = $finding['findingType'];
            }
            if (!empty($finding['findingMitigationStrategy'])) {
                $poam['mitigationStrategy'] = $finding['findingMitigationStrategy'];
            }
            if (!empty($finding['ecdDate'])) {
                $poam['currentEcd'] = $finding['ecdDate'];
            }
            $poam['ecdLocked'] = 0;
            $poam['discoveredDate'] = $finding['discoveredDate'];
            if (empty($finding['threatLevel'])) {
                $poam['threatLevel'] = 'NONE';
            } else {
                $poam['threatLevel'] = $finding['threatLevel'];
            }
            if (!empty($finding['threatDescription'])) {
                $poam['threat'] = $finding['threatDescription'];
            }
            if (!empty($finding['countermeasuresEffectiveness'])) {
                $poam['countermeasuresEffectiveness'] = $finding['countermeasuresEffectiveness'];
            }
            if (!empty($finding['countermeasureDescription'])) {
                $poam['countermeasures'] = $finding['countermeasureDescription'];
            }
            $poam['resourcesRequired'] = 'None';
            
            // Finally, create the finding
            $findingRecord = new Finding();
            $findingRecord->merge($poam);
            $findingRecord->CreatedBy = CurrentUser::getInstance();
            $findingRecord->save();
            $rowNumber++;
        }
        return $rowNumber - $this->_excelTemplateStartRow;
    }
}

