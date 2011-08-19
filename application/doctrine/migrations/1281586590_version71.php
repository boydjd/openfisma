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
 * Add PM0-11 records to securityControll table
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version71 extends Doctrine_Migration_Base
{
    /**
     * Add PM0-11 to securityControll table
     */
    public function up()
    {
        $pmSecurityControls = array(
            'Rev3_PM_01' => array(
                'code' => 'PM-01',
                'name' => 'Information Security Program Plan',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization:
                            <ol>
                            <li>Develops and disseminates an organization-wide information security program plan that:
                                <ul>
                                <li>Provides an overview of the requirements for the security program and a 
                                description of the security program management controls and common controls in place 
                                or planned for meeting those requirements;</li>
                                <li>Provides sufficient information about the program management controls and common 
                                controls (including specification of parameters for any assignment and selection 
                                operations either explicitly or by reference) to enable an implementation that is 
                                unambiguously compliant with the intent of the plan and a determination of the risk 
                                to be incurred if the plan is implemented as intended;</li>
                                <li>Includes roles, responsibilities, management commitment, coordination among 
                                organizational entities, and compliance;</li>
                                <li>Is approved by a senior official with responsibility and accountability for the 
                                risk being incurred to organizational operations (including mission, functions, 
                                image, and reputation), organizational assets, individuals, other organizations, and 
                                the Nation;</li>
                                </ul>
                            </li>
                            <li>Reviews the organization-wide information security program plan [<em>Assignment: 
                            organization-defined frequency</em>]; and</li>
                            <li>Revises the plan to address organizational changes and problems identified during plan 
                            implementation or security control assessments.</li>
                            </ol>",
                'supplementalGuidance' => "<p>The information security program plan can be represented in a single 
                                           document or compilation of documents at the discretion of the organization. 
                                           The plan documents the organization-wide program management controls and 
                                           organization-defined common controls. The security plans for individual 
                                           information systems and the organization-wide information security 
                                           program plan together, provide complete coverage for all security 
                                           controls employed within the organization. Common controls are documented 
                                           in an appendix to the organization's information security program plan 
                                           unless the controls are included in a separate security plan for an 
                                           information system (e.g., security controls employed as part of an 
                                           intrusion detection system providing organization-wide boundary protection 
                                           inherited by one or more organizational information systems). The 
                                           organization-wide information security program plan will indicate which 
                                           separate security plans contain descriptions of common controls</p>
                                           <p>Organizations have the flexibility to describe common controls in a 
                                           single document or in multiple documents. In the case of multiple 
                                           documents, the documents describing common controls are included as 
                                           attachments to the information security program plan. If the information 
                                           security program plan contains multiple documents, the organization 
                                           specifies in each document the organizational official or officials 
                                           responsible for the development, implementation, assessment, authorization, 
                                           and monitoring of the respective common controls. For example, the 
                                           organization may require that the Facilities Management Office develop, 
                                           implement, assess, authorize, and continuously monitor common physical and 
                                           environmental protection controls from the PE family when such controls 
                                           are not associated with a particular information system but instead, 
                                           support multiple information systems. 
                                           Related control: PM-8.</p>",
                'externalReferences' => 'None.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_02' => array(
                'code' => 'PM-02',
                'name' => 'Senior Information Security Officer',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization appoints a senior information security officer with the mission and 
                              resources to coordinate, develop, implement, and maintain an organization-wide 
                              information security program.",
                'supplementalGuidance' => "The security officer described in this control is an organizational 
                                           official. For a federal agency (as defined in applicable federal laws, 
                                           Executive Orders, directives, policies, or regulations) this official is 
                                           the Senior Agency Information Security Officer. Organizations may also 
                                           refer to this organizational official as the Senior Information Security 
                                           Officer or Chief Information Security Officer.",
                'externalReferences' => 'None.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_03' => array(
                'code' => 'PM-03',
                'name' => 'Information Security Resources',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization:
                              <ol>
                              <li>Ensures that all capital planning and investment requests include the resources 
                              needed to implement the information security program and documents all exceptions to 
                              this requirement;</li>
                              <li>Employs a business case/Exhibit 300/Exhibit 53 to record the resources required; 
                              and</li>
                              <li>Ensures that information security resources are available for expenditure as 
                              planned.</li>
                              </ol>",
                'supplementalGuidance' => "Organizations may designate and empower an Investment Review Board 
                                           (or similar group) to manage and provide oversight for the information 
                                           security-related aspects of the capital planning and investment control 
                                           process. Related controls: PM-4, SA-2.",
                'externalReferences' => 'NIST Special Publication 800-65.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_04' => array(
                'code' => 'PM-04',
                'name' => 'Plan Of Action And Milestones Process',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization implements a process for ensuring that plans of action and milestones 
                              for the security program and the associated organizational information systems are 
                              maintained and document the remedial information security actions to mitigate risk to 
                              organizational operations and assets, individuals, other organizations, and the Nation.",
                'supplementalGuidance' => "The plan of action and milestones is a key document in the information 
                                           security program and is subject to federal reporting requirements 
                                           established by OMB. The plan of action and milestones updates are based on 
                                           the findings from security control assessments, security impact analyses, 
                                           and continuous monitoring activities. OMB FISMA reporting guidance contains 
                                           instructions regarding organizational plans of action and milestones. 
                                           Related control: CA-5.",
                'externalReferences' => 'OMB Memorandum 02-01; NIST Special Publication 800-37.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_05' => array(
                'code' => 'PM-05',
                'name' => 'Information System Inventory',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization develops and maintains an inventory of its information systems.",
                'supplementalGuidance' => "This control addresses the inventory requirements in FISMA. OMB provides 
                                           guidance on developing information systems inventories and associated 
                                           reporting requirements.",
                'externalReferences' => 'None.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_06' => array(
                'code' => 'PM-06',
                'name' => 'Information Security Measures Of Performance',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization develops, monitors, and reports on the results of information security 
                              measures of performance.",
                'supplementalGuidance' => "Measures of performance are outcome-based metrics used by an organization 
                                           to measure the effectiveness or efficiency of the information security 
                                           program and the security controls employed in support of the program.",
                'externalReferences' => 'NIST Special Publication 800-55.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_07' => array(
                'code' => 'PM-07',
                'name' => 'Enterprise Architecture',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization develops an enterprise architecture with consideration for information 
                              security and the resulting risk to organizational operations, organizational assets, 
                              individuals, other organizations, and the Nation.",
                'supplementalGuidance' => "The enterprise architecture developed by the organization is aligned with 
                                           the Federal Enterprise Architecture. The integration of information 
                                           security requirements and associated security controls into the 
                                           organization's enterprise architecture helps to ensure that security 
                                           considerations are addressed by organizations early in the system 
                                           development life cycle and are directly and explicitly related to the 
                                           organization's mission/business processes. This also embeds into the 
                                           enterprise architecture, an integral security architecture consistent with 
                                           organizational risk management and information security strategies. 
                                           Security requirements and control integration are most effectively 
                                           accomplished through the application of the Risk Management Framework 
                                           and supporting security standards and guidelines. The Federal Segment 
                                           Architecture Methodology provides guidance on integrating information 
                                           security requirements and security controls into enterprise architectures. 
                                           Related controls: PL-2, PM-11, RA-2.",
                'externalReferences' => 'NIST Special Publication 800-39; Web: WWW.FSAM.GOV.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_08' => array(
                'code' => 'PM-08',
                'name' => 'Critical Infrastructure Plan',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization addresses information security issues in the development, 
                              documentation, and updating of a critical infrastructure and key resources 
                              protection plan.",
                'supplementalGuidance' => "The requirement and guidance for defining critical infrastructure and key 
                                           resources and for preparing an associated critical infrastructure 
                                           protection plan are found in applicable federal laws, Executive Orders, 
                                           directives, policies, regulations, standards, and guidance. Related 
                                           controls: PM-1, PM-9, PM-11, RA-3.",
                'externalReferences' => 'HSPD 7.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_09' => array(
                'code' => 'PM-09',
                'name' => 'Risk Management Strategy',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization:
                              <ol>
                              <li>Develops a comprehensive strategy to manage risk to organizational operations and 
                              assets, individuals, other organizations, and the Nation associated with the operation 
                              and use of information systems; and</li> 
                              <li>Implements that strategy consistently across the organization.</li>
                              </ol>",
                'supplementalGuidance' => "An organization-wide risk management strategy includes, for example, an 
                                           unambiguous expression of the risk tolerance for the organization, 
                                           acceptable risk assessment methodologies, risk mitigation strategies, a 
                                           process for consistently evaluating risk across the organization with 
                                           respect to the organization's risk tolerance, and approaches for 
                                           monitoring risk over time. The use of a risk executive function can 
                                           facilitate consistent, organization-wide application of the risk 
                                           management strategy. The organization-wide risk management strategy can 
                                           be informed by risk-related inputs from other sources both internal and 
                                           external to the organization to ensure the strategy is both broad-based 
                                           and comprehensive. Related control: RA-3.",
                'externalReferences' => 'NIST Special Publications 800-30, 800-39.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_10' => array(
                'code' => 'PM-10',
                'name' => 'Security Authorization Process',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization:
                              <ol>
                              <li>Manages (i.e., documents, tracks, and reports) the security state of organizational 
                              information systems through security authorization processes;</li>
                              <li>Designates individuals to fulfill specific roles and responsibilities within the 
                              organizational risk management process; and</li>
                              <li>Fully integrates the security authorization processes into an organization-wide 
                              risk management program.</li>
                              </ol>",
                'supplementalGuidance' => "The security authorization process for information systems requires the 
                                           implementation of the Risk Management Framework and the employment of 
                                           associated security standards and guidelines. Specific roles within the 
                                           risk management process include a designated authorizing official for each 
                                           organizational information system. Related control: CA-6.",
                'externalReferences' => 'NIST Special Publications 800-37, 800-39.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            ),
            'Rev3_PM_11' => array(
                'code' => 'PM-11',
                'name' => 'Mission/Business Process Definition',
                'class' => 'MANAGEMENT',
                'family' => 'Program Management',
                'control' => "The organization:
                              <ol>
                              <li>Defines mission/business processes with consideration for information security and 
                              the resulting risk to organizational operations, organizational assets, individuals, 
                              other organizations, and the Nation; and</li>
                              <li>Determines information protection needs arising from the defined mission/business 
                              processes and revises the processes as necessary, until an achievable set of protection 
                              needs is obtained.</li>
                              </ol>",
                'supplementalGuidance' => "Information protection needs are technology-independent, required 
                                           capabilities to counter threats to organizations, individuals, or the 
                                           Nation through the compromise of information (i.e., loss of 
                                           confidentiality, integrity, or availability). Information protection 
                                           needs are derived from the mission/business needs defined by the 
                                           organization, the mission/business processes selected to meet the stated 
                                           needs, and the organizational risk management strategy. Information 
                                           protection needs determine the required security controls for the 
                                           organization and the associated information systems supporting the 
                                           mission/business processes. Inherent in defining an organization's 
                                           information protection needs is an understanding of the level of adverse 
                                           impact that could result if a compromise of information occurs. The 
                                           security categorization process is used to make such potential impact 
                                           determinations. Mission/business process definitions and associated 
                                           information protection requirements are documented by the organization in 
                                           accordance with organizational policy and procedure. Related controls: 
                                           PM-7, PM-8, RA-2.",
                'externalReferences' => 'FIPS Publication 199; NIST Special Publication 800-60.',
                'priorityCode' => 'P1',
                'controlLevel' => 'LOW'
            )
        );

        $securityControlCatalog = Doctrine_Query::create()
                                  ->from('SecurityControlCatalog c')
                                  ->where('c.name = ? ', 'NIST SP 800-53 Rev. 3')
                                  ->fetchOne();

        foreach ($pmSecurityControls as $key => $pmSecurityControl) {
            $securityControl = new SecurityControl();
            $securityControl->code = $pmSecurityControl['code'];
            $securityControl->name = $pmSecurityControl['name'];
            $securityControl->class = $pmSecurityControl['class'];
            $securityControl->family = $pmSecurityControl['family'];
            $securityControl->control = $pmSecurityControl['control'];
            $securityControl->supplementalGuidance = $pmSecurityControl['supplementalGuidance'];
            $securityControl->externalReferences = $pmSecurityControl['externalReferences'];
            $securityControl->priorityCode = $pmSecurityControl['priorityCode'];
            $securityControl->controlLevel = $pmSecurityControl['controlLevel'];
            $securityControl->Catalog = $securityControlCatalog;
            $securityControl->save();
        }
        
    }

    /**
     * Delete the added records from securityControl table 
     */
    public function down()
    {
        $securityControlCode = array(
           'PM-01',
           'PM-02',
           'PM-03',
           'PM-04',
           'PM-05',
           'PM-06',
           'PM-07',
           'PM-08',
           'PM-09',
           'PM-10',
           'PM-11'
        );

        $securityControlCatalog = Doctrine_Query::create()
                                  ->from('SecurityControlCatalog')
                                  ->where('name = ?', 'NIST SP 800-53 Rev. 3')
                                  ->fetchOne();

        $deleteSecurityControl = Doctrine_Query::create()
                                 ->Delete('SecurityControl s')
                                 ->where('s.securitycontrolcatalogid = ?', $securityControlCatalog->id)
                                 ->andWhereIn('s.code', $securityControlCode)
                                 ->execute();
    }
}
