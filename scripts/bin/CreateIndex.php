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
 * @author    Ryan yang <ryan.yang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Script_Bin
 */

/**
 * Create lucene index, include findings, sources, networks, products, organizations, 
 *   roles, systems, accounts. Stored them in the directory of "data/index" 
 */
define('COMMAND_LINE', true);

require_once('../../application/init.php');
$plSetting = new Fisma_Controller_Plugin_Setting();

if ($plSetting->installed()) {
    // Kick off the main routine:
    $createIndex = new CreateIndex();
    $createIndex->process();
} else {
    die('This script cannot run because OpenFISMA has not been configured yet. Run the installer and try again.');
}

Class CreateIndex
{
    /**
     * initialize the direcotry of the lucene index
     */
    private $_indexDir = null;
    
    //Set the lucene index dir
    public function __construct() 
    {
        $this->_indexDir = Fisma_Controller_Front::getPath('data') . '/index/';
    }
        
    /**
     * Create index directory
     *
     * @param string $name the index name
     * @return Zend_Search_Lucene
     */
    private function _newIndex($name)
    {
        $index = new Zend_Search_Lucene($this->_indexDir . $name, true);
        return $index;
    }
    
    /**
     * Optimize the index if the index is exist
     *
     * @param string $name index name
     * @return bool if the index exists then optimize it and return true, else return false
     */
    private function _optimize($name)
    {
        if (is_dir($this->_indexDir . $name)) {
            $index = new Zend_Search_Lucene($this->_indexDir . $name);
            $index->optimize();
            /** @todo english */
            print("$name index optimize successfully. \n");
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create luence index document
     *
     * @param array $data the data which need to index
     * @return Zend_Search_Lucene_Docuemnt
     */
    private function _createDocument($data)
    {
        if (!is_array($data)) {
            throw new Fisma_Exception_General("Invalid data");
        }
        $doc = new Zend_Search_Lucene_Document();
        foreach ($data as $key=>$value) {
            if ('id' == $key) {
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($value)));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $value));
            } else {
                $doc->addField(Zend_Search_Lucene_Field::UnStored($key, $value));
            }
        }
        return $doc;
    }

    /**
     * Create lucene index
     *
     * @param string $name index name
     * @param array  $data index data
     */
    private function _createIndex($name, $data)
    {
        if (empty($data)) {
            return false;
        }
        $index = $this->_newIndex($name);
        set_time_limit(0);
        foreach ($data as $rowData) {
            $doc   = $this->_createDocument($rowData);
            $index->addDocument($doc);
        }
        $index->optimize();
        chmod($this->_indexDir . $name, 0777);
        /** @todo english */
        print("$name index created successfully. \n");

    }
    
    /**
     * Create lucene index for each model
     */
    public function process()
    {
        $this->_createFinding();
        $this->_createSource();
        $this->_createNetwork();
        $this->_createProduct();
        $this->_createRole();
        $this->_createOrganization();
        $this->_createSystem();
        $this->_createAccount();
    }

    private function _createFinding()
    {
        if ($this->_optimize('finding')) {
            return false;
        }

        $count = Doctrine::getTable('Finding')->count();
        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('Finding');
        $offset = 1;
        for ($limit=0;$limit<=$count;$limit+=$offset) {
            $findings = $query->limit($limit)
                              ->offset($offset)
                              ->execute();
            foreach ($findings as $finding) {
                $data[] = array(
                            'id' => $finding->id,
                            'description'        => $finding->description,
                            'recommendation'     => $finding->recommendation,
                            'mitigationstrategy' => $finding->mitigationStrategy,
                            'resourcesrequired'  => $finding->resourcesRequired,
                            'countermeasures'    => $finding->countermeasures,
                            'threat'             => $finding->threat,
                            'organization'       => $finding->ResponsibleOrganization->name . ',' .
                                                    $finding->ResponsibleOrganization->nickname,
                            'source'             => $finding->Source->name . $finding->Source->nickname,
                            'asset'              => $finding->Asset->name
                        );
            }
            $this->_createIndex('finding', $data);
        }
    }

    private function _createSource()
    {
        if ($this->_optimize('source')) {
            return false;
        }
        $sources = Doctrine::getTable('Source')->findAll();
        foreach ($sources as $source) {
            $data[] = array(
                        'id'           => $source->id,
                        'name'         => $source->name,
                        'nickname'     => $source->nickname,
                        'description'  => $source->description
                    );
        }
        $this->_createIndex('source', $data);
    }

    private function _createNetwork()
    {
        if ($this->_optimize('network')) {
            return false;
        }
        $networks = Doctrine::getTable('Network')->findAll();
        foreach ($networks as $network) {
            $data[] = array(
                        'id'           => $role->id,
                        'name'         => $role->name,
                        'nickName'     => $role->nickName,
                        'description'  => $role->description
                    );
        }
        $this->_createIndex('network', $data);
    }

    private function _createProduct()
    {
        if ($this->_optimize('product')) {
            return false;
        }
        $count  = Doctrine::getTable('Product')->count();
        $offset = 100;
        $query  = Doctrine_Query::Create()
                    ->select('*')
                    ->from('Product');
        for ($limit=0;$limit<=$count;$limit+=$offset) {
            $products = $query->limit($limit)
                              ->offset($offset)
                              ->execute();
            foreach ($products as $product) {
                $data[] = array(
                            'id'          => $role->id,
                            'name'        => $role->name,
                            'vendor'      => $role->vendor,
                            'version'     => $role->version,
                            'description' => $role->description
                            );
            }
            $this->_createIndex('product', $data);
        }
    }

    private function _createRole()
    {
        if ($this->_optimize('role')) {
            return false;
        }
        $roles = Doctrine::getTable('Role')->findAll();
        foreach ($roles as $role) {
            $data[] = array(
                        'id'           => $role->id,
                        'name'         => $role->name,
                        'nickname'     => $role->nickname,
                        'description'  => $role->description
                        );
        }
        $this->_createIndex('role', $data);
    }

    private function _createOrganization()
    {
        if ($this->_optimize('organization')) {
            return false;
        }
        $organizations = Doctrine::getTable('Organization')->findAll();
        foreach ($organizations as $organization) {
            $data[] = array(
                        'id'           => $organization->id,
                        'name'         => $organization->name,
                        'nickname'     => $organization->nickname,
                        'orgtype'      => $organization->orgType,
                        'description'  => $organization->description
                        );
        }
        $this->_createIndex('organization', $data);
    }

    private function _createSystem()
    {
        if ($this->_optimize('system')) {
            return false;
        }
        $systems = Doctrine::getTable('System')->findAll();
        foreach ($systems as $system) {
            $data[] = array(
                        'id'              => $system->id,
                        'name'            => $system->name,
                        'nickname'        => $system->nickname,
                        'type'            => $system->type,
                        'confidentiality' => $system->confidentiality,
                        'integrity'       => $system->integrity,
                        'availability'    => $system->availability,
                        'visibility'      => $system->visibility
                        );
        }
        $this->_createIndex('system', $data);
    }

    private function _createAccount()
    {
        if ($this->_optimize('account')) {
            return false;
        }
        $users = Doctrine::getTable('User')->findAll();
        foreach ($users as $user) {
            foreach ($user->Roles as $role) {
                $role[] = $role['name'] . $role['nickname'];
            }
            $data[] = array(
                        'id'        => $user->id,
                        'name'      => $user->username,
                        'namelast'  => $user->nameLast,
                        'namefirst' => $user->nameFirst,
                        'email'     => $user->email . ',' . $user->notifyEmail,
                        'role'      => empty($role) ? '' : implode(',', $role)
                    );
        }
        $this->_createIndex('account', $data);
    }
}
