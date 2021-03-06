<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Reports for organizations (in the system inventory module)
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 */
class OrganizationReportController extends Fisma_Zend_Controller_Action_Security
{
    public function init()
    {
        parent::init();

        $this->_helper->reportContextSwitch()
                      ->addActionContext('personnel', array('html', 'pdf', 'xls'))
                      ->addActionContext('privacy', array('html', 'pdf', 'xls'))
                      ->addActionContext('security-authorization', array('html', 'pdf', 'xls'))
                      ->addActionContext('documentation-compliance', array('html', 'pdf', 'xls'))
                      ->initContext();
    }

    /**
     * Check that the user has the privilege to run reports
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('system_inventory_report');
    }

    /**
     * List ISO and ISSO personnel for each organization and system
     *
     * This is the one report which is not constrained by the organizations which a user is allowed to view. This report
     * is kind of like a phone book for security personnel, so all users are allowed to view all entries in it.
     *
     * @GETAllowed
     */
    public function personnelAction()
    {
        $personnelQuery = Doctrine_Query::create()
                          ->select('r.nickname, o.nickname, u.nameLast, u.nameFirst, u.phoneOffice, u.email')
                          ->from('Organization o')
                          ->leftJoin('o.System s')
                          ->leftJoin('o.OrganizationType orgType')
                          ->leftJoin('o.UserRole ur')
                          ->leftJoin('ur.Role r')
                          ->leftJoin('ur.User u')
                          ->andWhere('orgType.nickname = ?', array('system'))
                          ->andWhere('s.sdlcPhase <> ?', 'disposal')
                          ->andWhere('r.nickname LIKE ? OR r.nickname LIKE ?', array('ISO', 'ISSO'))
                          ->andWhere("u.locktype IS NULL OR u.locktype<>'manual'")
                          ->orderBy('o.lft, o.rgt, u.nameLast, u.nameFirst')
                          ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $personnel = $personnelQuery->execute();

        $report = new Fisma_Report();

        $report->setTitle('Personnel Report')
               ->addColumn(new Fisma_Report_Column('System', true))
               ->addColumn(new Fisma_Report_Column('Role', true))
               ->addColumn(new Fisma_Report_Column('Last Name', true))
               ->addColumn(new Fisma_Report_Column('First Name', true))
               ->addColumn(new Fisma_Report_Column('Office Phone', true))
               ->addColumn(new Fisma_Report_Column('E-mail', true))
               ->setData($personnel);

        $this->_helper->reportContextSwitch()->setReport($report);
    }

