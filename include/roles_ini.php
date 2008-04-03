<?php
//  Roles privileges
require_once('Zend/Acl.php');
require_once('Zend/Acl/Role.php');
require_once('Zend/Acl/Resource.php');
require_once('privilege_def.php');
require_once('tablenames_def.php');

function &acl_initialize(){
    $acl = new Zend_Acl();
    //role:REVIEWER
    $acl->addRole(new Zend_Acl_Role(REVIEWER));
    $acl->add(new Zend_Acl_Resource('dashboard'))
        ->add(new Zend_Acl_Resource('asset'))
        ->add(new Zend_Acl_Resource('finding'))
        ->add(new Zend_Acl_Resource('remediation'))
        ->add(new Zend_Acl_Resource('report'))
        ->add(new Zend_Acl_Resource('vulnerability'));

    $acl->allow(REVIEWER,'dashboard',PN_READ )
        ->allow(REVIEWER,'asset',PN_READ)
        ->allow(REVIEWER,'finding',PN_READ)
        ->allow(REVIEWER,'remediation',array(PN_READ,PN_READ_EVIDENCE))
        ->allow(REVIEWER,'report',array(PN_READ,PN_GENERATE_POAM_REPORT,PN_OVERDUE_REPORT))
        ->allow(REVIEWER,'vulnerability',PN_READ);

    //role:public
    $acl->addRole(new Zend_Acl_Role('public'),REVIEWER);
    $acl->allow('public','asset',array(PN_CREATE,PN_UPDATE))
        ->allow('public','finding',array(PN_CREATE,PN_UPDATE))
        ->allow('public','remediation',array(PN_CREATE_INJECTION ,PN_UPDATE_FINDING,PN_UPDATE_CONTROL_ASSIGNMENT,PN_UPDATE_COUNTERMEASURES,PN_UPDATE_THREAT))
        ->allow('public','report',PN_GENERATE_SYSTEM_RAFS)
        ->allow('public','vulnerability',array(PN_CREATE,PN_UPDATE));

    //role:SAISO
    $acl->addRole(new Zend_Acl_Role(SAISO), REVIEWER);
    $acl->allow(SAISO,'remediation',PN_UPDATE_EVIDENCE_APPROVAL_THIRD)
        ->allow(SAISO,'report',array(PN_GENERATE_GENERAL_REPORT,PN_GENERATE_FISMA_REPORT,PN_GENERATE_SYSTEM_RAFS));

    //role:AUDITOR
    $acl->addRole(new Zend_Acl_Role(AUDITOR),'public');
    $acl->allow(AUDITOR,'remediation',array(PN_UPDATE_FINDING_ASSIGNMENT,PN_UPDATE_FINDING_RECOMMENDATION))
        ->deny(AUDITOR,'report',PN_OVERDUE_REPORT);

    //role:AO
    $acl->addRole(new Zend_Acl_Role(AO),REVIEWER);
    $acl->deny(AO,'finding',PN_READ)
        ->allow(AO,'report',array(PN_GENERATE_GENERAL_REPORT,PN_GENERATE_SYSTEM_RAFS));

    //role:ISO
    $acl->addRole(new Zend_Acl_Role(ISO),AO);
    $acl->allow(ISO,'remediation',array(PN_UPDATE_COURSE_OF_ACTION,PN_UPDATE_FINDING_RESOURCES,PN_UPDATE_EST_COMPLETION_DATE,PN_UPDATE_RISK_THIRD));

    //role:ISSO
    $acl->addRole(new Zend_Acl_Role(ISSO),'public');
    $acl->allow(ISSO,'remediation',array(PN_UPDATE_COURSE_OF_ACTION,PN_UPDATE_FINDING_COURSE_OF_ACTION,PN_UPDATE_FINDING_RESOURCES,
                PN_UPDATE_EST_COMPLETION_DATE,PN_UPDATE_MITIGATION_STRATEGY_APPROVAL,PN_UPDATE_EVIDENCE,PN_UPDATE_MITIGATION_STRATEGY_APPROVAL,
                PN_UPDATE_EVIDENCE_APPROVAL_FIRST,PN_UPDATE_RISK_FIRST))
        ->allow(ISSO,'report',PN_GENERATE_GENERAL_REPORT);



    //role:IVV
    $acl->addRole(new Zend_Acl_Role(IVV),REVIEWER);
    $acl->allow(IVV,'remediation',array(PN_UPDATE_EVIDENCE_APPROVAL_SECOND,PN_UPDATE_RISK_SECOND))
        ->allow(IVV,'report',PN_GENERATE_SYSTEM_RAFS)
        ->deny(IVV,'finding',PN_READ)
        ->deny(IVV,'report',PN_OVERDUE_REPORT);

    //role:ADMIN
    $acl->addRole(new Zend_Acl_Role(ADMIN));
    $acl->add(new Zend_Acl_Resource('header'));
    $acl->add(new Zend_Acl_Resource('admin_users'));
    $acl->add(new Zend_Acl_Resource('admin_roles'));
    $acl->add(new Zend_Acl_Resource('admin_systems'));
    $acl->add(new Zend_Acl_Resource('admin_products'));
    $acl->add(new Zend_Acl_Resource('admin_system_groups'));
    $acl->add(new Zend_Acl_Resource('admin_functions'));
    $acl->add(new Zend_Acl_Resource('admin_role_functions'));
    $acl->add(new Zend_Acl_Resource('admin_finding_source'));
    $acl->add(new Zend_Acl_Resource('admin_system_group_systems'));

    $acl->allow(ADMIN,'finding',array('read','delete'))
        ->allow(ADMIN,'remediation',array('read','delete'))
        ->allow(ADMIN,array('dashboard','asset','report','vulnerability','header','admin_users','admin_roles',
               'admin_systems','admin_products','admin_system_groups','admin_functions','admin_finding_source'));

    return $acl;
}

?>
