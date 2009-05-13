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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: Notify.php 1633 2009-05-11 03:08:33Z woody712 $
 * @package    Cron_Job
 */

/**
 * Create lucene index, include findings, sources, networks, products, organizations, 
 *   roles, systems, accounts. Stored them in the directory of "data/index" 
 */
define('COMMAND_LINE', true);

require_once('../../application/init.php');
$plSetting = new Fisma_Controller_Plugin_Setting(RootPath::getRootPath());
$front = Fisma_Controller_Front::getInstance();
$front->registerPlugin($plSetting, 60); //this should be the highest priority
$pl = new Fisma_Controller_Plugin_Web();
$front->registerPlugin($pl);


// Kick off the main routine:
CreateIndex::process();

Class CreateIndex
{
    /**
     * the direcotry of the lucene index
     */
    const INDEX_DIR = '/data/index/';

    /**
     * Judge the index if is exist
     *
     * @param string $index index name
     * @return bool
     */
    static function isExist($index)
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . $index)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get db instance
     * @return Zend_Db
     */
    static function getDb()
    {
        $db = Zend_Db::factory(Zend_Registry::get('datasource'));
        return $db;
    }

    /**
     * Create lucene index
     */
    public function process()
    {
        self::createFinding();
        self::createSource();
        self::createNetwork();
        self::createProduct();
        self::createRole();
        self::createOrganization();
        self::createSystem();
        self::createAccount();
    }

    public function createFinding()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'finding')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'finding');
            $index->optimize();
            /** @todo english */
            print("Findings index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'finding', true);
        $query = $db->select()->from('poams', array('count'=>'count(*)'));
        $ret   = $db->fetchRow($query);
        $count = $ret['count'];

        $query = $db->select()->from(array('p'=>'poams'), 'p.*')
                              ->join(array('as'=>'assets'), 'p.asset_id = as.id', array())
                              ->join(array('s'=>'sources'), 'p.source_id = s.id', array())
                              ->join(array('sys'=>'systems'), 'p.system_id = sys.id', array())
                              ->where('p.status != "DELETED"');
        $offset = 100;
        for ($limit=0;$limit<=$count;$limit+=$offset) {
            $query->limit($offset, $limit);
            $list = $db->fetchAll($query);
            set_time_limit(0);
            if (!empty($list)) {
                foreach ($list as $row) {
                    $doc = new Zend_Search_Lucene_Document();
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                    $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('finding_data', $row['finding_data']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('action_planned', $row['action_planned']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('action_suggested',
                                $row['action_suggested']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('action_resources',
                                $row['action_resources']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('cmeasure', $row['cmeasure']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('cmeasure_justification',
                                $row['cmeasure_justification']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('threat_source', $row['threat_source']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('threat_justification',
                                $row['threat_justification']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('system',
                                $row['system_name'] . ' ' . $row['system_nickname']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('source',
                                $row['source_name'] . ' ' . $row['source_nickname']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('asset', $row['asset_name']));
                    $index->addDocument($doc);
                }
            }
        }
        $index->optimize();
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'finding', 0777);
        /** @todo english */
        print("Findings index created successfully. \n");
    }

    public function createSource()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'source')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'source');
            $index->optimize();
            /** @todo english */
            print("Sources index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'source', true);
        $query = $db->select()->from('sources', array('id', 'name', 'nickname', 'desc'));
        $list  = $db->fetchAll($query);
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('nickname', $row['nickname']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'source', 0777);
        /** @todo english */
        print("Sources index created successfully. \n");
    }

    public function createNetwork()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'network')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'network');
            $index->optimize();
            /** @todo english */
            print("Networks index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'network', true);
        $query = $db->select()->from('networks', array('id', 'name', 'nickname', 'desc'));
        $list  = $db->fetchAll($query);
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('nickname', $row['nickname']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'network', 0777);
        /** @todo english */
        print("Networks index created successfully. \n");
    }

    public function createProduct()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'product')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'product');
            $index->optimize();
            /** @todo english */
            print("Products index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'product', true);
        $query = $db->select()->from('products', array('count'=>'count(*)'));
        $ret   = $db->fetchRow($query);
        $count = $ret['count'];

        $query = $db->select()->from('products',array('id', 'vendor', 'name', 'version', 'desc'));
        $offset = 100;
        for ($limit=0;$limit<=$count;$limit+=$offset) {
            $query->limit($offset, $limit);
            $list  = $db->fetchAll($query);
            set_time_limit(0);
            if (!empty($list)) {
                foreach ($list as $row) {
                    $doc = new Zend_Search_Lucene_Document();
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                    $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('vendor', $row['vendor']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('version', $row['version']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                    $index->addDocument($doc);
                }
            }
        }
        $index->optimize();
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'product', 0777);
        /** @todo english */
        print("Products index created successfully. \n");
    }

    public function createRole()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'role')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'role');
            $index->optimize();
            /** @todo english */
            print("Roles index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'role', true);
        $query = $db->select()->from('roles', array('id', 'name', 'nickname', 'desc'));
        $list  = $db->fetchAll($query);
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('nickname', $row['nickname']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'role', 0777);
        /** @todo english */
        print("Roles index created successfully. \n");
    }

    public function createOrganization()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'organization')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'organization');
            $index->optimize();
            /** @todo english */
            print("Organizations index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'organization', true);
        $query = $db->select()->from('organizations', array('id', 'name', 'nickname'));
        $list  = $db->fetchAll($query);
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('nickname', $row['nickname']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'organization', 0777);
        /** @todo english */
        print("Organizations index created successfully. \n");
    }

    public function createSystem()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'system')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'system');
            $index->optimize();
            /** @todo english */
            print("Systems index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'system', true);
        $query = $db->select()->from(array('s'=>'systems'), 's.*')
                              ->join(array('o'=>'organizations'), 's.organization_id = o.id',
                                     array('org_name'=>'o.name', 'org_nickname'=>'o.nickname'));
        $list  = $db->fetchAll($query);
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('nickname', $row['nickname']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('organization',
                            $row['org_name'] . ' ' . $row['org_nickname']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('type', $row['type']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('confidentiality', $row['confidentiality']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('integrity', $row['integrity']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('availability', $row['availability']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('confidentiality_justification',
                            $row['confidentiality_justification']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('integrity_justification',
                            $row['integrity_justification']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('availability_justification',
                            $row['availability_justification']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'system', 0777);
        /** @todo english */
        print("Systems index created successfully. \n");
    }

    public function createAccount()
    {
        if (is_dir(RootPath::getRootPath() . self::INDEX_DIR . 'account')) {
            $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'account');
            $index->optimize();
            /** @todo english */
            print("Accounts index optimize successfully. \n");
            return false;
        }
        $db = self::getDb();
        $index = new Zend_Search_Lucene(RootPath::getRootPath() . self::INDEX_DIR . 'account', true);
        $query = $db->select()->from(array('u'=>'users'),
                                   array('u.id', 'u.account', 'u.name_last', 'u.name_first','u.email'))
                              ->join(array('ur'=>'user_roles'), 'u.id = ur.user_id', array())
                              ->join(array('r'=>'roles'), 'ur.role_id = r.id',
                                   array('role_name'=>'r.name', 'role_nickname'=>'r.nickname'));
        $list = $db->fetchAll($query);
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['account']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('lastname', $row['name_last']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('firstname', $row['name_first']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('email', $row['email']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('role',
                            $row['role_name'] . ' ' . $row['role_nickname']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
        chmod(RootPath::getRootPath() . self::INDEX_DIR . 'account', 0777);
        /** @todo english */
        print("Accounts index created successfully. \n");
    }
}