    /**
     * List privacy status for all systems
     *
     * @GETAllowed
     */
    public function privacyAction()
    {
        $storageNamespace = 'Organization.Privacy.Report';
        $orgTypeId = $this->_helper->OrganizationType
                          ->getOrganizationTypeIdByStorageOrRequest($this->_me->id, $storageNamespace);
        $filterForm = $this->_helper->OrganizationType->getFilterForm($orgTypeId);

        $this->view->orgTypeId = $orgTypeId;
        $this->view->organizationTypeForm = $filterForm;
        $this->view->namespace = $storageNamespace;
        $this->view->url = "/organization-report/privacy/format/html";

        $baseQuery = CurrentUser::getInstance()->getOrganizationsByPrivilegeQuery('organization', 'read');

        if ('none' != $orgTypeId) {
            $systemQuery = $baseQuery->select('bureau.nickname AS name')
                            ->addSelect('o.nickname AS name');
        } else {
            $systemQuery = $baseQuery->select('o.nickname AS name');
        }

        $systemQuery
                    ->addSelect('systemData.hasPii AS has_pii')
                    ->addSelect('systemData.piaRequired AS pia_required')
                    ->addSelect(
                        'IF(\'YES\' = systemData.piaRequired,
                            IF(systemData.piaUrl IS NULL, \'NO\', \'YES\'),
                            \'N/A\') AS pia_url'
                    )
                    ->addSelect('systemData.sornRequired AS sorn_required')
                    ->addSelect(
                        'IF(\'YES\' = systemData.sornRequired,
                            IF(systemData.sornUrl IS NULL, \'NO\', \'YES\'),
                            \'N/A\') AS sorn_url'
                    )
                    ->innerJoin('o.System systemData')
                    ->innerJoin('o.OrganizationType orgType')
                    ->leftJoin('Organization bureau')
                    ->andWhere('orgType.nickname = ?', array('system'))
                    ->andWhere('systemData.sdlcPhase <> ?', 'disposal')
                    ->andWhere('o.lft BETWEEN bureau.lft and bureau.rgt')
                    ->orderBy('bureau.nickname, o.nickname')
                    ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if ('none' != $orgTypeId) {
            $systemQuery->andWhere('bureau.orgTypeId = ?', $orgTypeId);
        }
        $systems = $systemQuery->execute();

        $report = new Fisma_Report();

        $report->setTitle('Privacy Report');

        $orgType = Doctrine::getTable('OrganizationType')->find($orgTypeId);

        if ('none' != $orgTypeId) {
            $report->addColumn(new Fisma_Report_Column(ucwords($orgType->nickname), true))
                   ->addColumn(new Fisma_Report_Column('System', true));
        } else {
            $report->addColumn(new Fisma_Report_Column('System', true));
        }

        $report->addColumn(new Fisma_Report_Column('Contains PII', true))
               ->addColumn(new Fisma_Report_Column('PIA Required', true))
               ->addColumn(new Fisma_Report_Column('PIA Completed', true, 'Fisma.TableFormat.yesNo'))
               ->addColumn(new Fisma_Report_Column('SORN Required', true))
               ->addColumn(new Fisma_Report_Column('SORN Completed', true, 'Fisma.TableFormat.yesNo'))
               ->setData($systems);

        $this->_helper->reportContextSwitch()->setReport($report);
    }

    /**
     * List security authorization status for all systems
     *
     * @GETAllowed
     */
    public function securityAuthorizationAction()
    {
        $storageNamespace = 'Organization.SecurityAuth.Report';
        $orgTypeId = $this->_helper->OrganizationType
                                   ->getOrganizationTypeIdByStorageOrRequest($this->_me->id, $storageNamespace);
        $filterForm = $this->_helper->OrganizationType->getFilterForm($orgTypeId);

        $this->view->orgTypeId = $orgTypeId;
        $this->view->organizationTypeForm = $filterForm;
        $this->view->namespace = $storageNamespace;
        $this->view->url = "/organization-report/security-authorization/format/html";

        $systemQuery = CurrentUser::getInstance()->getOrganizationsByPrivilegeQuery('organization', 'read');

        $systemQuery->select('o.nickname AS name');
        if ('none' != $orgTypeId) {
            $systemQuery->addSelect('\'\' AS parent');
        }

        $systemQuery->addSelect('IFNULL(systemData.fipsCategory, \'NONE\') AS fips_category')
                    ->addSelect('IFNULL(systemData.controlledBy, \'N/A\') AS operated_by')
                    ->addSelect('IFNULL(systemData.securityAuthorizationDt, \'N/A\') AS security_auth_dt')
                    ->addSelect('IFNULL(systemData.controlAssessmentDt, \'N/A\') AS self_assessment_dt')
                    ->addSelect('IFNULL(systemData.contingencyPlanTestDt, \'N/A\') AS cplan_test_dt')
                    ->addSelect("IF(systemData.fismaReportable,'Yes','No') AS fisma_reportable")
                    ->innerJoin('o.System systemData')
                    ->innerJoin('o.OrganizationType orgType')
                    ->andWhere('orgType.nickname = ?', array('system'))
                    ->andWhere('systemData.sdlcPhase <> ?', 'disposal')
                    ->orderBy('o.nickname')
                    ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        $systems = $systemQuery->execute();

        if ('none' != $orgTypeId) {
            $baseQuery = CurrentUser::getInstance()->getOrganizationsByPrivilegeQuery('organization', 'read')
                ->select('o.nickname as id, bureau.nickname as name')
                ->leftJoin('Organization bureau')
                ->andWhere('o.lft BETWEEN bureau.lft and bureau.rgt')
                ->andWhere('bureau.orgTypeId = ?', $orgTypeId)
                ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
            $parents = $baseQuery->execute();
            foreach ($parents as $key => $value) {
                $parents[$value['o_id']] = $value['bureau_name'];
                unset($parents[$key]);
            }

            foreach ($systems as &$system) {
                if (isset($parents[$system['o_name']])) {
                    $system['o_parent'] = $parents[$system['o_name']];
                }
            }
        }

        $report = new Fisma_Report();

        $report->setTitle('Security Authorizations Report');
        $orgType = Doctrine::getTable('OrganizationType')->find($orgTypeId);

        $report->addColumn(new Fisma_Report_Column('System', true));
        if ('none' != $orgTypeId) {
            $report->addColumn(new Fisma_Report_Column(ucwords($orgType->nickname), true));
        }

        $report->addColumn(new Fisma_Report_Column('FIPS 199', true))
               ->addColumn(new Fisma_Report_Column('Operated By', true))
               ->addColumn(
                   new Fisma_Report_Column(
                       'Security Authorization',
                       true,
                       'Fisma.TableFormat.securityAuthorization'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       'Self-Assessment',
                       true,
                       'Fisma.TableFormat.selfAssessment'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       'Contingency Plan Test',
                       true,
                       'Fisma.TableFormat.contingencyPlanTest'
                   )
               )
               ->addColumn(new Fisma_Report_Column('FISMA Reportable', true))
               ->setData($systems);

        $this->_helper->reportContextSwitch()->setReport($report);
    }

    /**
     * Generate documentation compliance report
     *
     * @GETAllowed
     */
    public function documentationComplianceAction()
    {
        $systemDocuments = Doctrine::getTable('SystemDocument')->getSystemDocumentReportDataQuery()->execute();
        $allRequiredDocumentTypeName = Doctrine::getTable('DocumentType')
                                       ->getAllRequiredDocumentTypeQuery()
                                       ->execute()
                                       ->toKeyValueArray('id', 'name');
        $systemData = array();
        foreach ($systemDocuments as $systemDocument) {
            $systemData[] = array(
                $systemDocument['o_name'],
                $systemDocument['dt_percentage'],
                $this->_getMissingDocumentTypeName($allRequiredDocumentTypeName,
                                                   $systemDocument['dt_uploadedRequiredDocument'])
            );
        }

        $report = new Fisma_Report();

        $report->setTitle('Documentation Compliance Report')
               ->addColumn(new Fisma_Report_Column('System', true))
               ->addColumn(
                   new Fisma_Report_Column(
                       'Percentage',
                       true,
                       'Fisma.TableFormat.completeDocTypePercentage',
                       null,
                       false,
                       'number'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       'Incomplete',
                       true,
                       'Fisma.TableFormat.incompleteDocumentType'
                   )
               )
               ->setData($systemData);

        $this->_helper->reportContextSwitch()->setReport($report);
    }

    /**
     * Get the missing document type name(s) by comparing $allRequiredDocumentType
     * and $uploadedRequiredDocumentType
     */
    private function _getMissingDocumentTypeName($allRequiredDocumentType, $uploadedRequiredDocumentType)
    {
        if ('N/A' != $uploadedRequiredDocumentType && count($allRequiredDocumentType) > 0) {
            $uploadedRequiredDocumentTypeArray = explode(',', $uploadedRequiredDocumentType);
            $missingDocumentTypeNames = array_diff($allRequiredDocumentType, $uploadedRequiredDocumentTypeArray);

            return count($missingDocumentTypeNames) > 0 ? join(',', $missingDocumentTypeNames) : 'N/A';
        } else if (count($allRequiredDocumentType) > 0) {
            return join(',', $allRequiredDocumentType);
        } else {
            return 'N/A';
        }
    }
}
