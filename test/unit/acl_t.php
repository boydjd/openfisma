<?php

define('REVIEVER', 'REVIEVER');
define('SAISO', 'SAISO');
define('AO', 'AO');
define('ISSO', 'ISSO');
define('ISO', 'ISO');
define('IVV', 'IV&V');
define('AUDITOR', 'AUDITOR');


class TestOfACL extends UnitTestCase {
    public $acl;
    public $privilege;
    public $role_privilege;
    function setUp(){
        require_once(ROOT . DS . 'include' . DS . 'roles_ini.php');
        $this->acl = acl_initialize();
        $privilege = array("dashboard" =>array(PN_READ),
                           "finding"   =>array(PN_READ,PN_UPDATE,PN_CREATE,PN_DELETE),
                           "asset"    =>array(PN_READ,PN_UPDATE,PN_CREATE,PN_DELETE),
                           "remediation" =>array(PN_READ,PN_CREATE_INJECTION,PN_UPDATE_FINDING,PN_DELETE,PN_UPDATE_COURSE_OF_ACTION,
                                          PN_UPDATE_FINDING_ASSIGNMENT,PN_UPDATE_CONTROL_ASSIGNMENT,PN_UPDATE_COUNTERMEASURES,
                                          PN_UPDATE_THREAT,PN_UPDATE_FINDING_RECOMMENDATION,PN_UPDATE_FINDING_RESOURCES,
                                          PN_UPDATE_EST_COMPLETION_DATE,PN_READ_EVIDENCE,PN_UPDATE_EVIDENCE,PN_UPDATE_MITIGATION_STRATEGY_APPROVAL,
                                          PN_UPDATE_EVIDENCE_APPROVAL_FIRST,PN_UPDATE_EVIDENCE_APPROVAL_SECOND,PN_UPDATE_EVIDENCE_APPROVAL_THIRD,
                                          PN_UPDATE_RISK_FIRST,PN_UPDATE_RISK_SECOND,PN_UPDATE_RISK_THIRD),
                            "report"    =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_GENERATE_FISMA_REPORT,PN_GENERATE_GENERAL_REPORT),
                            "vulnerability" =>array(PN_READ,PN_UPDATE,PN_CREATE,PN_DELETE));
        $this->privilege = $privilege;
        $role_privilege = array(REVIEWER =>array(
                                                    "dashboard" => array(PN_READ),
                                                    "asset" => array(PN_READ),
                                                    "finding" => array(PN_READ),
                                                    "remediation" =>array(PN_READ,PN_READ_EVIDENCE),
                                                    "report"     =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_OVERDUE_REPORT),
                                                    "vulnerability" =>array(PN_READ)),
                                 SAISO =>array(
                                                    "dashboard" => array(PN_READ),
                                                    "asset" => array(PN_READ),
                                                    "finding" => array(PN_READ),
                                                    "remediation" =>array(PN_READ,PN_READ_EVIDENCE,PN_UPDATE_EVIDENCE_APPROVAL_THIRD),
                                                    "report"     =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_GENERATE_GENERAL_REPORT,PN_GENERATE_FISMA_REPORT,PN_GENERATE_SYSTEM_RAFS,PN_OVERDUE_REPORT),
                                                    "vulnerability" =>array(PN_READ)),
                                 AO  =>array(
                                                    "dashboard" => array(PN_READ),
                                                    "asset" => array(PN_READ),
                                                    "finding" => array(),
                                                    "remediation" =>array(PN_READ,PN_READ_EVIDENCE),
                                                    "report"     =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_GENERATE_GENERAL_REPORT,PN_GENERATE_SYSTEM_RAFS,PN_OVERDUE_REPORT),
                                                    "vulnerability" =>array(PN_READ)),
                                  ISO =>array(
                                                    "dashboard" => array(PN_READ),
                                                    "asset" => array(PN_READ),
                                                    "finding" => array(),
                                                    "remediation" =>array(PN_READ,PN_UPDATE_COURSE_OF_ACTION,PN_UPDATE_FINDING_RESOURCES,PN_UPDATE_EST_COMPLETION_DATE,PN_UPDATE_RISK_THIRD,PN_READ_EVIDENCE),
                                                    "report"     =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_GENERATE_GENERAL_REPORT,PN_GENERATE_SYSTEM_RAFS,PN_OVERDUE_REPORT),
                                                    "vulnerability" =>array(PN_READ)),
                                  ISSO =>array(
                                                    "dashboard" => array(PN_READ),
                                                    "asset" => array(PN_READ,PN_CREATE,PN_UPDATE),
                                                    "finding" => array(PN_READ,PN_CREATE,PN_UPDATE),
                                                    "remediation" =>array(PN_READ,PN_READ_EVIDENCE,PN_CREATE_INJECTION,PN_UPDATE_FINDING,PN_UPDATE_COURSE_OF_ACTION,
                                                                          PN_UPDATE_CONTROL_ASSIGNMENT,PN_UPDATE_COUNTERMEASURES,PN_UPDATE_THREAT,PN_UPDATE_FINDING_COURSE_OF_ACTION,
                                                                          PN_UPDATE_FINDING_RESOURCES,PN_UPDATE_EST_COMPLETION_DATE,PN_READ_EVIDENCE,PN_UPDATE_EVIDENCE,PN_UPDATE_MITIGATION_STRATEGY_APPROVAL,
                                                                          PN_UPDATE_EVIDENCE_APPROVAL_FIRST,PN_UPDATE_RISK_FIRST),
                                                    "report"     =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_GENERATE_GENERAL_REPORT,PN_GENERATE_SYSTEM_RAFS,PN_OVERDUE_REPORT),
                                                    "vulnerability" =>array(PN_READ,PN_CREATE,PN_UPDATE)),
                                   AUDITOR =>array(
                                                    "dashboard" => array(PN_READ),
                                                    "asset" => array(PN_READ,PN_CREATE,PN_UPDATE),
                                                    "finding" => array(PN_READ,PN_CREATE,PN_UPDATE),
                                                    "remediation" =>array(PN_READ,PN_CREATE_INJECTION,PN_UPDATE_FINDING,PN_UPDATE_FINDING_ASSIGNMENT,PN_UPDATE_CONTROL_ASSIGNMENT,PN_UPDATE_COUNTERMEASURES,
                                                                         PN_UPDATE_THREAT,PN_UPDATE_FINDING_RECOMMENDATION,PN_READ_EVIDENCE),
                                                    "report"     =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_GENERATE_SYSTEM_RAFS),
                                                    "vulnerability" =>array(PN_READ,PN_CREATE,PN_UPDATE)),
                                   IVV   =>array(
                                                    "dashboard" => array(PN_READ),
                                                    "asset" => array(PN_READ),
                                                    "finding" => array(),
                                                    "remediation" =>array(PN_READ,PN_READ_EVIDENCE,PN_UPDATE_EVIDENCE_APPROVAL_SECOND,PN_UPDATE_RISK_SECOND),
                                                    "report"     =>array(PN_READ,PN_GENERATE_POAM_REPORT,PN_GENERATE_SYSTEM_RAFS),
                                                    "vulnerability" =>array(PN_READ)));
        $this->role_privilege = $role_privilege;
                                 

    }
    function testRoles(){
        $role_array = array(REVIEWER,SAISO,AO,ISO,ISSO,AUDITOR,IVV);
        foreach ($role_array as $role) {
            $this->__testRole($role);
        }
    }
    private function __testRole($role){    
        $acl = $this->acl;
        $privilege = $this->privilege;
        $role_privilege = $this->role_privilege;
        foreach ($privilege as $resouce=>$action) {
            foreach ($action as $right) {
                $ret = $acl->isAllowed($role,$resouce,$right);
                $this->assertTrue($ret === in_array($right, $role_privilege[$role][$resouce]) );
            }
        }
    }
    
    function testUserRoles(){
        $db_initialize;
        $user = new user($db);
    }
}
?>
