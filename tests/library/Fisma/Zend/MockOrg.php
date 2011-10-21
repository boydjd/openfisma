<?php
/**
 ** Dummy class to implements Fisma_Zend_Acl_OriganizationDependency
 ** @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 ** @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 ** @license    http://www.openfisma.org/content/license GPLv3
 ** @package    Test
 ** @subpackage Test_Library
 **/

class Test_Library_Fisma_Zend_MockOrg implements Fisma_Zend_Acl_OrganizationDependency
{
    public $orgId;
    public function __construct($orgId = '')
    {
        $this->orgId = $orgId;
    }
    public function getOrganizationDependencyId()
    {
        return $this->orgId;
    }
}
