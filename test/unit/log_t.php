<?php
/**
* OpenFISMA
*
* MIT LICENSE
*
* @version $Id$
*/

/**
* Test logging 
*/
require_once MODELS . DS . 'user.php';
class  TestLogging extends UnitTestCase
{
    function setUp(){
        $db = Zend_Registry::get('db');
        $this->db = $db;
    }
     
    function testUserLogging(){            
        $user = new User($this->db);       
        $all_user = $user->fetchAll();
        $id = $all_user->current()->user_id;
        $user->log(User::CREATION, $id, 'test');
        $aid = $this->db->lastInsertID();
        $record = $this->db->fetchAll('SELECT * from account_log where id = '.$aid);
        unset($record[0]['timestamp']);
        $this->assertTrue(array_values($record[0])==array($aid,6,'INFO','creation',$id,'test'));
    }
}
