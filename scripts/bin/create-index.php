#!/usr/bin/env php
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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Script_Bin
 */

/**
 * Create lucene index, include findings, sources, networks, products, organizations, 
 *   roles, systems, accounts. Stored them in the directory of "data/index" 
 */

$createIndex = new CreateIndex();
$createIndex->process();

class CreateIndex
{
    /**
     * initialize the direcotry of the lucene index
     */
    private $_indexDir = null;
    
    //Set the lucene index dir
    public function __construct() 
    {
        require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::connectDb();
        
        $this->_indexDir = Fisma::getPath('index');
    }
        
    /**
     * Create index directory, if the index directory is exists, then read the index
     *
     * @param string $name the index name
     * @return Zend_Search_Lucene
     */
    private function _newIndex($name)
    {
        $indexPath = $this->_indexDir . "/$name";
        if (file_exists($indexPath)) {
            $index = new Zend_Search_Lucene($indexPath);
        } else {
            $index = new Zend_Search_Lucene($indexPath, true);
        }
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
        $indexPath = $this->_indexDir . "/$name";
        if (is_dir($indexPath)) {
            $index = new Zend_Search_Lucene($indexPath);
            $index->optimize();
            print("$name index optimized successfully ($indexPath).\n");
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create luence index document
     *
     * @param Doctrine_Connection $record the record which need to index
     * @return Zend_Search_Lucene_Docuemnt
     */
    private function _createDocument($indexName, $record)
    {
        $doc = new Zend_Search_Lucene_Document();
        
        $columnTypes = array();
        
        foreach ($record as $name => $value) {
            if (substr($name, -3) == '_id') {
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $value));
            } else {
                //index the string type fields
                if (!isset($columnTypes[$name])) {
                    $columnTypes[$name] = Fisma_Lucene::getColumnType($indexName, $name);;
                }
                $type = $columnTypes[$name];
                if ('string' ==  $type || 'enum' == $type) {
                    $storedValue = html_entity_decode(strip_tags($value));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored($name, $storedValue));
                }
            }
        }
        return $doc;
    }

    /**
     * Create lucene index
     *
     * @param string $name index name
     * @param Doctrine_Connection  $records  Doctrine Connections
     */
    private function _createIndex($name, $records)
    {
        if (empty($records)) {
            return false;
        }
        $indexPath = $this->_indexDir . "/$name";

        $index = $this->_newIndex($name);
        set_time_limit(0);
        $count = 0;
        $status = "$name: 0 rows";
        fwrite(STDOUT, $status);        
        $statusLength = strlen($status);
        foreach ($records as $record) {
            $doc   = $this->_createDocument($name, $record);
            $index->addDocument($doc);
            $count++;
            if (0 == $count % 100) {
                fwrite(STDOUT, str_repeat(chr(0x8), $statusLength));
                $status = "$name: $count rows";
                fwrite(STDOUT, "$status");
                $statusLength = strlen($status);
            }
            if (0 == $count % 1000) {
                $index->optimize();
            }
        }
        $index->optimize();
        chmod($indexPath, 0770);
        fwrite(STDOUT, str_repeat(chr(0x8), $statusLength));
        fwrite(STDOUT, "$name index created successfully ($count rows). \n");
    }
    
    /**
     * Create lucene index for each model
     */
    public function process()
    {
        print "This may take several minutes...\n";
        $start = time();
        
        $this->_createFinding();
        $this->_createSource();
        $this->_createNetwork();
        $this->_createProduct();
        $this->_createRole();
        $this->_createOrganization();
        $this->_createSystem();
        $this->_createAccount();
        $this->_createSystemDocument();
        
        $stop = time();
        $elapsed = $stop - $start;
        $minutes = floor($elapsed/60);
        $seconds = $elapsed - ($minutes * 60);
        
        print "Finished in $minutes minutes and $seconds seconds\n";
    }

    private function _createFinding()
    {
        if ($this->_optimize('finding')) {
            return false;
        }

        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('Finding')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $findings = $query->execute();
        $this->_createIndex('finding', $findings);
        $query->free();
    }

    private function _createSource()
    {
        if ($this->_optimize('source')) {
            return false;
        }
        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('Source')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $sources = $query->execute();
        $this->_createIndex('source', $sources);
        $query->free();
    }

    private function _createNetwork()
    {
        if ($this->_optimize('network')) {
            return false;
        }
        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('Network')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $networks = $query->execute();
        $this->_createIndex('network', $networks);
        $query->free();
    }

    private function _createProduct()
    {
        if ($this->_optimize('product')) {
            return false;
        }
        
        $query  = Doctrine_Query::create()
                    ->select('*')
                    ->from('Product')
                    ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $products = $query->execute();
        $this->_createIndex('product', $products);
        $query->free();
    }

    private function _createRole()
    {
        if ($this->_optimize('role')) {
            return false;
        }
        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('Role')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $roles = $query->execute();
        $this->_createIndex('role', $roles);
        $query->free();
    }

    private function _createOrganization()
    {
        if ($this->_optimize('organization')) {
            return false;
        }
        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('Organization')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $organizations = $query->execute();
        $this->_createIndex('organization', $organizations);
        $query->free();
    }

    private function _createSystem()
    {
        if ($this->_optimize('system')) {
            return false;
        }

        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('System')
                        ->innerJoin('System.Organization')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $systems = $query->execute();
        $this->_createIndex('system', $systems);
        $query->free();
    }

    private function _createAccount()
    {
        if ($this->_optimize('user')) {
            return false;
        }
        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('User')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $users = $query->execute();
        $this->_createIndex('user', $users);
        $query->free();
    }

    private function _createSystemDocument()
    {
        if ($this->_optimize('systemdocument')) {
            return false;
        }
        $query = Doctrine_Query::create()
                        ->select('*')
                        ->from('SystemDocument')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $documents = $query->execute();
        $this->_createIndex('systemdocument', $documents);
        $query->free();
    }
}
