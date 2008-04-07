<?php
//  Roles privileges
require_once('dblink.php');
require_once('Zend/Acl.php');
require_once('Zend/Acl/Role.php');
require_once('Zend/Acl/Resource.php');
require_once('privilege_def.php');
require_once('tablenames_def.php');
function &acl_initialize(){
    $acl = new Zend_Acl();
    $Zend_db = Zend_Registry::get('Zend_db');
    
    $acl_role = $Zend_db->fetchAll("SELECT role_nickname FROM " . TN_ROLES . "
                                    WHERE role_nickname <> ?",'none');
    foreach($acl_role as $result){
        $acl->addRole(new Zend_Acl_Role($result['role_nickname']));
    }

    $acl_resource = $Zend_db->fetchAll("SELECT distinct function_screen FROM " . TN_FUNCTIONS . "");
    foreach($acl_resource as $result){
        $acl->add(new Zend_Acl_Resource($result['function_screen']));
    }
    
    $sql = "SELECT r.role_nickname,f.function_screen,f.function_action
            FROM " . TN_ROLES . " r, " . TN_ROLE_FUNCTIONS . " rf," . TN_FUNCTIONS . " f
            WHERE rf.role_id = r.role_id AND rf.function_id = f.function_id AND r.role_nickname <> 'none'";
    $res = $Zend_db->fetchAll($sql);
    foreach($res as $result){
        $acl->allow($result['role_nickname'],$result['function_screen'],$result['function_action']);
    }
    return $acl;
}

?>
