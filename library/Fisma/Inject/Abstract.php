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
 * @package   Inject
 *
 */

/**
 * An abstract class for creating injection plug-ins
 *
 * @package   Inject
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
abstract class Fisma_Inject_Abstract
{
    protected $_file;
    protected $_networkId;
    protected $_orgSystemId;
    protected $_findingSourceId;
    
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
    }

    /**
     * _commit() - Conditionally commit the specific finding.
     *
     * The finding is evaluated with respect to the Injection Filtering rules. The finding may be committed or it may be
     * deleted based on the filter rules.
     *
     * Subclasses must call this function to commit findings rather than committing new findings directly.
     *
     * @param array $finding Column data for the new finding object
     * action was taken.
     */
    protected function _commit($finding) {
        $findingTable = new Finding();
        
        // See if any existing finding contains the same finding description as this finding.
        $result = $findingTable->getTable('Finding')->findOneByDescription($finding['description']);

        // Decide what action to take with this finding: 1) create 2) delete 3) review
        $action = null;
        if ($result) {
            // Assign the duplicate id to the current finding
            $finding['duplicateFindingId'] = $result->id;
            
            // If a duplicate exists, then run the Injection Filtering rules
            $types = $findingTable->getTable('Finding')->getEnumValues('type');
            if (in_array($result->type, $types)) {
                if ($result->systemId == $finding['systemId']) {
                    if ($result->status == 'CLOSED') {
                        $action = self::CREATE_FINDING;
                    } else {
                        $action = self::DELETE_FINDING;
                    }
                } else {
                    $action = self::REVIEW_FINDING;
                }
            } elseif ($result->type == 'AR') {
                if ($result->systemId == $finding['systemId']) {
                    $action = self::DELETE_FINDING;
                } else {
                    $action = self::REVIEW_FINDING;
                }
            } else {
                throw new Fisma_Exception("Unknown mitigation type: \"{$result->type}\"");
            }
        } else {
            // If there is no duplicate, then the default action is to create a new finding
            $action = self::CREATE_FINDING;
        }

        $findingTable->merge($finding);
        // Now take action on the current finding
        switch ($action) {
            case self::CREATE_FINDING:
                $finding['status'] = 'NEW';
                $findingTable->save();
                $this->_findingIds[] = $findingTable->id;
                $this->_totalFindings['created']++;
                break;

            case self::DELETE_FINDING:
                $this->_totalFindings['deleted']++;
                break;

            case self::REVIEW_FINDING:
                $finding['status'] = 'PEND';
                $findingTable->save();
                $this->_totalFindings['reviewed']++;
                break;

            default:
                throw new Fisma_Exception("\$action is not valid: \"$action\"");
                break;
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
}
