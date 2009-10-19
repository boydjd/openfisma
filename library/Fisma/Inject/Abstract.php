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
        // disable the preInsert listener on Finding
        Doctrine::getTable('Finding')->getRecordListener()->get('FindingListener')->setOption('disabled', array('preInsert'));

        Doctrine_Manager::connection()->beginTransaction();
        $findingTable = new Finding();
        $findingTable->merge($finding);

        // set the necessary options on finding that aren't covered by the preInsert listener anymore
        $findingTable->status = 'NEW';
        $findingTable->createdBy = User::currentUser();
        $findingTable->updateNextDueDate();

        $findingTable->save();
        if ($findingTable->status == 'PEND') {
            $duplicate = Doctrine::getTable('Finding')->find($findingTable->duplicateFindingId);
            // If a duplicate exists, then run the Injection Filtering rules
            if ($duplicate->type == 'NONE' || $duplicate->type == 'CAP' || $duplicate->type == 'FP') {
                if ($findingTable->responsibleOrganizationId == $duplicate->responsibleOrganizationId) {
                    if ($duplicate->status == 'CLOSED') {
                        $this->_totalFindings['created']++;
                        Doctrine_Manager::connection()->commit();
                        $findingTable->log('New Finding Created');
                    } else {
                        $this->_totalFindings['deleted']++;
                        Doctrine_Manager::connection()->rollback();
                    }
                } else {
                    $this->_totalFindings['reviewed']++;
                    Doctrine_Manager::connection()->commit();
                    $findingTable->log('New Finding Created');
                }
            } elseif ($duplicate->type == 'AR') {
                if ($duplicate->responsibleOrganizationId == $findingTable->responsibleOrganizationId) {
                    $this->_totalFindings['deleted']++;
                    Doctrine_Manager::connection()->rollback();
                } else {
                    $this->_totalFindings['reviewed']++;
                    Doctrine_Manager::connection()->commit();
                    $findingTable->log('New Finding Created');
                }
            }
        } else {
            $this->_totalFindings['created']++;
            Doctrine_Manager::connection()->commit();
            $findingTable->log('New Finding Created');
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
