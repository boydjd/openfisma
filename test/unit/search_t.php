<?php 
/**
 * search_t.php
 *
 * Test Searching
 *
 * @package Test_Unit
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */

require_once MODELS . DS . 'system.php';
require_once MODELS . DS . 'source.php';
require_once MODELS . DS . 'network.php';
require_once MODELS . DS . 'poam.php';
/**
 * Test function search in poam model
 *
 * @package Test_Unit
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */

class TestRemediationOfSearch extends UnitTestCase{

    public $systems = null;
    function setUp()
    {
        $system = new system();
        $this->systems = $system->getList('name');
        $this->db = Zend_Registry::get('db');
        $this->system_ids = array_keys($this->systems);
        $this->poam = new poam($this->db);
    }

    function testSourceCount()
    {
        $db = $this->db;
        $query = $db->select()->from(array('p'=>'poams'),array('count'=>'count(p.id)','source_id'))
                              ->where('p.system_id IN ('.makeSqlInStmt($this->system_ids).')')
                              ->where('p.source_id != 0')
                              ->group('source_id')->order('count DESC');
        $ret = $db->fetchRow($query);
        $srcid = $ret['source_id'];

        $s1 = $this->poam->search($this->system_ids, array('count'=>'source_id','source_id'));
        foreach( $s1 as $s ){
            if($srcid == $s['source_id']){
                $this->assertTrue($ret == $s);
            }
        }
        $s2 = $this->poam->search($this->system_ids, array('count'=>'count(*)'),
                                  array('source_id'=>$srcid) );
        $this->assertTrue($ret['count'] == $s2);
    }

    function testSystemCount()
    {
        $db = $this->db;
        $system_ids = array_keys($this->systems);
        foreach( $system_ids as $id ) {
            $query = $db->select()->from(array('p'=>'poams'),array('num'=>'count(*)'))
                                  ->where("system_id = $id ");
            $result = $db->fetchRow($query);
            $count = $result['num'];

            $poam = new poam($db);
            $result = $poam->search(array($id),'count');
            $this->assertTrue($count == $result);
        }
    }
    
    function testJoinCount()
    {
        $fields = array('count'=>'ip','ip');
        $query = $this->db->select()->from(array('p'=>'poams'),array('count'=>'count(*)'))
                                    ->where('p.system_id IN ('.makeSqlInStmt($this->system_ids).')')
                                    ->group('a.address_ip')
                                    ->join(array('a'=>'assets'),'p.asset_id = a.id',array('ip'=>'a.address_ip'));
        $result = $this->db->fetchAll($query);
        $poams = $this->poam->search($this->system_ids,$fields);
        $this->assertTrue($result == $poams);


    }

    function testGroupCount()
    {
        $fields = array('count'=>'type','type');
        $query = $this->db->select()->from(array('p'=>'poams'),array('count'=>'count(*)','type'))
                                    ->where('p.system_id IN ('.makeSqlInStmt($this->system_ids).')')
                                    ->group('type');
        $result = $this->db->fetchAll($query);
        $poams = $this->poam->search($this->system_ids,$fields);

        $this->assertTrue($result == $poams);
    }
        
    public function testCountFields()
    {
        $fields = 'status';
        $poam = new poam();
        $query = $this->db->select()->from(array('p'=>'poams'),array('count'=>'count(status)'))
                          ->where('p.system_id IN ('.makeSqlInStmt($this->system_ids).')')
                          ->group('status');
        $result = $this->db->fetchAll($query);
        $poams = $poam->search($this->system_ids,array('count'=>$fields));
        $this->assertTrue($result == $poams);
    }

    public function testCriteria()
    {
        $criteria = array('source_id'=>2,'type'=>'CAP','status'=>'OPEN',
                          'est_date_begin'=>new Zend_Date('20070101'),
                          'est_date_end'=>new Zend_Date('20080601'),
                          'created_date_begin'=>new Zend_Date('20060101'),
                          'created_date_end'=>new Zend_Date('20090601'));
        $db = $this->db;
        $poam = new poam();
        extract($criteria);
        $ids = implode(',',$this->system_ids);
        $query = $db->select()->from(array('p'=>'poams'),array('num'=>'count(p.id)'))
                              ->where("p.system_id IN (". $ids .")")
                              ->where("p.source_id = $source_id")
                              ->where("p.type = '$type'")
                              ->where("p.status = '$status'")
                              ->where("p.action_est_date >?",$est_date_begin->toString('Ymd'))
                              ->where("p.action_est_date <=?",$est_date_end->toString('Ymd'))
                              ->where("p.create_ts >?",$created_date_begin->toString('Ymd'))
                              ->where("p.create_ts <=?",$created_date_end->toString('Ymd'));
        $result = $db->fetchRow($query);
        $count = $result['num'];
        $result = $poam->search($this->system_ids,'count',$criteria);
        $this->assertTrue($count == $result);
    }

    function testCountOnly()
    {
        $poam = new poam();
        $fields = array('count'=>'count(*)');
        $query = $this->db->select()->from(array('p'=>'poams'),$fields)
                                    ->where("p.system_id IN (". makeSqlInStmt($this->system_ids)." )");
        $result = $this->db->fetchOne($query);
        $poams = $poam->search($this->system_ids,$fields);
        $this->assertTrue($result == $poams);
    }

    function testCountWithFields()
    {
        $poam = new poam();
        $fields = array('type'=>'type','status'=>'status');
        $query = $this->db->select()->from(array('p'=>'poams'),$fields)
                                    ->where("p.system_id IN (". makeSqlInStmt($this->system_ids).")" );
        $result = $this->db->fetchAll($query);

        $fields = array('count'=>'count(*)','type'=>'type','status'=>'status');
        $poams = $poam->search($this->system_ids,$fields);
        $count2=array_pop($poams);
        $this->assertTrue(count($result) == $count2);
        $this->assertTrue($result == $poams);
    }
        
    public function testCountGroups()
    {
        $field = 'status';
        $poam = new poam();
        $query = $this->db->select()->from(array('p'=>'poams'),array('status','count'=>'count(status)'))->group('status');
        $result = $this->db->fetchAll($query);
        $poams = $poam->search($this->system_ids,array('status',
                                                       'count'=>"$field"));
        foreach($query as $row ){
            $this->assertTrue( $row['count'] == $poams);
        }
    
    }
}
