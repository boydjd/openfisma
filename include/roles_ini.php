<?php
//  Roles privileges
require_once('Zend/acl.php');
require_once('Zend/acl/role.php');
require_once('Zend/acl/resource.php');
require_once('privilege_def.php');

function &acl_initialize(){
    $acl = new Zend_Acl();
    //echo PN_CREATE;die();
    //role:reviewer
    $acl->addRole(new Zend_Acl_Role('reviewer'));
    $acl->add(new Zend_Acl_Resource('dashboard'))
        ->add(new Zend_Acl_Resource('assets'))
        ->add(new Zend_Acl_Resource('finding'))
        ->add(new Zend_Acl_Resource('remediation'))
        ->add(new Zend_Acl_Resource('report'))
        ->add(new Zend_Acl_Resource('vulnerabilities'));
            
    $acl->allow('reviewer','dashboard',PN_READ )
        ->allow('reviewer','assets',PN_READ)
        ->allow('reviewer','finding',PN_READ)
        ->allow('reviewer','remediation',array(PN_READ,PN_READ_EVIDENCE))
        ->allow('reviewer','report',array(PN_READ,PN_GENERATE_POAM_REPORT,PN_OVERDUE_REPORT))
        ->allow('reviewer','vulnerabilities',PN_READ);
    
    //role:public
    $acl->addRole(new Zend_Acl_Role('public'),'reviewer');
    $acl->allow('public','assets',array(PN_CREATE,PN_UPDATE))
        ->allow('public','finding',array(PN_CREATE,PN_UPDATE))
        ->allow('public','remediation',array(PN_CREATE_INJECTION ,PN_UPDATE_FINDING,PN_UPDATE_CONTROL_ASSIGNMENT,PN_UPDATE_COUNTERMEASURES,PN_UPDATE_THREAT))
        ->allow('public','report',PN_GENERATE_SYSTEM_RAFS)
        ->allow('public','vulnerabilities',array(PN_CREATE,PN_UPDATE));
        
    //role:saiso 
    $acl->addRole(new Zend_Acl_Role('saiso'), 'reviewer');
    $acl->allow('saiso','remediation',PN_UPDATE_EVIDENCE_APPROVAL_THIRD)
        ->allow('saiso','report',array(PN_GENERATE_GENERAL_REPORT,PN_GENERATE_FISMA_REPORT,PN_GENERATE_SYSTEM_RAFS));
    
    //role:auditor
    $acl->addRole(new Zend_Acl_Role('auditor'),'public');
    $acl->allow('auditor','remediation',array(PN_UPDATE_FINDING_ASSIGNMENT,PN_UPDATE_FINDING_RECOMMENDATION))
        ->deny('auditor','remediation',PN_OVERDUE_REPORT);
        
    //role:ao    
    $acl->addRole(new Zend_Acl_Role('ao'),'reviewer');
    $acl->deny('ao','finding',PN_READ)
        ->allow('ao','report',array(PN_GENERATE_GENERAL_REPORT,PN_GENERATE_SYSTEM_RAFS));
    
    //role:iso
    $acl->addRole(new Zend_Acl_Role('iso'),'ao');
    $acl->allow('iso','remediation',array(PN_UPDATE_COURSE_OF_ACTION,PN_UPDATE_FINDING_RESOURCES,PN_UPDATE_EST_COMPLETION_DATE,PN_UPDATE_RISK_THIRD));
    
    //role:isso
    $acl->addRole(new Zend_Acl_Role('isso'),'public');
    $acl->allow('isso','remediation',array(PN_UPDATE_COURSE_OF_ACTION,PN_UPDATE_FINDING_COURSE_OF_ACTION,PN_UPDATE_FINDING_RESOURCES,
                PN_UPDATE_EST_COMPLETION_DATE,PN_UPDATE_MITIGATION_STRATEGY_APPROVAL,PN_UPDATE_EVIDENCE,PN_UPDATE_MITIGATION_STRATEGY_APPROVAL,
                PN_UPDATE_EVIDENCE_APPROVAL_FIRST,PN_UPDATE_RISK_FIRST))
        ->allow('isso','report',PN_GENERATE_GENERAL_REPORT);
        
    
    
    //role:ivv
    $acl->addRole(new Zend_Acl_Role('ivv'),'reviewer');
    $acl->allow('ivv','remediation',array(PN_UPDATE_EVIDENCE_APPROVAL_SECOND,PN_UPDATE_RISK_SECOND))
        ->deny('ivv','finding',PN_READ)
        ->deny('ivv','report',PN_OVERDUE_REPORT);
    
    //role:admin               
    $acl->addRole(new Zend_Acl_Role('admin'));
    $acl->add(new Zend_Acl_Resource('admin_users'));
    $acl->add(new Zend_Acl_Resource('admin_roles'));
    $acl->add(new Zend_Acl_Resource('admin_systems'));
    $acl->add(new Zend_Acl_Resource('admin_products'));
    $acl->add(new Zend_Acl_Resource('admin_system_groups'));
    $acl->add(new Zend_Acl_Resource('admin_functions'));
    $acl->add(new Zend_Acl_Resource('admin_finding_source'));
    
    $acl->allow('admin',array('dashboard','finding','assets','report','vulnerabilities','admin_users','admin_roles',
               'admin_systems','admin_products','admin_system_groups','admin_functions','admin_finding_source'));
               
    return $acl;
}

?>
