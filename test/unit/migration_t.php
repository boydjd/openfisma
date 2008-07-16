<?php
/**
* OpenFISMA
*
* MIT LICENSE
*
* @version $Id:$
*/

/**
* Test function check migrate function
*/
//require_once MODELS . DS . 'system.php';
class  TestFismaModel extends UnitTestCase{
        function setUp(){
            $db_target = Zend_DB::factory(Zend_Registry::get('datasource')->default);
            $db_src = Zend_DB::factory(Zend_Registry::get('legacy_datasource')->default);
            $this->db_target = $db_target;
            $this->db_src = $db_src;
        }
        
        function testPoamSysemId(){   
            $query = $this->db_target->select()->from(array('p'=>'poams'),array('poam_id'=>'id','sa_id'=>'system_id'));
            $result = $this->db_target->fetchAll($query);
            $query2= $this->db_src->select()->from(array('p'=>'POAMS'),array('poam_id'=>'poam_id','sa_id'=>'poam_action_owner'));
            $result2= $this->db_src->fetchAll($query2);
          /* $query3= $this->db_src->select()->from(array('f'=>'FINDINGS'),array('finding_id'=>'finding_id'))     
                                            ->joinLeft(array('a'=>'ASSETS'),'f.asset_id=a.asset_id',array())
                                            ->joinLeft(array('sa'=>'SYSTEM_ASSETS'),'a.asset_id=sa.asset_id',array('sa_id'=>'system_id'))
                                            ->where('finding_status=""');
            */                                
            $query3="SELECT `f`.`finding_id`,`sa`.`system_id` FROM `FINDINGS` as `f`, `SYSTEM_ASSETS` as `sa` WHERE f.asset_id=sa.asset_id and `finding_status` = 'OPEN'";

            $result3= $this->db_src->fetchAll($query3);
          
            foreach($result2 as $row){
                $this->assertTrue(in_array($row,$result));
            }

            $query = $this->db_target->select()->from(array('p'=>'poams'),array('finding_id'=>'legacy_finding_id','system_id'=>'system_id'));
            $result = $this->db_target->fetchAll($query);
            foreach($result3 as $row){
                $this->assertTrue(in_array($row,$result));
            }
      }
}

