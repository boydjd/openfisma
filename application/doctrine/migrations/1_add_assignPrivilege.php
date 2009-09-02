<?php
/**
 * Add a privilege to control the role definition, 
 *　then assign this privilege to ADMIN.
 */
Class AddPrivilege extends Doctrine_Migration_Base
{
    public function up()
    {
        $privilege = new Privilege();
        $privilege->resource    = 'role';
        $privilege->action      = 'assignPrivileges';
        $privilege->description = 'Assign Privileges';
        $privilege->save();

        $rolePrivilege = new RolePrivilege();
        $rolePrivilege->roleId     = Doctrine::getTable('Role')->findOneByNickname('ADMIN')->id;
        $rolePrivilege->privilegeId = $privilege->id;
        $rolePrivilege->save();
    }
    
    public function down()
    {
        $role      = Doctrine::getTable('Role')->findOneByNickname('ADMIN');
        $privilege = Doctrine::getTable('Privilege')->findOneByAction('assignPrivileges');
        $rolePrivilege = new RolePrivilege();
        $rolePrivilege = Doctrine::getTable('RolePrivilege')
                            ->findByDql("roleId = $role->id AND privilegeId = $privilege->id");
        $rolePrivilege[0]->delete();
        $privilege->delete();
    }
}
   
